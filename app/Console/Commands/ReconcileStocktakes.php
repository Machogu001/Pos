<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Stocktake;
use App\StockHistory;
use App\VariationLocationDetails;
use App\Utils\ProductUtil;

class ReconcileStocktakes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reconcile:stocktakes
                            {--reference= : Stocktake reference (e.g. ST-20251027-001)}
                            {--date_from= : Start date (YYYY-MM-DD) for range}
                            {--date_to= : End date (YYYY-MM-DD) for range}
                            {--location_id= : Location ID to filter}
                            {--user_id=1 : User ID to set as created_by for history entries}
                            {--dry-run : Report-only mode; do not modify the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile stocktakes: create missing StockHistory rows and update VariationLocationDetails to counted quantities';

    /** @var ProductUtil */
    protected $productUtil;

    public function __construct()
    {
        parent::__construct();
        $this->productUtil = app(ProductUtil::class);
    }

    public function handle()
    {
    $reference = $this->option('reference');
    $dateFrom = $this->option('date_from');
    $dateTo = $this->option('date_to');
    $locationId = $this->option('location_id');
    $userId = (int) $this->option('user_id');
    $dryRun = (bool) $this->option('dry-run');

        if (!$reference && !$dateFrom) {
            $this->error('Provide either --reference or --date_from/--date_to to select stocktakes.');
            return 1;
        }

        $query = Stocktake::with('items')->where('status', 'completed');

        if ($reference) {
            $query->where('reference_no', $reference);
        } else {
            if ($dateFrom) $query->whereDate('completed_at', '>=', $dateFrom);
            if ($dateTo) $query->whereDate('completed_at', '<=', $dateTo);
            if ($locationId) $query->where('location_id', $locationId);
        }

        $stocktakes = $query->get();

        if ($stocktakes->isEmpty()) {
            $this->info('No completed stocktakes found for the given criteria.');
            return 0;
        }

        foreach ($stocktakes as $st) {
            $this->info('Processing stocktake: ' . $st->reference_no . ' (id=' . $st->id . ')');

            if (!$dryRun) {
                DB::beginTransaction();
            }

            try {
                foreach ($st->items as $item) {
                    // Check if history exists for this item and reference
                    $exists = StockHistory::where('reference_no', $st->reference_no)
                        ->where('product_id', $item->product_id)
                        ->where('variation_id', $item->variation_id)
                        ->exists();

                    $currentQty = $this->productUtil->getStockByVariation($item->variation_id, $st->location_id);

                    if (!$exists) {
                        $adjustment = $item->counted_quantity - $currentQty;
                        if ($adjustment == 0) {
                            $this->info(" - Item {$item->id}: no adjustment needed (counted={$item->counted_quantity}, current={$currentQty})");
                        } else {
                            if ($dryRun) {
                                $this->info(" - DRY-RUN: Would create StockHistory for item {$item->id} (adjustment={$adjustment})");
                            } else {
                                StockHistory::create([
                                    'product_id' => $item->product_id,
                                    'product_variation_id' => $item->product_variation_id,
                                    'variation_id' => $item->variation_id,
                                    'location_id' => $st->location_id,
                                    'quantity' => abs($adjustment),
                                    'old_quantity' => $currentQty,
                                    'new_quantity' => $item->counted_quantity,
                                    'type' => $adjustment > 0 ? 'stocktake_increase' : 'stocktake_decrease',
                                    'transaction_id' => $st->adjustment_transaction_id ?? null,
                                    'reference_no' => $st->reference_no,
                                    'actual_adjustment' => $adjustment,
                                    'reason' => 'Reconciled stocktake ' . $st->reference_no,
                                    'created_by' => $userId,
                                ]);

                                $this->info(" - Created StockHistory for item {$item->id} (adjustment={$adjustment})");
                            }
                        }
                    } else {
                        $this->info(" - StockHistory exists for item {$item->id}, skipping history creation");
                    }

                    // Update VariationLocationDetails to match counted quantity
                    if ($dryRun) {
                        $this->info(" - DRY-RUN: Would set VariationLocationDetails for item {$item->id} to counted quantity {$item->counted_quantity} (current={$currentQty})");
                    } else {
                        $updateSuccess = $this->productUtil->updateProductQuantityForStocktake(
                            $st->location_id,
                            $item->product_id,
                            $item->variation_id,
                            $item->counted_quantity,
                            $item->lot_number,
                            $item->expiry_date
                        );

                        if ($updateSuccess) {
                            $this->info(" - Updated VariationLocationDetails for item {$item->id} to counted quantity {$item->counted_quantity}");
                        } else {
                            $this->error(" - Failed to update VariationLocationDetails for item {$item->id}");
                        }
                    }
                }

                if (!$dryRun) {
                    DB::commit();
                    $this->info('Completed reconciliation for stocktake: ' . $st->reference_no);
                } else {
                    $this->info('DRY-RUN completed for stocktake: ' . $st->reference_no);
                }
            } catch (\Exception $e) {
                if (!$dryRun) {
                    DB::rollBack();
                }
                $this->error('Failed to reconcile stocktake ' . $st->reference_no . ': ' . $e->getMessage());
            }
        }

        $this->info('All done.');
        return 0;
    }
}
