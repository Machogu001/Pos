<?php

namespace App\Services;

use App\AdminSetting;
use App\BusinessCentralMapping;
use App\BusinessCentralLocationMapping;
use App\BusinessCentralSalesperson;
use App\Contact;
use App\Product;
use App\ProductUnitConversion;
use App\PurchaseLine;
use App\Transaction;
use App\TransactionPayment;
use App\TransactionSellLine;
use App\Unit;
use App\User;
use App\VariationLocationDetails;
use App\Utils\ProductUtil;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class BusinessCentralSyncService
{
    protected const INVENTORY_BRIDGE_NOTE = 'Business Central inventory bridge';

    protected const WALK_IN_CUSTOMER_ID = 'CO0001';

    protected ?AdminSetting $settings;

    public function __construct(protected ProductUtil $productUtil)
    {
        $this->settings = AdminSetting::first();
    }

    public function enabledFor(?int $businessId = null): bool
    {
        return ! empty($this->settings)
            && (bool) $this->settings->business_central_enabled
            && ($businessId === null || (int) $this->settings->business_central_business_id === $businessId);
    }

    public function requiresSuccessfulSaleSyncFor(?int $businessId = null): bool
    {
        return $this->enabledFor($businessId)
            && (bool) $this->settings->business_central_require_sale_sync;
    }

    public function syncAll(): array
    {
        if (! $this->enabledFor()) {
            if (! empty($this->settings?->business_central_business_id)) {
                $this->ensureDefaultWalkInCustomer((int) $this->settings->business_central_business_id);
            }

            return [
                'enabled' => false,
                'items' => 0,
                'customers' => 0,
                'vendors' => 0,
                'purchases' => 0,
                'salespeople' => 0,
                'inventory' => 0,
                'sales' => 0,
            ];
        }

        try {
            $result = [
                'enabled' => true,
                'items' => $this->syncItems(),
                'customers' => $this->syncCustomers(),
                'vendors' => $this->syncVendors(),
                'purchases' => $this->syncPurchaseInvoices(),
                'salespeople' => $this->syncSalespeople(),
                'inventory' => $this->syncInventory(),
                'sales' => $this->syncPendingSales(),
            ];
            $this->settings->update([
                'business_central_last_sync_at' => now(),
                'business_central_last_error' => null,
            ]);

            return $result;
        } catch (Throwable $e) {
            $this->recordFailure($e);
            throw $e;
        }
    }

    public function syncSale(Transaction $transaction): bool
    {
        if (! $this->enabledFor((int) $transaction->business_id)
            || $transaction->type !== 'sell'
            || $transaction->status !== 'final'
            || $transaction->sub_type === 'subscription_invoice') {
            return false;
        }

        $mapping = $this->mapping('sale', $transaction->id);
        if ($mapping->status === 'synced') {
            return true;
        }

        try {
            $locationMapping = BusinessCentralLocationMapping::where('business_id', $transaction->business_id)
                ->where('pos_location_id', $transaction->location_id)
                ->where('enabled', true)
                ->first();
            if (! $locationMapping) {
                throw new RuntimeException('The POS sale location is not mapped to a Business Central location.');
            }
            $bcLocationId = $this->resolveBcLocationId($locationMapping);

            $transaction->loadMissing(['contact', 'sell_lines.product.unit', 'sell_lines.sub_unit', 'sell_lines.variations']);
            $customerMapping = BusinessCentralMapping::where('business_id', $transaction->business_id)
                ->where('entity_type', 'customer')
                ->where('local_id', $transaction->contact_id)
                ->where('status', 'synced')
                ->first();
            $customerNumber = trim((string) ($customerMapping->bc_number ?? optional($transaction->contact)->custom_field1));
            if ($customerNumber === '') {
                throw new RuntimeException('The sale customer is not synchronized with Business Central.');
            }

            $salesperson = BusinessCentralSalesperson::where('business_id', $transaction->business_id)
                ->where('pos_user_id', $transaction->created_by)
                ->where('available_in_bc', true)
                ->where('blocked', false)
                ->first();
            if (! $salesperson) {
                $userName = optional(User::find($transaction->created_by))->user_full_name
                    ?: 'user ID '.$transaction->created_by;
                throw new RuntimeException(
                    'POS user '.$userName.' is not mapped to an active Business Central salesperson.'
                );
            }

            $client = $this->client();
            $externalNumber = str_replace("'", "''", (string) $transaction->invoice_no);
            $invoiceDate = Carbon::parse($transaction->transaction_date)->format('Y-m-d');
            $linePayloads = [];
            foreach ($transaction->sell_lines as $line) {
                $linePayloads[] = $this->saleLinePayload($line, (int) $transaction->business_id);
            }

            if ($client->hasSalesCodeunit()) {
                $posted = $client->postSale([
                    'externalDocumentNumber' => (string) $transaction->invoice_no,
                    'customerNumber' => $customerNumber,
                    'postingDate' => $invoiceDate,
                    'salespersonCode' => $salesperson->code,
                    'locationCode' => $locationMapping->bc_location_code,
                    'lines' => $linePayloads,
                ]);
                $mapping->update([
                    'bc_id' => $posted['id'] ?? null,
                    'bc_number' => $posted['number'] ?? null,
                    'status' => 'synced',
                    'last_error' => null,
                    'last_synced_at' => now(),
                ]);

                return true;
            }

            $existingInvoices = $client->getCollection('salesInvoices', [
                '$filter' => "externalDocumentNumber eq '".$externalNumber."'",
                '$select' => 'id,number,externalDocumentNumber,status',
                '$top' => 1,
            ]);
            if (! empty($existingInvoices)) {
                $existingInvoice = $existingInvoices[0];
                if (strtolower((string) ($existingInvoice['status'] ?? '')) !== 'draft') {
                    $mapping->update([
                        'bc_id' => $existingInvoice['id'] ?? $mapping->bc_id,
                        'bc_number' => $existingInvoice['number'] ?? $mapping->bc_number,
                        'status' => 'synced',
                        'last_error' => null,
                        'last_synced_at' => now(),
                    ]);

                    return true;
                }

                $mapping->update([
                    'bc_id' => $existingInvoice['id'] ?? null,
                    'bc_number' => $existingInvoice['number'] ?? null,
                ]);
            }

            if (! empty($mapping->bc_id)) {
                if (! $client->delete('salesInvoices('.rawurlencode($mapping->bc_id).')')) {
                    throw new RuntimeException('The previous Business Central invoice could not be reconciled. Check BC before retrying to avoid a duplicate.');
                }
                $mapping->update(['bc_id' => null, 'bc_number' => null]);
            }

            $invoice = $client->post('salesInvoices', [
                'customerNumber' => $customerNumber,
                'invoiceDate' => $invoiceDate,
                'postingDate' => $invoiceDate,
                'externalDocumentNumber' => (string) $transaction->invoice_no,
                'salesperson' => $salesperson->code,
            ]);
            $mapping->update([
                'bc_id' => $invoice['id'] ?? null,
                'bc_number' => $invoice['number'] ?? null,
                'status' => 'pending',
                'last_error' => null,
            ]);

            foreach ($linePayloads as $linePayload) {
                $linePayload['locationId'] = $bcLocationId;
                $client->post(
                    'salesInvoices('.rawurlencode($invoice['id']).')/salesInvoiceLines',
                    $linePayload
                );
            }

            $posted = $client->postAbsolute(
                $client->resourceUrl('salesInvoices('.rawurlencode($invoice['id']).')/Microsoft.NAV.post')
            );
            $mapping->update([
                'bc_id' => $posted['id'] ?? $mapping->bc_id,
                'bc_number' => $posted['number'] ?? $mapping->bc_number,
                'status' => 'synced',
                'last_error' => null,
                'last_synced_at' => now(),
            ]);

            return true;
        } catch (Throwable $e) {
            if (! empty($mapping->bc_id)) {
                try {
                    if ($this->client()->delete('salesInvoices('.rawurlencode($mapping->bc_id).')')) {
                        $mapping->bc_id = null;
                        $mapping->bc_number = null;
                    }
                } catch (Throwable $cleanupError) {
                    Log::warning('Business Central draft invoice cleanup failed.', [
                        'transaction_id' => $transaction->id,
                        'bc_id' => $mapping->bc_id,
                        'error' => $cleanupError->getMessage(),
                    ]);
                }
            }
            $mapping->update(['status' => 'failed', 'last_error' => mb_substr($e->getMessage(), 0, 65535)]);
            $this->recordFailure($e);
            throw $e;
        }
    }

    public function syncPendingSales(): int
    {
        $businessId = (int) $this->settings->business_central_business_id;
        $retryableIds = BusinessCentralMapping::where('business_id', $businessId)
            ->where('entity_type', 'sale')
            ->whereIn('status', ['pending', 'failed'])
            ->pluck('local_id');

        $count = 0;
        Transaction::where('business_id', $businessId)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->where(function ($query) {
                $query->whereNull('sub_type')->orWhere('sub_type', '!=', 'subscription_invoice');
            })
            ->whereIn('id', $retryableIds)
            ->orderBy('id')
            ->chunkById(50, function ($transactions) use (&$count) {
                foreach ($transactions as $transaction) {
                    try {
                        if ($this->syncSale($transaction)) {
                            $count++;
                        }
                    } catch (Throwable $e) {
                        Log::error('Business Central pending sale sync failed.', [
                            'transaction_id' => $transaction->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return $count;
    }

    public function syncItems(): int
    {
        $items = $this->client()->getCollection('items', [
            '$select' => 'id,number,displayName,baseUnitOfMeasureCode,unitPrice,unitCost,blocked,lastModifiedDateTime',
        ]);
        $itemUnits = collect($this->client()->itemUnits())->groupBy(function ($row) {
            return trim((string) ($row['itemNumber'] ?? ''));
        });
        $businessId = (int) $this->settings->business_central_business_id;
        $locationIds = BusinessCentralLocationMapping::where('business_id', $businessId)
            ->where('enabled', true)
            ->pluck('pos_location_id')
            ->all();
        $creatorId = $this->creatorId($businessId);
        $count = 0;

        DB::transaction(function () use ($items, $itemUnits, $businessId, $locationIds, $creatorId, &$count) {
            foreach ($items as $item) {
                $number = trim((string) ($item['number'] ?? ''));
                if ($number === '') {
                    continue;
                }

                $unitCode = trim((string) ($item['baseUnitOfMeasureCode'] ?? 'PCS')) ?: 'PCS';
                $unit = Unit::firstOrCreate(
                    ['business_id' => $businessId, 'short_name' => $unitCode],
                    ['actual_name' => $unitCode, 'allow_decimal' => true, 'created_by' => $creatorId]
                );
                $product = Product::where('business_id', $businessId)->where('sku', $number)->first();
                if (! $product) {
                    $product = Product::create([
                        'name' => $item['displayName'] ?: $number,
                        'business_id' => $businessId,
                        'type' => 'single',
                        'unit_id' => $unit->id,
                        'tax_type' => 'exclusive',
                        'enable_stock' => true,
                        'sku' => $number,
                        'barcode_type' => 'C128',
                        'created_by' => $creatorId,
                        'is_inactive' => $this->isBlocked($item['blocked'] ?? false),
                    ]);
                    $this->productUtil->createSingleProductVariation(
                        $product,
                        $number,
                        $item['unitCost'] ?? 0,
                        $item['unitCost'] ?? 0,
                        0,
                        $item['unitPrice'] ?? 0,
                        $item['unitPrice'] ?? 0
                    );
                } else {
                    if ($product->type !== 'single') {
                        Log::warning('Skipped BC item update because its POS SKU belongs to a variable product.', [
                            'product_id' => $product->id,
                            'sku' => $number,
                        ]);
                        continue;
                    }
                    $product->update([
                        'name' => $item['displayName'] ?: $number,
                        'unit_id' => $unit->id,
                        'is_inactive' => $this->isBlocked($item['blocked'] ?? false),
                        'enable_stock' => true,
                    ]);
                    $product->variations()->update([
                        'sub_sku' => $number,
                        'default_purchase_price' => (float) ($item['unitCost'] ?? 0),
                        'dpp_inc_tax' => (float) ($item['unitCost'] ?? 0),
                        'default_sell_price' => (float) ($item['unitPrice'] ?? 0),
                        'sell_price_inc_tax' => (float) ($item['unitPrice'] ?? 0),
                    ]);
                }
                $this->syncProductUnits(
                    $product,
                    $unit,
                    $itemUnits->get($number, collect()),
                    $businessId,
                    $creatorId
                );
                $product->product_locations()->syncWithoutDetaching($locationIds);
                $this->markSynced('product', $product->id, $item['id'] ?? null, $number);
                $count++;
            }
        });

        return $count;
    }

    protected function syncProductUnits(
        Product $product,
        Unit $baseUnit,
        $itemUnits,
        int $businessId,
        int $creatorId
    ): void {
        $syncedUnitIds = [];
        foreach ($itemUnits as $itemUnit) {
            $code = trim((string) ($itemUnit['code'] ?? ''));
            $multiplier = (float) ($itemUnit['qtyPerUnitOfMeasure'] ?? 0);
            if ($code === '' || $multiplier <= 0) {
                continue;
            }

            $unit = Unit::firstOrCreate(
                ['business_id' => $businessId, 'short_name' => $code],
                ['actual_name' => $code, 'allow_decimal' => true, 'created_by' => $creatorId]
            );
            ProductUnitConversion::updateOrCreate(
                [
                    'business_id' => $businessId,
                    'product_id' => $product->id,
                    'unit_id' => $unit->id,
                ],
                [
                    'qty_per_base' => $multiplier,
                    'is_purchase_default' => $unit->id === $baseUnit->id,
                    'is_sale_default' => $unit->id === $baseUnit->id,
                ]
            );
            $syncedUnitIds[] = $unit->id;
        }

        if (! in_array($baseUnit->id, $syncedUnitIds, true)) {
            ProductUnitConversion::updateOrCreate(
                [
                    'business_id' => $businessId,
                    'product_id' => $product->id,
                    'unit_id' => $baseUnit->id,
                ],
                [
                    'qty_per_base' => 1,
                    'is_purchase_default' => true,
                    'is_sale_default' => true,
                ]
            );
            $syncedUnitIds[] = $baseUnit->id;
        }

        ProductUnitConversion::where('business_id', $businessId)
            ->where('product_id', $product->id)
            ->whereNotIn('unit_id', $syncedUnitIds)
            ->delete();
        $product->update(['sub_unit_ids' => array_values($syncedUnitIds)]);
    }

    public function syncCustomers(): int
    {
        $customers = $this->client()->getCollection('customers', [
            '$select' => 'id,number,displayName,addressLine1,addressLine2,city,state,country,postalCode,phoneNumber,email,taxRegistrationNumber,creditLimit,paymentTermsId,balanceDue,blocked',
        ]);
        $paymentTerms = $this->paymentTermsById();
        $businessId = (int) $this->settings->business_central_business_id;
        $creatorId = $this->creatorId($businessId);
        $count = 0;

        foreach ($customers as $customer) {
            $number = trim((string) ($customer['number'] ?? ''));
            if ($number === '') {
                continue;
            }

            $mapping = BusinessCentralMapping::where('business_id', $businessId)
                ->where('entity_type', 'customer')
                ->where(function ($query) use ($customer, $number) {
                    $query->where('bc_number', $number);
                    if (! empty($customer['id'])) {
                        $query->orWhere('bc_id', $customer['id']);
                    }
                })
                ->first();
            $contact = $mapping
                ? Contact::withTrashed()->where('is_default', 0)->find($mapping->local_id)
                : null;
            if (! $contact) {
                $contact = Contact::withTrashed()
                    ->where('business_id', $businessId)
                    ->where('custom_field1', $number)
                    ->where('is_default', 0)
                    ->first();
            }
            $contact = $contact ?: new Contact([
                'business_id' => $businessId,
                'custom_field1' => $number,
            ]);
            $contact->fill([
                'type' => $contact->type === 'supplier' ? 'both' : ($contact->type ?: 'customer'),
                'name' => $customer['displayName'] ?: $number,
                'contact_id' => $number,
                'tax_number' => $customer['taxRegistrationNumber'] ?? null,
                'address_line_1' => $customer['addressLine1'] ?? null,
                'address_line_2' => $customer['addressLine2'] ?? null,
                'city' => $customer['city'] ?? null,
                'state' => $customer['state'] ?? null,
                'country' => $customer['country'] ?? null,
                'zip_code' => $customer['postalCode'] ?? null,
                'mobile' => $customer['phoneNumber'] ?: '-',
                'email' => $customer['email'] ?? null,
                'credit_limit' => isset($customer['creditLimit']) && is_numeric($customer['creditLimit'])
                    ? (float) $customer['creditLimit']
                    : $contact->credit_limit,
                'bc_balance_due' => isset($customer['balanceDue']) && is_numeric($customer['balanceDue'])
                    ? (float) $customer['balanceDue']
                    : null,
                'contact_status' => $this->isBlocked($customer['blocked'] ?? false) ? 'inactive' : 'active',
                'created_by' => $contact->created_by ?: $creatorId,
            ]);
            $payTerm = $paymentTerms[(string) ($customer['paymentTermsId'] ?? '')] ?? null;
            if ($payTerm !== null) {
                $contact->pay_term_number = $payTerm['number'];
                $contact->pay_term_type = $payTerm['type'];
            }
            $contact->deleted_at = null;
            $contact->save();
            if ($mapping && (int) $mapping->local_id !== (int) $contact->id) {
                $mapping->update([
                    'local_id' => $contact->id,
                    'bc_id' => $customer['id'] ?? null,
                    'bc_number' => $number,
                    'status' => 'synced',
                    'last_error' => null,
                    'last_synced_at' => now(),
                ]);
            } else {
                $this->markSynced('customer', $contact->id, $customer['id'] ?? null, $number);
            }
            $count++;
        }

        return $count;
    }

    public function ensureDefaultWalkInCustomer(int $businessId): Contact
    {
        return DB::transaction(function () use ($businessId) {
            $walkInCustomer = Contact::withTrashed()
                ->where('business_id', $businessId)
                ->where('contact_id', self::WALK_IN_CUSTOMER_ID)
                ->whereNotExists(function ($query) use ($businessId) {
                    $query->select(DB::raw(1))
                        ->from('business_central_mappings')
                        ->whereColumn('business_central_mappings.local_id', 'contacts.id')
                        ->where('business_central_mappings.business_id', $businessId)
                        ->where('business_central_mappings.entity_type', 'customer');
                })
                ->first();

            $walkInCustomer = $walkInCustomer ?: new Contact([
                'business_id' => $businessId,
                'contact_id' => self::WALK_IN_CUSTOMER_ID,
            ]);
            $walkInCustomer->fill([
                'type' => 'customer',
                'name' => 'Walk-In Customer',
                'mobile' => '-',
                'contact_status' => 'active',
                'is_default' => 1,
                'credit_limit' => 0,
                'created_by' => $walkInCustomer->created_by ?: $this->creatorId($businessId),
            ]);
            $walkInCustomer->custom_field1 = null;
            $walkInCustomer->bc_balance_due = null;
            $walkInCustomer->deleted_at = null;
            $walkInCustomer->save();

            Contact::where('business_id', $businessId)
                ->whereKeyNot($walkInCustomer->id)
                ->where('is_default', 1)
                ->update(['is_default' => 0]);

            return $walkInCustomer;
        });
    }

    public function syncVendors(): int
    {
        $vendors = $this->client()->getCollection('vendors', [
            '$select' => 'id,number,displayName,addressLine1,addressLine2,city,state,country,postalCode,phoneNumber,email,taxRegistrationNumber,balance,blocked',
        ]);
        $businessId = (int) $this->settings->business_central_business_id;
        $creatorId = $this->creatorId($businessId);
        $count = 0;

        foreach ($vendors as $vendor) {
            $number = trim((string) ($vendor['number'] ?? ''));
            if ($number === '') {
                continue;
            }

            $mapping = BusinessCentralMapping::where('business_id', $businessId)
                ->where('entity_type', 'vendor')
                ->where(function ($query) use ($vendor, $number) {
                    $query->where('bc_number', $number);
                    if (! empty($vendor['id'])) {
                        $query->orWhere('bc_id', $vendor['id']);
                    }
                })
                ->first();
            $contact = $mapping ? Contact::withTrashed()->find($mapping->local_id) : null;
            if (! $contact) {
                $contact = Contact::withTrashed()
                    ->where('business_id', $businessId)
                    ->where('type', 'supplier')
                    ->where('contact_id', $number)
                    ->first();
            }
            $contact = $contact ?: new Contact(['business_id' => $businessId]);
            $contact->fill([
                'type' => $contact->type === 'customer' ? 'both' : ($contact->type ?: 'supplier'),
                'supplier_business_name' => $vendor['displayName'] ?: $number,
                'name' => $vendor['displayName'] ?: $number,
                'contact_id' => $number,
                'tax_number' => $vendor['taxRegistrationNumber'] ?? null,
                'address_line_1' => $vendor['addressLine1'] ?? null,
                'address_line_2' => $vendor['addressLine2'] ?? null,
                'city' => $vendor['city'] ?? null,
                'state' => $vendor['state'] ?? null,
                'country' => $vendor['country'] ?? null,
                'zip_code' => $vendor['postalCode'] ?? null,
                'mobile' => $vendor['phoneNumber'] ?: '-',
                'email' => $vendor['email'] ?? null,
                'bc_purchase_balance_due' => isset($vendor['balance']) && is_numeric($vendor['balance'])
                    ? (float) $vendor['balance']
                    : null,
                'contact_status' => $this->isBlocked($vendor['blocked'] ?? false) ? 'inactive' : 'active',
                'created_by' => $contact->created_by ?: $creatorId,
            ]);
            $contact->deleted_at = null;
            $contact->save();
            $this->markSynced('vendor', $contact->id, $vendor['id'] ?? null, $number);
            $count++;
        }

        return $count;
    }

    public function syncPurchaseInvoices(): int
    {
        $businessId = (int) $this->settings->business_central_business_id;
        $locationMappings = BusinessCentralLocationMapping::where('business_id', $businessId)
            ->where('enabled', true)
            ->get();
        $defaultLocationId = (int) optional($locationMappings->first())->pos_location_id;
        if (! $defaultLocationId) {
            return 0;
        }

        $invoices = $this->client()->getCollection('purchaseInvoices', [
            '$select' => 'id,number,vendorId,vendorNumber,invoiceDate,postingDate,status,totalAmountExcludingTax,totalAmountIncludingTax,totalTaxAmount',
        ]);
        $creatorId = $this->creatorId($businessId);
        $count = 0;

        foreach ($invoices as $invoice) {
            $status = strtolower(trim((string) ($invoice['status'] ?? '')));
            $number = trim((string) ($invoice['number'] ?? ''));
            $bcId = trim((string) ($invoice['id'] ?? ''));
            if ($number === '' || $bcId === '' || ! in_array($status, ['open', 'paid'], true)) {
                continue;
            }

            $mapping = BusinessCentralMapping::where('business_id', $businessId)
                ->where('entity_type', 'purchase_invoice')
                ->where(function ($query) use ($bcId, $number) {
                    $query->where('bc_id', $bcId)->orWhere('bc_number', $number);
                })
                ->first();
            if ($mapping) {
                $transaction = Transaction::find($mapping->local_id);
                if ($transaction && $status === 'paid' && $transaction->payment_status !== 'paid') {
                    $this->markPurchaseInvoicePaid($transaction, $creatorId);
                    $count++;
                }
                continue;
            }

            $supplier = $this->resolveSupplier($businessId, trim((string) ($invoice['vendorNumber'] ?? '')), trim((string) ($invoice['vendorId'] ?? '')));
            if (! $supplier) {
                Log::warning('Skipped BC purchase invoice because its vendor is not synchronized with POS.', [
                    'bc_invoice_number' => $number,
                ]);
                continue;
            }

            $lines = $this->client()->getCollection(
                'purchaseInvoices('.rawurlencode($bcId).')/purchaseInvoiceLines',
                ['$select' => 'lineType,lineObjectNumber,quantity,unitOfMeasureCode,amountExcludingTax,amountIncludingTax,locationId']
            );

            $locationId = $defaultLocationId;
            foreach ($lines as $line) {
                $lineLocationId = trim((string) ($line['locationId'] ?? ''), '{} ');
                if ($lineLocationId !== '') {
                    $matched = $locationMappings->first(
                        fn ($m) => strcasecmp(trim((string) $m->bc_location_id, '{} '), $lineLocationId) === 0
                    );
                    if ($matched) {
                        $locationId = (int) $matched->pos_location_id;
                        break;
                    }
                }
            }

            $purchaseLineRows = [];
            foreach ($lines as $line) {
                if (strtolower((string) ($line['lineType'] ?? '')) !== 'item') {
                    continue;
                }
                $itemNumber = trim((string) ($line['lineObjectNumber'] ?? ''));
                $quantity = (float) ($line['quantity'] ?? 0);
                if ($itemNumber === '' || $quantity == 0.0) {
                    continue;
                }
                $product = Product::where('business_id', $businessId)
                    ->where('sku', $itemNumber)
                    ->with('variations')
                    ->first();
                $variation = $product?->variations?->first();
                if (! $variation) {
                    Log::warning('Skipped BC purchase invoice line because its item is not synchronized with POS.', [
                        'bc_invoice_number' => $number,
                        'bc_item_number' => $itemNumber,
                    ]);
                    continue;
                }

                $factor = $this->unitFactor($businessId, $product, trim((string) ($line['unitOfMeasureCode'] ?? '')));
                $baseQuantity = $quantity * $factor;
                $priceExc = (float) ($line['amountExcludingTax'] ?? 0) / $baseQuantity;
                $priceInc = (float) ($line['amountIncludingTax'] ?? 0) / $baseQuantity;
                $purchaseLineRows[] = [
                    'product_id' => $product->id,
                    'variation_id' => $variation->id,
                    'location_id' => $locationId,
                    'quantity' => $baseQuantity,
                    'quantity_remaining' => $baseQuantity,
                    'pp_without_discount' => $priceExc,
                    'purchase_price' => $priceExc,
                    'purchase_price_inc_tax' => $priceInc,
                    'item_tax' => max($priceInc - $priceExc, 0),
                ];
            }

            DB::transaction(function () use (
                $invoice,
                $status,
                $number,
                $bcId,
                $businessId,
                $locationId,
                $supplier,
                $purchaseLineRows,
                $creatorId,
                &$count
            ) {
                $transaction = Transaction::create([
                    'business_id' => $businessId,
                    'location_id' => $locationId,
                    'type' => 'purchase',
                    'status' => 'received',
                    'payment_status' => 'due',
                    'contact_id' => $supplier->id,
                    'ref_no' => $number,
                    'transaction_date' => $invoice['postingDate'] ?: ($invoice['invoiceDate'] ?: now()),
                    'total_before_tax' => (float) ($invoice['totalAmountExcludingTax'] ?? 0),
                    'tax_amount' => (float) ($invoice['totalTaxAmount'] ?? 0),
                    'final_total' => (float) ($invoice['totalAmountIncludingTax'] ?? 0),
                    'exchange_rate' => 1,
                    'additional_notes' => 'Imported from Business Central',
                    'created_by' => $creatorId,
                ]);
                foreach ($purchaseLineRows as $row) {
                    $transaction->purchase_lines()->create($row);
                }
                if ($status === 'paid') {
                    $this->markPurchaseInvoicePaid($transaction, $creatorId);
                }
                $this->markSynced('purchase_invoice', $transaction->id, $bcId, $number);
                $count++;
            });
        }

        return $count;
    }

    protected function resolveSupplier(int $businessId, string $vendorNumber, string $vendorId): ?Contact
    {
        $mapping = BusinessCentralMapping::where('business_id', $businessId)
            ->where('entity_type', 'vendor')
            ->where(function ($query) use ($vendorNumber, $vendorId) {
                $query->where('bc_number', $vendorNumber);
                if ($vendorId !== '') {
                    $query->orWhere('bc_id', $vendorId);
                }
            })
            ->first();
        $supplier = $mapping ? Contact::find($mapping->local_id) : null;
        if (! $supplier && $vendorNumber !== '') {
            $supplier = Contact::where('business_id', $businessId)
                ->whereIn('type', ['supplier', 'both'])
                ->where('contact_id', $vendorNumber)
                ->first();
        }

        return $supplier;
    }

    protected function unitFactor(int $businessId, Product $product, string $unitCode): float
    {
        if ($unitCode === '') {
            return 1.0;
        }
        $unit = Unit::where('business_id', $businessId)
            ->whereRaw('LOWER(short_name) = ?', [strtolower($unitCode)])
            ->first();
        if (! $unit || (int) $unit->id === (int) $product->unit_id) {
            return 1.0;
        }
        $factor = ProductUnitConversion::where('business_id', $businessId)
            ->where('product_id', $product->id)
            ->where('unit_id', $unit->id)
            ->value('qty_per_base');

        return $factor > 0 ? (float) $factor : 1.0;
    }

    protected function markPurchaseInvoicePaid(Transaction $transaction, int $creatorId): void
    {
        $paid = (float) TransactionPayment::where('transaction_id', $transaction->id)->sum('amount');
        $remaining = (float) $transaction->final_total - $paid;
        if ($remaining > 0.0001) {
            TransactionPayment::create([
                'transaction_id' => $transaction->id,
                'business_id' => $transaction->business_id,
                'amount' => $remaining,
                'method' => 'bank_transfer',
                'note' => 'Paid in Business Central',
                'paid_on' => now(),
                'created_by' => $creatorId,
                'payment_for' => $transaction->contact_id,
            ]);
        }
        $transaction->update(['payment_status' => 'paid']);
    }

    public function syncSalespeople(): int
    {
        $rows = $this->client()->salespeople();
        $businessId = (int) $this->settings->business_central_business_id;
        $seenIds = [];
        $count = 0;

        foreach ($rows as $row) {
            $code = trim((string) ($row['code'] ?? $row['Code'] ?? ''));
            $bcId = trim((string) ($row['id'] ?? $row['SystemId'] ?? $code));
            if ($bcId === '' || $code === '') {
                continue;
            }

            $salesperson = BusinessCentralSalesperson::where('business_id', $businessId)
                ->where(function ($query) use ($bcId, $code) {
                    $query->where('bc_id', $bcId)->orWhere('code', $code);
                })
                ->first() ?: new BusinessCentralSalesperson(['business_id' => $businessId]);
            $salesperson->fill([
                'bc_id' => $bcId,
                'code' => $code,
                'display_name' => $row['displayName'] ?? $row['Name'] ?? $code,
                'email' => $row['email'] ?? $row['E_Mail'] ?? null,
                'phone_number' => $row['phoneNo'] ?? $row['Phone_No'] ?? null,
                'blocked' => $this->isBlocked($row['blocked'] ?? $row['Privacy_Blocked'] ?? false),
                'available_in_bc' => true,
                'last_synced_at' => now(),
            ]);
            $salesperson->save();
            $seenIds[] = $salesperson->id;
            $count++;
        }

        BusinessCentralSalesperson::where('business_id', $businessId)
            ->when(! empty($seenIds), fn ($query) => $query->whereNotIn('id', $seenIds))
            ->update(['available_in_bc' => false]);

        return $count;
    }

    public function syncInventory(): int
    {
        $businessId = (int) $this->settings->business_central_business_id;
        $count = 0;
        $locationMappings = BusinessCentralLocationMapping::where('business_id', $businessId)
            ->where('enabled', true)
            ->get();

        $creatorId = $this->creatorId($businessId);

        foreach ($locationMappings as $locationMapping) {
            try {
                $this->resolveBcLocationId($locationMapping);
                $rows = $this->client()->inventory($locationMapping->bc_location_code);
                $inventoryByItem = [];
                foreach ($rows as $row) {
                    $number = $row['itemNumber'] ?? $row['number'] ?? null;
                    if (empty($number)) {
                        continue;
                    }
                    $inventoryByItem[$number] = ($inventoryByItem[$number] ?? 0)
                        + (float) ($row['remainingQuantity'] ?? $row['inventory'] ?? 0);
                }

                DB::transaction(function () use ($inventoryByItem, $businessId, $creatorId, $locationMapping, &$count) {
                    $mappedProductIds = BusinessCentralMapping::where('business_id', $businessId)
                        ->where('entity_type', 'product')
                        ->where('status', 'synced')
                        ->pluck('local_id');
                    $products = Product::where('business_id', $businessId)
                        ->whereIn('id', $mappedProductIds)
                        ->with('variations')
                        ->get();

                    foreach ($products as $product) {
                        $variation = $product->variations->first();
                        if (! $variation) {
                            continue;
                        }
                        $hasInventoryRow = array_key_exists($product->sku, $inventoryByItem);
                        $inventory = (float) ($inventoryByItem[$product->sku] ?? 0);

                        VariationLocationDetails::updateOrCreate(
                            [
                                'product_id' => $product->id,
                                'variation_id' => $variation->id,
                                'product_variation_id' => $variation->product_variation_id,
                                'location_id' => $locationMapping->pos_location_id,
                            ],
                            ['qty_available' => $inventory]
                        );
                        $this->ensureInventoryPurchaseLayer(
                            $product,
                            $variation,
                            (int) $locationMapping->pos_location_id,
                            $inventory,
                            $creatorId
                        );
                        if ($hasInventoryRow) {
                            $count++;
                        }
                    }
                });
                $locationMapping->update([
                    'last_inventory_sync_at' => now(),
                    'last_error' => null,
                ]);
            } catch (Throwable $e) {
                $locationMapping->update(['last_error' => mb_substr($e->getMessage(), 0, 65535)]);
                Log::error('Business Central location inventory sync failed.', [
                    'pos_location_id' => $locationMapping->pos_location_id,
                    'bc_location_code' => $locationMapping->bc_location_code,
                    'error' => $e->getMessage(),
                ]);
                if ($locationMappings->count() === 1) {
                    throw $e;
                }
            }
        }

        return $count;
    }

    protected function ensureInventoryPurchaseLayer(
        Product $product,
        $variation,
        int $locationId,
        float $inventory,
        int $creatorId
    ): void {
        $bridgeTransaction = Transaction::where('business_id', $product->business_id)
            ->where('location_id', $locationId)
            ->where('type', 'opening_stock')
            ->where('opening_stock_product_id', $product->id)
            ->where('additional_notes', self::INVENTORY_BRIDGE_NOTE)
            ->with(['purchase_lines' => fn ($query) => $query->where('variation_id', $variation->id)])
            ->first();

        $otherRemaining = Transaction::join('purchase_lines as PL', 'transactions.id', '=', 'PL.transaction_id')
            ->where('transactions.business_id', $product->business_id)
            ->where('transactions.location_id', $locationId)
            ->where('transactions.status', 'received')
            ->whereIn('transactions.type', [
                'purchase',
                'purchase_transfer',
                'opening_stock',
                'production_purchase',
                'stocktake_adjustment',
            ])
            ->where('PL.product_id', $product->id)
            ->where('PL.variation_id', $variation->id)
            ->when($bridgeTransaction, fn ($query) => $query->where('transactions.id', '!=', $bridgeTransaction->id))
            ->get([
                'PL.quantity',
                'PL.quantity_sold',
                'PL.quantity_adjusted',
                'PL.quantity_returned',
                'PL.mfg_quantity_used',
            ])
            ->sum(fn ($line) => max(
                (float) $line->quantity
                - (float) $line->quantity_sold
                - (float) $line->quantity_adjusted
                - (float) $line->quantity_returned
                - (float) $line->mfg_quantity_used,
                0
            ));

        $bridgeRemaining = max($inventory - $otherRemaining, 0);
        $purchaseLine = $bridgeTransaction?->purchase_lines->first();
        if (! $purchaseLine && $bridgeRemaining <= 0) {
            return;
        }

        $purchasePrice = (float) ($variation->default_purchase_price ?? 0);
        $purchasePriceIncTax = (float) ($variation->dpp_inc_tax ?: $purchasePrice);

        if (! $bridgeTransaction) {
            $bridgeTransaction = Transaction::create([
                'type' => 'opening_stock',
                'opening_stock_product_id' => $product->id,
                'status' => 'received',
                'business_id' => $product->business_id,
                'transaction_date' => now(),
                'additional_notes' => self::INVENTORY_BRIDGE_NOTE,
                'location_id' => $locationId,
                'payment_status' => 'paid',
                'created_by' => $creatorId,
            ]);
        }

        $quantityUsed = $purchaseLine?->quantity_used ?? 0;
        $quantity = $quantityUsed + $bridgeRemaining;
        $lineValues = [
            'product_id' => $product->id,
            'variation_id' => $variation->id,
            'location_id' => $locationId,
            'quantity' => $quantity,
            'quantity_remaining' => $bridgeRemaining,
            'pp_without_discount' => $purchasePrice,
            'purchase_price' => $purchasePrice,
            'purchase_price_inc_tax' => $purchasePriceIncTax,
            'item_tax' => max($purchasePriceIncTax - $purchasePrice, 0),
        ];

        if ($purchaseLine) {
            $purchaseLine->update($lineValues);
        } else {
            $purchaseLine = $bridgeTransaction->purchase_lines()->create($lineValues);
        }

        $total = $purchasePriceIncTax * $quantity;
        $bridgeTransaction->update([
            'total_before_tax' => $total,
            'final_total' => $total,
        ]);
    }

    protected function resolveBcLocationId(BusinessCentralLocationMapping $mapping): string
    {
        if (! empty($mapping->bc_location_id)) {
            return $mapping->bc_location_id;
        }

        $code = str_replace("'", "''", $mapping->bc_location_code);
        $locations = $this->client()->getCollection('locations', [
            '$filter' => "code eq '".$code."'",
            '$select' => 'id,code,displayName',
            '$top' => 1,
        ]);
        if (empty($locations[0]['id'])) {
            throw new RuntimeException('Business Central location '.$mapping->bc_location_code.' was not found.');
        }

        $mapping->update([
            'bc_location_id' => $locations[0]['id'],
            'last_error' => null,
        ]);

        return $locations[0]['id'];
    }

    protected function client(): BusinessCentralClient
    {
        return new BusinessCentralClient($this->settings);
    }

    protected function saleLinePayload(TransactionSellLine $line, int $businessId): array
    {
        $itemNumber = trim((string) ($line->variations->sub_sku ?? $line->product->sku ?? ''));
        if ($itemNumber === '') {
            throw new RuntimeException('A sale line has no Business Central item number.');
        }

        $selectedUnit = $line->sub_unit ?: $line->product->unit;
        $unitOfMeasureCode = trim((string) optional($selectedUnit)->short_name);
        if ($unitOfMeasureCode === '') {
            throw new RuntimeException('POS item '.$itemNumber.' has no unit of measure.');
        }

        $multiplier = (float) $this->productUtil->getProductUnitMultiplier(
            $businessId,
            $line->product_id,
            $selectedUnit->id,
            $line->product->unit_id
        );
        if ($multiplier <= 0) {
            throw new RuntimeException('POS item '.$itemNumber.' has an invalid unit conversion.');
        }

        $payload = [
            'lineType' => 'Item',
            'lineObjectNumber' => $itemNumber,
            'unitOfMeasureCode' => $unitOfMeasureCode,
            'quantity' => (float) $line->quantity / $multiplier,
            'unitPrice' => (float) $line->unit_price_before_discount * $multiplier,
        ];
        if ($line->line_discount_type === 'percentage') {
            $payload['discountPercent'] = (float) $line->line_discount_amount;
        } elseif ($line->line_discount_type === 'fixed') {
            $payload['discountAmount'] = round(
                (float) $line->line_discount_amount * (float) $line->quantity,
                2
            );
        }

        return $payload;
    }

    protected function mapping(string $type, int $localId): BusinessCentralMapping
    {
        return BusinessCentralMapping::firstOrCreate([
            'business_id' => (int) $this->settings->business_central_business_id,
            'entity_type' => $type,
            'local_id' => $localId,
        ]);
    }

    public function queueSale(Transaction $transaction): void
    {
        if ($this->enabledFor((int) $transaction->business_id)
            && $transaction->type === 'sell'
            && $transaction->status === 'final'
            && $transaction->sub_type !== 'subscription_invoice') {
            $this->mapping('sale', $transaction->id);
        }
    }

    protected function markSynced(string $type, int $localId, ?string $bcId, string $number): void
    {
        $this->mapping($type, $localId)->update([
            'bc_id' => $bcId,
            'bc_number' => $number,
            'status' => 'synced',
            'last_error' => null,
            'last_synced_at' => now(),
        ]);
    }

    protected function creatorId(int $businessId): int
    {
        $id = User::where('business_id', $businessId)->orderBy('id')->value('id');
        if (! $id) {
            throw new RuntimeException('The selected POS business has no user to own synchronized records.');
        }

        return (int) $id;
    }

    /**
     * Returns BC payment terms keyed by GUID as POS pay term number/type pairs.
     */
    protected function paymentTermsById(): array
    {
        try {
            $rows = $this->client()->getCollection('paymentTerms', [
                '$select' => 'id,code,dueDateCalculation',
            ]);
        } catch (Throwable $e) {
            Log::warning('Business Central payment terms could not be loaded; customer pay terms were skipped.', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        $terms = [];
        foreach ($rows as $row) {
            $id = (string) ($row['id'] ?? '');
            $parsed = $this->parseDueDateCalculation((string) ($row['dueDateCalculation'] ?? ''));
            if ($id !== '' && $parsed !== null) {
                $terms[$id] = $parsed;
            }
        }

        return $terms;
    }

    /**
     * Parses a BC due-date formula such as 30D or 1M(8D) into POS pay term parts.
     */
    protected function parseDueDateCalculation(string $formula): ?array
    {
        if (preg_match('/(\d+)\s*M/i', $formula, $matches)) {
            return ['number' => (int) $matches[1], 'type' => 'months'];
        }
        if (preg_match('/(\d+)\s*D/i', $formula, $matches)) {
            return ['number' => (int) $matches[1], 'type' => 'days'];
        }

        return null;
    }

    protected function isBlocked($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return $value != 0;
        }

        $value = strtolower(trim((string) $value));

        return ! in_array($value, [
            '',
            '0',
            'false',
            'no',
            'none',
            'unblocked',
            '_x0020_',
        ], true);
    }

    protected function recordFailure(Throwable $e): void
    {
        Log::error('Business Central synchronization failed', ['error' => $e->getMessage()]);
        $this->settings?->update([
            'business_central_last_failed_at' => now(),
            'business_central_last_error' => mb_substr($e->getMessage(), 0, 65535),
        ]);
    }
}
