<?php

namespace Tests\Feature;

use App\AdminSetting;
use App\Business;
use App\BusinessCentralLocationMapping;
use App\BusinessCentralMapping;
use App\BusinessCentralSalesperson;
use App\Contact;
use App\InvoiceLayout;
use App\InvoiceScheme;
use App\Product;
use App\ProductUnitConversion;
use App\ProductVariation;
use App\PurchaseLine;
use App\Services\BusinessCentralSyncService;
use App\Transaction;
use App\Unit;
use App\User;
use App\Variation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BusinessCentralSalespersonSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_salespeople_without_creating_pos_users(): void
    {
        $business = $this->configureIntegration();

        Http::fake([
            'https://bc.test/BC240/api/v2.0/companies(company-id)/salespeoplePurchasers*' => Http::response([
                'value' => [[
                    'id' => 'salesperson-id',
                    'code' => 'SP001',
                    'displayName' => 'Jane Seller',
                    'email' => 'jane@example.test',
                    'phoneNo' => '0700000000',
                    'blocked' => '_x0020_',
                ]],
            ]),
        ]);

        $userCount = User::count();
        $count = app(BusinessCentralSyncService::class)->syncSalespeople();

        $this->assertSame(1, $count);
        $this->assertSame($userCount, User::count());
        $this->assertDatabaseHas('business_central_salespeople', [
            'business_id' => $business->id,
            'bc_id' => 'salesperson-id',
            'code' => 'SP001',
            'display_name' => 'Jane Seller',
            'available_in_bc' => true,
            'pos_user_id' => null,
        ]);
        $this->assertFalse(BusinessCentralSalesperson::first()->blocked);
    }

    public function test_it_imports_salespeople_from_a_bc24_odata_page(): void
    {
        $business = $this->configureIntegration();
        AdminSetting::first()->update([
            'business_central_salespeople_url' => "https://bc.test/BC240/ODataV4/Company('TEST')/SalespeoplePurchasers",
        ]);

        Http::fake([
            "https://bc.test/BC240/ODataV4/Company('TEST')/SalespeoplePurchasers" => Http::response([
                'value' => [[
                    '@odata.etag' => 'W/"etag"',
                    'Code' => 'SP002',
                    'Name' => 'BC24 Seller',
                    'Phone_No' => '0712345678',
                    'Privacy_Blocked' => false,
                ]],
            ]),
        ]);

        $count = app(BusinessCentralSyncService::class)->syncSalespeople();

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('business_central_salespeople', [
            'business_id' => $business->id,
            'bc_id' => 'SP002',
            'code' => 'SP002',
            'display_name' => 'BC24 Seller',
            'phone_number' => '0712345678',
            'blocked' => false,
            'available_in_bc' => true,
        ]);
    }

    public function test_disabled_integration_leaves_pos_running_without_bc_requests(): void
    {
        $business = $this->configureIntegration();
        $importedCustomer = Contact::create([
            'business_id' => $business->id,
            'type' => 'customer',
            'name' => 'Imported Customer',
            'mobile' => '-',
            'created_by' => $business->owner_id,
            'contact_status' => 'active',
            'is_default' => 1,
            'contact_id' => 'CUS001',
        ]);
        BusinessCentralMapping::create([
            'business_id' => $business->id,
            'entity_type' => 'customer',
            'local_id' => $importedCustomer->id,
            'bc_number' => 'CUS001',
        ]);
        AdminSetting::first()->update(['business_central_enabled' => false]);
        Http::fake();

        $result = app(BusinessCentralSyncService::class)->syncAll();

        $this->assertFalse($result['enabled']);
        $this->assertSame(0, $result['salespeople']);
        $this->assertDatabaseHas('contacts', [
            'business_id' => $business->id,
            'contact_id' => 'CO0001',
            'name' => 'Walk-In Customer',
            'contact_status' => 'active',
            'is_default' => 1,
        ]);
        $this->assertDatabaseHas('contacts', [
            'id' => $importedCustomer->id,
            'is_default' => 0,
        ]);
        Http::assertNothingSent();
    }

    public function test_strict_sale_mode_only_applies_when_business_central_is_enabled(): void
    {
        $business = $this->configureIntegration();
        $settings = AdminSetting::first();
        $settings->update(['business_central_require_sale_sync' => true]);
        $service = app(BusinessCentralSyncService::class);

        $this->assertTrue($service->requiresSuccessfulSaleSyncFor($business->id));

        $settings->update(['business_central_enabled' => false]);
        $this->assertFalse(
            app(BusinessCentralSyncService::class)->requiresSuccessfulSaleSyncFor($business->id)
        );
    }

    public function test_customer_sync_never_overwrites_the_default_walk_in_customer(): void
    {
        $business = $this->configureIntegration();
        $walkInCustomer = Contact::create([
            'business_id' => $business->id,
            'type' => 'customer',
            'name' => 'Walk-In Customer',
            'mobile' => '-',
            'created_by' => $business->owner_id,
            'contact_status' => 'active',
            'is_default' => 1,
            'contact_id' => 'CO0001',
            'custom_field1' => 'CUS001',
        ]);
        Http::fake([
            'https://bc.test/BC240/api/v2.0/companies(company-id)/customers*' => Http::response([
                'value' => [[
                    'id' => 'customer-id',
                    'number' => 'CUS001',
                    'displayName' => 'Imported Customer',
                    'taxRegistrationNumber' => 'A012345678Z',
                    'creditLimit' => 150000,
                    'paymentTermsId' => 'term-30d',
                    'balanceDue' => 2500.75,
                    'blocked' => '_x0020_',
                ]],
            ]),
            'https://bc.test/BC240/api/v2.0/companies(company-id)/paymentTerms*' => Http::response([
                'value' => [[
                    'id' => 'term-30d',
                    'code' => '30D',
                    'dueDateCalculation' => '30D',
                ]],
            ]),
        ]);

        $this->assertSame(1, app(BusinessCentralSyncService::class)->syncCustomers());

        $this->assertDatabaseHas('contacts', [
            'id' => $walkInCustomer->id,
            'contact_id' => 'CO0001',
            'name' => 'Walk-In Customer',
            'is_default' => 1,
        ]);
        $this->assertDatabaseHas('contacts', [
            'business_id' => $business->id,
            'contact_id' => 'CUS001',
            'name' => 'Imported Customer',
            'is_default' => 0,
            'contact_status' => 'active',
            'tax_number' => 'A012345678Z',
            'credit_limit' => 150000,
            'pay_term_number' => 30,
            'pay_term_type' => 'days',
            'bc_balance_due' => 2500.75,
        ]);
        $this->assertSame(2, Contact::where('business_id', $business->id)->count());

        $importedContact = Contact::where('business_id', $business->id)
            ->where('contact_id', 'CUS001')
            ->firstOrFail();
        $transactionUtil = app(\App\Utils\TransactionUtil::class);
        $this->assertSame(2500.75, (float) $transactionUtil->getContactDue($importedContact->id, $business->id));

        // Disabled integration must stop overriding the POS-computed due.
        $balanceDueProbe = new class extends \App\Utils\Util
        {
            public function bcBalanceDue($contactId, $businessId): ?float
            {
                return $this->getBusinessCentralBalanceDue($contactId, $businessId);
            }
        };
        AdminSetting::first()->update(['business_central_enabled' => false]);
        $this->assertNull($balanceDueProbe->bcBalanceDue($importedContact->id, $business->id));
    }

    public function test_inventory_sync_creates_and_reconciles_a_sellable_purchase_layer(): void
    {
        $business = $this->configureIntegration();
        $invoiceLayout = InvoiceLayout::create([
            'name' => 'Default Layout',
            'business_id' => $business->id,
        ]);
        $invoiceScheme = InvoiceScheme::create([
            'business_id' => $business->id,
            'name' => 'Default Scheme',
            'scheme_type' => 'blank',
            'start_number' => 1,
            'invoice_count' => 0,
            'total_digits' => 4,
            'is_default' => true,
        ]);
        $locationId = DB::table('business_locations')->insertGetId([
            'business_id' => $business->id,
            'name' => 'Main Branch',
            'country' => 'Kenya',
            'state' => 'Nairobi',
            'city' => 'Nairobi',
            'zip_code' => '00100',
            'invoice_scheme_id' => $invoiceScheme->id,
            'invoice_layout_id' => $invoiceLayout->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $unitId = DB::table('units')->insertGetId([
            'business_id' => $business->id,
            'actual_name' => 'Pieces',
            'short_name' => 'PCS',
            'allow_decimal' => true,
            'created_by' => $business->owner_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $product = Product::create([
            'business_id' => $business->id,
            'unit_id' => $unitId,
            'name' => 'BC Item',
            'sku' => 'ITM00038',
            'type' => 'single',
            'tax_type' => 'exclusive',
            'barcode_type' => 'C128',
            'enable_stock' => true,
            'created_by' => $business->owner_id,
        ]);
        $productVariation = ProductVariation::create([
            'product_id' => $product->id,
            'name' => 'DUMMY',
            'is_dummy' => true,
        ]);
        $variation = Variation::create([
            'product_id' => $product->id,
            'product_variation_id' => $productVariation->id,
            'name' => 'DUMMY',
            'sub_sku' => 'ITM00038',
            'default_purchase_price' => 10,
            'dpp_inc_tax' => 10,
            'default_sell_price' => 15,
            'sell_price_inc_tax' => 15,
        ]);
        BusinessCentralMapping::create([
            'business_id' => $business->id,
            'entity_type' => 'product',
            'local_id' => $product->id,
            'bc_id' => 'bc-item-id',
            'bc_number' => 'ITM00038',
            'status' => 'synced',
        ]);
        BusinessCentralLocationMapping::create([
            'business_id' => $business->id,
            'pos_location_id' => $locationId,
            'bc_location_code' => 'HQ',
            'bc_location_id' => 'bc-location-id',
            'enabled' => true,
        ]);
        AdminSetting::first()->update([
            'business_central_inventory_url' => 'https://bc.test/odata/inventory',
        ]);
        Http::fake([
            'https://bc.test/odata/inventory*' => Http::sequence()
                ->push([
                    'value' => [[
                        'itemNumber' => 'ITM00038',
                        'locationCode' => 'HQ',
                        'remainingQuantity' => 10,
                    ]],
                ])
                ->push([
                    'value' => [[
                        'itemNumber' => 'ITM00038',
                        'locationCode' => 'HQ',
                        'remainingQuantity' => 8,
                    ]],
                ]),
        ]);

        app(BusinessCentralSyncService::class)->syncInventory();

        $bridge = Transaction::where('opening_stock_product_id', $product->id)
            ->where('location_id', $locationId)
            ->where('additional_notes', 'Business Central inventory bridge')
            ->firstOrFail();
        $this->assertSame('opening_stock', $bridge->type);
        $line = PurchaseLine::where('transaction_id', $bridge->id)->firstOrFail();
        $this->assertSame(10.0, $line->quantity);
        $this->assertDatabaseHas('variation_location_details', [
            'product_id' => $product->id,
            'variation_id' => $variation->id,
            'location_id' => $locationId,
            'qty_available' => 10,
        ]);

        $line->update(['quantity_sold' => 4]);
        app(BusinessCentralSyncService::class)->syncInventory();

        $line->refresh();
        $this->assertSame(12.0, $line->quantity);
        $this->assertSame(8.0, $line->quantity_remaining);
    }

    public function test_it_imports_bc_vendors_as_pos_suppliers(): void
    {
        $business = $this->configureIntegration();

        Http::fake([
            'https://bc.test/BC240/api/v2.0/companies(company-id)/vendors*' => Http::response([
                'value' => [[
                    'id' => 'vendor-id',
                    'number' => 'V0001',
                    'displayName' => 'Medical Supplies Ltd',
                    'addressLine1' => 'Industrial Road',
                    'city' => 'Nairobi',
                    'country' => 'KE',
                    'phoneNumber' => '0711000000',
                    'email' => 'vendor@example.test',
                    'taxRegistrationNumber' => 'P111111111A',
                    'balance' => 7800.5,
                    'blocked' => false,
                ]],
            ]),
        ]);

        $count = app(BusinessCentralSyncService::class)->syncVendors();

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('contacts', [
            'business_id' => $business->id,
            'type' => 'supplier',
            'contact_id' => 'V0001',
            'supplier_business_name' => 'Medical Supplies Ltd',
            'contact_status' => 'active',
            'tax_number' => 'P111111111A',
            'bc_purchase_balance_due' => 7800.5,
        ]);
        $this->assertDatabaseHas('business_central_mappings', [
            'business_id' => $business->id,
            'entity_type' => 'vendor',
            'bc_id' => 'vendor-id',
            'bc_number' => 'V0001',
            'status' => 'synced',
        ]);
    }

    public function test_it_imports_bc_purchase_invoices_for_suppliers(): void
    {
        $business = $this->configureIntegration();
        $invoiceLayout = InvoiceLayout::create([
            'name' => 'Default Layout',
            'business_id' => $business->id,
        ]);
        $invoiceScheme = InvoiceScheme::create([
            'business_id' => $business->id,
            'name' => 'Default Scheme',
            'scheme_type' => 'blank',
            'start_number' => 1,
            'invoice_count' => 0,
            'total_digits' => 4,
            'is_default' => true,
        ]);
        $locationId = DB::table('business_locations')->insertGetId([
            'business_id' => $business->id,
            'name' => 'Main Branch',
            'country' => 'Kenya',
            'state' => 'Nairobi',
            'city' => 'Nairobi',
            'zip_code' => '00100',
            'invoice_scheme_id' => $invoiceScheme->id,
            'invoice_layout_id' => $invoiceLayout->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        BusinessCentralLocationMapping::create([
            'business_id' => $business->id,
            'pos_location_id' => $locationId,
            'bc_location_code' => 'HQ',
            'bc_location_id' => 'bc-location-id',
            'enabled' => true,
        ]);
        $supplier = Contact::create([
            'business_id' => $business->id,
            'type' => 'supplier',
            'name' => 'Medical Supplies Ltd',
            'supplier_business_name' => 'Medical Supplies Ltd',
            'contact_id' => 'V0001',
            'mobile' => '-',
            'created_by' => $business->owner_id,
        ]);
        BusinessCentralMapping::create([
            'business_id' => $business->id,
            'entity_type' => 'vendor',
            'local_id' => $supplier->id,
            'bc_id' => 'vendor-id',
            'bc_number' => 'V0001',
            'status' => 'synced',
        ]);
        $unitId = DB::table('units')->insertGetId([
            'business_id' => $business->id,
            'actual_name' => 'Pieces',
            'short_name' => 'PCS',
            'allow_decimal' => true,
            'created_by' => $business->owner_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $product = Product::create([
            'business_id' => $business->id,
            'unit_id' => $unitId,
            'name' => 'BC Item',
            'sku' => 'ITM00038',
            'type' => 'single',
            'tax_type' => 'exclusive',
            'barcode_type' => 'C128',
            'enable_stock' => true,
            'created_by' => $business->owner_id,
        ]);
        $productVariation = ProductVariation::create([
            'product_id' => $product->id,
            'name' => 'DUMMY',
            'is_dummy' => true,
        ]);
        $variation = Variation::create([
            'product_id' => $product->id,
            'product_variation_id' => $productVariation->id,
            'name' => 'DUMMY',
            'sub_sku' => 'ITM00038',
            'default_purchase_price' => 10,
            'dpp_inc_tax' => 10,
            'default_sell_price' => 15,
            'sell_price_inc_tax' => 15,
        ]);
        BusinessCentralMapping::create([
            'business_id' => $business->id,
            'entity_type' => 'product',
            'local_id' => $product->id,
            'bc_id' => 'bc-item-id',
            'bc_number' => 'ITM00038',
            'status' => 'synced',
        ]);

        $invoiceResponse = [
            'value' => [[
                'id' => 'purchase-invoice-id',
                'number' => 'PINV001',
                'vendorId' => 'vendor-id',
                'vendorNumber' => 'V0001',
                'invoiceDate' => '2026-11-10',
                'postingDate' => '2026-11-10',
                'status' => 'Open',
                'totalAmountExcludingTax' => 1100,
                'totalAmountIncludingTax' => 1276,
                'totalTaxAmount' => 176,
            ]],
        ];
        $invoiceLinesResponse = [
            'value' => [[
                'lineType' => 'Item',
                'lineObjectNumber' => 'ITM00038',
                'quantity' => 100,
                'unitOfMeasureCode' => 'PCS',
                'amountExcludingTax' => 1100,
                'amountIncludingTax' => 1276,
                'locationId' => 'bc-location-id',
            ]],
        ];
        Http::fake([
            'https://bc.test/BC240/api/v2.0/companies(company-id)/purchaseInvoices(purchase-invoice-id)/purchaseInvoiceLines*' => Http::response($invoiceLinesResponse),
            'https://bc.test/BC240/api/v2.0/companies(company-id)/purchaseInvoices*' => Http::response($invoiceResponse),
        ]);

        $service = app(BusinessCentralSyncService::class);
        $this->assertSame(1, $service->syncPurchaseInvoices());

        $transaction = Transaction::where('business_id', $business->id)
            ->where('type', 'purchase')
            ->where('ref_no', 'PINV001')
            ->firstOrFail();
        $this->assertSame('received', $transaction->status);
        $this->assertSame('due', $transaction->payment_status);
        $this->assertSame($supplier->id, (int) $transaction->contact_id);
        $this->assertSame($locationId, (int) $transaction->location_id);
        $this->assertSame(1276.0, (float) $transaction->final_total);
        $this->assertDatabaseHas('purchase_lines', [
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'variation_id' => $variation->id,
            'quantity' => 100,
            'purchase_price' => 11,
        ]);
        $this->assertDatabaseHas('business_central_mappings', [
            'business_id' => $business->id,
            'entity_type' => 'purchase_invoice',
            'bc_id' => 'purchase-invoice-id',
            'bc_number' => 'PINV001',
            'status' => 'synced',
        ]);

        AdminSetting::first()->update([
            'business_central_inventory_url' => 'https://bc.test/odata/inventory',
        ]);
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake([
            'https://bc.test/odata/inventory*' => Http::response([
                'value' => [[
                    'itemNumber' => 'ITM00038',
                    'locationCode' => 'HQ',
                    'remainingQuantity' => 100,
                ]],
            ]),
        ]);

        $service = app(BusinessCentralSyncService::class);
        $service->syncInventory();

        $this->assertDatabaseHas('variation_location_details', [
            'product_id' => $product->id,
            'variation_id' => $variation->id,
            'location_id' => $locationId,
            'qty_available' => 100,
        ]);
        $this->assertSame(0, Transaction::where('business_id', $business->id)
            ->where('type', 'opening_stock')
            ->where('additional_notes', 'Business Central inventory bridge')
            ->count());

        // Re-syncing a now-paid invoice settles it without duplicating the purchase.
        $paidInvoiceResponse = $invoiceResponse;
        $paidInvoiceResponse['value'][0]['status'] = 'Paid';
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fake([
            'https://bc.test/BC240/api/v2.0/companies(company-id)/purchaseInvoices*' => Http::response($paidInvoiceResponse),
        ]);
        $this->assertSame(1, $service->syncPurchaseInvoices());

        $this->assertSame(1, Transaction::where('business_id', $business->id)->where('type', 'purchase')->count());
        $this->assertSame('paid', $transaction->fresh()->payment_status);
        $this->assertDatabaseHas('transaction_payments', [
            'transaction_id' => $transaction->id,
            'amount' => 1276,
            'note' => 'Paid in Business Central',
        ]);
    }

    public function test_item_sync_imports_bc_units_for_the_pos_selector(): void
    {
        $business = $this->configureIntegration();
        AdminSetting::first()->update([
            'business_central_inventory_url' => 'https://bc.test/BC240/api/bremac/pos/v1.0/companies({company_id})/posInventoryEntries',
        ]);
        Http::fake([
            'https://bc.test/BC240/api/v2.0/companies(company-id)/items*' => Http::response([
                'value' => [[
                    'id' => 'bc-item-id',
                    'number' => 'ITM00038',
                    'displayName' => 'BC Item',
                    'baseUnitOfMeasureCode' => 'PCS',
                    'unitPrice' => 6.05,
                    'unitCost' => 5.50,
                    'blocked' => false,
                ]],
            ]),
            'https://bc.test/BC240/api/bremac/pos/v1.0/companies(company-id)/posItemUnits*' => Http::response([
                'value' => [
                    [
                        'id' => 'pcs-id',
                        'itemNumber' => 'ITM00038',
                        'code' => 'PCS',
                        'qtyPerUnitOfMeasure' => 1,
                    ],
                    [
                        'id' => 'whl-id',
                        'itemNumber' => 'ITM00038',
                        'code' => 'WHL',
                        'qtyPerUnitOfMeasure' => 100,
                    ],
                ],
            ]),
        ]);

        $this->assertSame(1, app(BusinessCentralSyncService::class)->syncItems());

        $product = Product::where('business_id', $business->id)->where('sku', 'ITM00038')->firstOrFail();
        $this->assertFalse($product->is_inactive);
        $wholesaleUnit = Unit::where('business_id', $business->id)->where('short_name', 'WHL')->firstOrFail();
        $this->assertDatabaseHas('product_unit_conversions', [
            'business_id' => $business->id,
            'product_id' => $product->id,
            'unit_id' => $wholesaleUnit->id,
            'qty_per_base' => 100,
        ]);
        $this->assertSame(
            100.0,
            ProductUnitConversion::where('product_id', $product->id)
                ->where('unit_id', $wholesaleUnit->id)
                ->value('qty_per_base')
        );
        $subUnits = app(\App\Utils\ProductUtil::class)->getSubUnits(
            $business->id,
            $product->unit_id,
            false,
            $product->id
        );
        $this->assertArrayHasKey($wholesaleUnit->id, $subUnits);
        $this->assertSame(100.0, $subUnits[$wholesaleUnit->id]['multiplier']);
    }

    protected function configureIntegration(): Business
    {
        $owner = User::factory()->create();
        $currencyId = DB::table('currencies')->insertGetId([
            'country' => 'Kenya',
            'currency' => 'Kenyan Shilling',
            'code' => 'KES',
            'symbol' => 'KSh',
            'thousand_separator' => ',',
            'decimal_separator' => '.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $business = Business::create([
            'name' => 'BC Test Business',
            'currency_id' => $currencyId,
            'tax_number_1' => 'P000000000A',
            'tax_label_1' => 'PIN',
            'owner_id' => $owner->id,
            'stop_selling_before' => 0,
            'weighing_scale_setting' => '{}',
        ]);
        $owner->update(['business_id' => $business->id]);

        AdminSetting::first()->update([
            'business_central_enabled' => true,
            'business_central_business_id' => $business->id,
            'business_central_api_url' => 'https://bc.test/BC240/api/v2.0',
            'business_central_company_id' => 'company-id',
            'business_central_auth_type' => 'basic',
            'business_central_username' => 'pos-user',
            'business_central_secret' => 'secret',
        ]);

        return $business;
    }
}
