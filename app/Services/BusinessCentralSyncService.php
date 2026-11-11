<?php

namespace App\Services;

use App\AdminSetting;
use App\BusinessCentralMapping;
use App\BusinessCentralLocationMapping;
use App\BusinessCentralSalesperson;
use App\Contact;
use App\Product;
use App\Transaction;
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

    public function syncAll(): array
    {
        if (! $this->enabledFor()) {
            return [
                'enabled' => false,
                'items' => 0,
                'customers' => 0,
                'vendors' => 0,
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

            $transaction->loadMissing(['contact', 'sell_lines.product', 'sell_lines.variations']);
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
            $postedInvoices = $client->getCollection('postedSalesInvoices', [
                '$filter' => "externalDocumentNumber eq '".$externalNumber."'",
                '$select' => 'id,number,externalDocumentNumber',
                '$top' => 1,
            ]);
            if (! empty($postedInvoices)) {
                $posted = $postedInvoices[0];
                $mapping->update([
                    'bc_id' => $posted['id'] ?? $mapping->bc_id,
                    'bc_number' => $posted['number'] ?? $mapping->bc_number,
                    'status' => 'synced',
                    'last_error' => null,
                    'last_synced_at' => now(),
                ]);

                return true;
            }

            if (! empty($mapping->bc_id)) {
                if (! $client->delete('salesInvoices('.rawurlencode($mapping->bc_id).')')) {
                    throw new RuntimeException('The previous Business Central invoice could not be reconciled. Check BC before retrying to avoid a duplicate.');
                }
                $mapping->update(['bc_id' => null, 'bc_number' => null]);
            }

            $invoiceDate = Carbon::parse($transaction->transaction_date)->format('Y-m-d');
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

            foreach ($transaction->sell_lines as $line) {
                $itemNumber = trim((string) ($line->variations->sub_sku ?? $line->product->sku ?? ''));
                if ($itemNumber === '') {
                    throw new RuntimeException('A sale line has no Business Central item number.');
                }

                $linePayload = [
                    'lineType' => 'Item',
                    'lineObjectNumber' => $itemNumber,
                    'quantity' => (float) $line->quantity,
                    'unitPrice' => (float) $line->unit_price_before_discount,
                    'locationId' => $bcLocationId,
                ];
                if ($line->line_discount_type === 'percentage') {
                    $linePayload['discountPercent'] = (float) $line->line_discount_amount;
                } elseif ($line->line_discount_type === 'fixed') {
                    $linePayload['discountAmount'] = round(
                        (float) $line->line_discount_amount * (float) $line->quantity,
                        2
                    );
                }

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
        $businessId = (int) $this->settings->business_central_business_id;
        $locationIds = BusinessCentralLocationMapping::where('business_id', $businessId)
            ->where('enabled', true)
            ->pluck('pos_location_id')
            ->all();
        $creatorId = $this->creatorId($businessId);
        $count = 0;

        DB::transaction(function () use ($items, $businessId, $locationIds, $creatorId, &$count) {
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
                        'is_inactive' => (bool) ($item['blocked'] ?? false),
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
                $product->product_locations()->syncWithoutDetaching($locationIds);
                $this->markSynced('product', $product->id, $item['id'] ?? null, $number);
                $count++;
            }
        });

        return $count;
    }

    public function syncCustomers(): int
    {
        $customers = $this->client()->getCollection('customers', [
            '$select' => 'id,number,displayName,addressLine1,addressLine2,city,state,country,postalCode,phoneNumber,email,taxRegistrationNumber,blocked',
        ]);
        $businessId = (int) $this->settings->business_central_business_id;
        $creatorId = $this->creatorId($businessId);
        $count = 0;

        foreach ($customers as $customer) {
            $number = trim((string) ($customer['number'] ?? ''));
            if ($number === '') {
                continue;
            }
            $contact = Contact::withTrashed()->firstOrNew([
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
                'contact_status' => ! empty($customer['blocked']) ? 'inactive' : 'active',
                'created_by' => $contact->created_by ?: $creatorId,
            ]);
            $contact->deleted_at = null;
            $contact->save();
            $this->markSynced('customer', $contact->id, $customer['id'] ?? null, $number);
            $count++;
        }

        return $count;
    }

    public function syncVendors(): int
    {
        $vendors = $this->client()->getCollection('vendors', [
            '$select' => 'id,number,displayName,addressLine1,addressLine2,city,state,country,postalCode,phoneNumber,email,taxRegistrationNumber,blocked',
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
                'contact_status' => ! empty($vendor['blocked']) ? 'inactive' : 'active',
                'created_by' => $contact->created_by ?: $creatorId,
            ]);
            $contact->deleted_at = null;
            $contact->save();
            $this->markSynced('vendor', $contact->id, $vendor['id'] ?? null, $number);
            $count++;
        }

        return $count;
    }

    public function syncSalespeople(): int
    {
        $rows = $this->client()->getCollection('salespeoplePurchasers', [
            '$select' => 'id,code,displayName,email,phoneNo,blocked',
        ]);
        $businessId = (int) $this->settings->business_central_business_id;
        $seenIds = [];
        $count = 0;

        foreach ($rows as $row) {
            $bcId = trim((string) ($row['id'] ?? ''));
            $code = trim((string) ($row['code'] ?? ''));
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
                'display_name' => $row['displayName'] ?: $code,
                'email' => $row['email'] ?? null,
                'phone_number' => $row['phoneNo'] ?? null,
                'blocked' => (bool) ($row['blocked'] ?? false),
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

                DB::transaction(function () use ($inventoryByItem, $businessId, $locationMapping, &$count) {
                    $mappedProductIds = BusinessCentralMapping::where('business_id', $businessId)
                        ->where('entity_type', 'product')
                        ->where('status', 'synced')
                        ->pluck('local_id');
                    VariationLocationDetails::where('location_id', $locationMapping->pos_location_id)
                        ->whereIn('product_id', $mappedProductIds)
                        ->update(['qty_available' => 0]);

                    foreach ($inventoryByItem as $number => $inventory) {
                        $product = Product::where('business_id', $businessId)->where('sku', $number)->first();
                        $variation = optional($product)->variations()->first();
                        if (! $product || ! $variation) {
                            continue;
                        }

                        VariationLocationDetails::updateOrCreate(
                            [
                                'product_id' => $product->id,
                                'variation_id' => $variation->id,
                                'product_variation_id' => $variation->product_variation_id,
                                'location_id' => $locationMapping->pos_location_id,
                            ],
                            ['qty_available' => $inventory]
                        );
                        $count++;
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

    protected function recordFailure(Throwable $e): void
    {
        Log::error('Business Central synchronization failed', ['error' => $e->getMessage()]);
        $this->settings?->update([
            'business_central_last_failed_at' => now(),
            'business_central_last_error' => mb_substr($e->getMessage(), 0, 65535),
        ]);
    }
}
