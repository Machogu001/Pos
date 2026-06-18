<?php

namespace App\Console\Commands;

use App\AdminSetting;
use App\BusinessLocation;
use App\Transaction;
use App\VariationLocationDetails;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillStockCostingLayers extends Command
{
    protected $signature = 'stock:backfill-costing-layers
                            {--business-id= : Business id (required)}
                            {--location-id= : Location id (required)}
                            {--variation-id= : Restrict to one variation id}
                            {--dry-run : Preview only, no writes}';

    protected $description = 'Backfill missing stock costing layers for positive qty_available without changing physical stock quantities.';

    public function handle(): int
    {
        $businessId = $this->option('business-id');
        $locationId = $this->option('location-id');
        $variationId = $this->option('variation-id');
        $dryRun = (bool) $this->option('dry-run');

        $businessId = $businessId !== null ? (int) $businessId : null;
        $locationId = $locationId !== null ? (int) $locationId : null;
        $variationId = $variationId !== null ? (int) $variationId : null;

        if (empty($businessId) || empty($locationId)) {
            $this->error('Options --business-id and --location-id are required.');

            return 1;
        }

        $location = BusinessLocation::query()
            ->where('id', $locationId)
            ->where('business_id', $businessId)
            ->first();

        if (empty($location)) {
            $this->error('Location not found for the specified business.');

            return 1;
        }

        $query = VariationLocationDetails::query()
            ->join('products as p', 'p.id', '=', 'variation_location_details.product_id')
            ->join('variations as v', 'v.id', '=', 'variation_location_details.variation_id')
            ->where('variation_location_details.location_id', $locationId)
            ->where('p.business_id', $businessId)
            ->where('p.enable_stock', 1)
            ->where('variation_location_details.qty_available', '>', 0)
            ->select([
                'variation_location_details.product_id',
                'variation_location_details.variation_id',
                'variation_location_details.qty_available',
                'p.name as product_name',
                'v.sub_sku',
                'v.default_purchase_price',
                'v.dpp_inc_tax',
            ]);

        if (! empty($variationId)) {
            $query->where('variation_location_details.variation_id', $variationId);
        }

        $rows = $query->get();

        $checked = 0;
        $needsLayer = 0;
        $createdLines = 0;
        $totalLayerQty = 0.0;
        $totalBeforeTax = 0.0;
        $totalIncTax = 0.0;

        $linePayloads = [];

        foreach ($rows as $row) {
            $checked++;

            $qtyAvailable = (float) $row->qty_available;
            $layerAvailable = $this->getLayerAvailableQuantity(
                $businessId,
                $locationId,
                (int) $row->product_id,
                (int) $row->variation_id
            );

            $gap = round($qtyAvailable - $layerAvailable, 4);
            if ($gap <= 0) {
                continue;
            }

            $needsLayer++;

            $purchasePrice = (float) ($row->default_purchase_price ?? 0);
            $purchasePriceIncTax = (float) ($row->dpp_inc_tax ?? 0);
            if ($purchasePrice <= 0 && $purchasePriceIncTax > 0) {
                $purchasePrice = $purchasePriceIncTax;
            }
            if ($purchasePriceIncTax <= 0) {
                $purchasePriceIncTax = $purchasePrice;
            }

            $itemTax = max(0, $purchasePriceIncTax - $purchasePrice);

            $this->line(sprintf(
                '[%s] variation_id=%d sku=%s product=%s qty_available=%.4f layer_available=%.4f gap=%.4f',
                $dryRun ? 'DRY-RUN' : 'FIX',
                (int) $row->variation_id,
                (string) ($row->sub_sku ?? ''),
                (string) ($row->product_name ?? ''),
                $qtyAvailable,
                $layerAvailable,
                $gap
            ));

            $linePayloads[] = [
                'product_id' => (int) $row->product_id,
                'variation_id' => (int) $row->variation_id,
                'quantity' => $gap,
                'item_tax' => $itemTax,
                'tax_id' => null,
                'pp_without_discount' => $purchasePrice,
                'purchase_price' => $purchasePrice,
                'purchase_price_inc_tax' => $purchasePriceIncTax,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $totalLayerQty += $gap;
            $totalBeforeTax += $gap * $purchasePrice;
            $totalIncTax += $gap * $purchasePriceIncTax;
        }

        if (! $dryRun && ! empty($linePayloads)) {
            DB::transaction(function () use (
                $businessId,
                $locationId,
                $linePayloads,
                $totalBeforeTax,
                $totalIncTax,
                &$createdLines
            ) {
                $createdBy = (int) (DB::table('users')->where('role', 'admin')->value('id') ?: 1);

                $transaction = Transaction::create([
                    'type' => 'stocktake_adjustment',
                    'status' => 'received',
                    'business_id' => $businessId,
                    'location_id' => $locationId,
                    'payment_status' => 'paid',
                    'transaction_date' => now(),
                    'total_before_tax' => round($totalBeforeTax, 4),
                    'final_total' => round($totalIncTax, 4),
                    'additional_notes' => 'Auto-backfill costing layers only (no qty_available update).',
                    'created_by' => $createdBy,
                ]);

                foreach ($linePayloads as $payload) {
                    $payload['transaction_id'] = $transaction->id;
                    DB::table('purchase_lines')->insert($payload);
                    $createdLines++;
                }
            });
        }

        if (! $dryRun) {
            $this->markLastRun();
        }

        $this->newLine();
        $this->info('Stock costing layer backfill summary:');
        $this->line('Business id: '.$businessId);
        $this->line('Location id: '.$locationId);
        $this->line('Variation filter: '.($variationId ?? 'all'));
        $this->line('Rows checked: '.$checked);
        $this->line('Rows needing layer backfill: '.$needsLayer);
        $this->line('Layer qty backfilled: '.round($totalLayerQty, 4));
        $this->line('Purchase lines created: '.($dryRun ? 0 : $createdLines));
        $this->line('Dry run: '.($dryRun ? 'yes' : 'no'));

        if ($needsLayer === 0) {
            $this->info('No missing costing layers found for this scope.');
        }

        return 0;
    }

    protected function getLayerAvailableQuantity(int $businessId, int $locationId, int $productId, int $variationId): float
    {
        $value = (float) DB::table('purchase_lines as pl')
            ->join('transactions as t', 't.id', '=', 'pl.transaction_id')
            ->where('t.business_id', $businessId)
            ->where('t.location_id', $locationId)
            ->whereIn('t.type', ['purchase', 'purchase_transfer', 'opening_stock', 'production_purchase', 'stocktake_adjustment'])
            ->where('t.status', 'received')
            ->where('pl.product_id', $productId)
            ->where('pl.variation_id', $variationId)
            ->sum(DB::raw('GREATEST(0, pl.quantity - (pl.quantity_sold + pl.quantity_adjusted + pl.quantity_returned + pl.mfg_quantity_used))'));

        return round($value, 4);
    }

    protected function markLastRun(): void
    {
        if (! Schema::hasTable('admin_settings') || ! Schema::hasColumn('admin_settings', 'stock_costing_backfill_last_run_at')) {
            return;
        }

        $settings = AdminSetting::firstOrCreate([]);
        $settings->stock_costing_backfill_last_run_at = now();
        $settings->save();
    }
}
