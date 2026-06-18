<?php

namespace App\Console\Commands;

use App\BusinessLocation;
use App\Utils\ProductUtil;
use Illuminate\Console\Command;

class FixNegativeStockMismatches extends Command
{
    protected $signature = 'stock:fix-negative-mismatches
                            {--business-id= : Restrict repair to one business}
                            {--location-id= : Restrict repair to one location (required when business has multiple locations)}
                            {--variation-id= : Restrict repair to one variation}
                            {--force-reset-positive : Also reset rows where current qty_available is positive}
                            {--dry-run : Preview repairs without writing changes}';

    protected $description = 'Repair negative calculated stock mismatches by clamping qty_available to max(0, calculated_stock).';

    protected ProductUtil $productUtil;

    public function __construct(ProductUtil $productUtil)
    {
        parent::__construct();
        $this->productUtil = $productUtil;
    }

    public function handle(): int
    {
        $businessId = $this->option('business-id');
        $locationId = $this->option('location-id');
        $variationId = $this->option('variation-id');
        $forceResetPositive = (bool) $this->option('force-reset-positive');
        $dryRun = (bool) $this->option('dry-run');

        $businessId = $businessId !== null ? (int) $businessId : null;
        $locationId = $locationId !== null ? (int) $locationId : null;
        $variationId = $variationId !== null ? (int) $variationId : null;

        if (empty($businessId)) {
            $this->error('Option --business-id is required.');

            return 1;
        }

        if (! empty($variationId) && empty($locationId)) {
            $this->error('Option --location-id is required when using --variation-id.');

            return 1;
        }

        $locationsQuery = BusinessLocation::query()->where('business_id', $businessId);
        if (! empty($locationId)) {
            $locationsQuery->where('id', $locationId);
        }
        $locations = $locationsQuery->get(['id', 'name']);

        if ($locations->isEmpty()) {
            $this->error('No matching business locations found for the provided filters.');

            return 1;
        }

        $checkedRows = 0;
        $negativeRows = 0;
        $skippedPositiveRows = 0;
        $updatedRows = 0;

        $this->info('Scanning calculated stock mismatches...');

        foreach ($locations as $location) {
            $rows = $this->productUtil->getVariationStockMisMatch($businessId, $variationId, (int) $location->id);

            foreach ($rows as $row) {
                $checkedRows++;
                $calculated = (float) ($row->total_stock_calculated ?? 0);
                if ($calculated >= 0) {
                    continue;
                }

                $negativeRows++;
                $currentStock = (float) ($row->stock ?? 0);

                // Keep positive current qty_available by default (stocktake/adjustment can legitimately create it).
                if (! $forceResetPositive && $currentStock > 0) {
                    $skippedPositiveRows++;
                    $this->line(sprintf(
                        '[SKIP-POSITIVE] variation_id=%d sku=%s product=%s location=%s calculated=%.4f current=%.4f',
                        (int) $row->variation_id,
                        (string) ($row->sub_sku ?? ''),
                        (string) ($row->product ?? ''),
                        (string) ($location->name ?? $location->id),
                        $calculated,
                        $currentStock
                    ));

                    continue;
                }

                $targetStock = 0.0;

                $this->line(sprintf(
                    '[%s] variation_id=%d sku=%s product=%s location=%s calculated=%.4f current=%.4f target=%.4f',
                    $dryRun ? 'DRY-RUN' : 'FIX',
                    (int) $row->variation_id,
                    (string) ($row->sub_sku ?? ''),
                    (string) ($row->product ?? ''),
                    (string) ($location->name ?? $location->id),
                    $calculated,
                    $currentStock,
                    $targetStock
                ));

                if (! $dryRun) {
                    $this->productUtil->fixVariationStockMisMatch(
                        $businessId,
                        (int) $row->variation_id,
                        (int) $location->id,
                        $targetStock
                    );
                    $updatedRows++;
                }
            }
        }

        $this->newLine();
        $this->info('Negative stock mismatch repair summary:');
        $this->line('Business id: '.$businessId);
        $this->line('Location filter: '.($locationId ?? 'all')); 
        $this->line('Variation filter: '.($variationId ?? 'all'));
        $this->line('Force reset positive current qty: '.($forceResetPositive ? 'yes' : 'no'));
        $this->line('Rows checked: '.$checkedRows);
        $this->line('Negative calculated rows: '.$negativeRows);
        $this->line('Skipped (positive current qty): '.$skippedPositiveRows);
        $this->line('Rows updated: '.($dryRun ? 0 : $updatedRows));

        if ($negativeRows === 0) {
            $this->info('No negative calculated stock mismatches found.');
        }

        return 0;
    }
}
