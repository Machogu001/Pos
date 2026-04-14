<?php

namespace App\Console\Commands;

use App\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillStocktakeAdjustmentValues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stocktake:backfill-adjustment-values
                            {--business_id= : Limit to a specific business ID}
                            {--dry-run : Show changes without writing to the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate historical stocktake adjustment totals from signed stock adjustment line items.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $businessId = $this->option('business_id');
        $dryRun = (bool) $this->option('dry-run');

        $query = Transaction::query()
            ->with(['stock_adjustment_lines'])
            ->where('type', 'stock_adjustment')
            ->where('additional_notes', 'like', 'Stocktake adjustment -%');

        if (! empty($businessId)) {
            $query->where('business_id', $businessId);
        }

        $totalTransactions = $query->count();

        if ($totalTransactions === 0) {
            $this->info('No stocktake adjustment transactions found to backfill.');
            return 0;
        }

        $this->info("Found {$totalTransactions} stocktake adjustment transactions to review.");

        $updated = 0;
        $skipped = 0;

        $query->chunkById(100, function ($transactions) use (&$updated, &$skipped, $dryRun) {
            foreach ($transactions as $transaction) {
                $computedTotal = round($transaction->stock_adjustment_lines->sum(function ($line) {
                    return (float) $line->quantity * (float) $line->unit_price;
                }), 2);

                $storedTotal = round((float) $transaction->final_total, 2);
                $needsFlagUpdate = (int) ($transaction->is_stocktake ?? 0) !== 1;

                if ((float) $storedTotal === (float) $computedTotal
                    && (float) round((float) $transaction->total_before_tax, 2) === (float) $computedTotal
                    && ! $needsFlagUpdate
                ) {
                    $skipped++;
                    continue;
                }

                $this->line(sprintf(
                    'Transaction %d (%s): final_total %s -> %s',
                    $transaction->id,
                    $transaction->ref_no ?? 'no-ref',
                    number_format($storedTotal, 2),
                    number_format($computedTotal, 2)
                ));

                if (! $dryRun) {
                    DB::transaction(function () use ($transaction, $computedTotal) {
                        $transaction->update([
                            'is_stocktake' => 1,
                            'final_total' => $computedTotal,
                            'total_before_tax' => $computedTotal,
                        ]);
                    });
                }

                $updated++;
            }
        });

        if ($dryRun) {
            $this->info("Dry run complete. {$updated} transactions would be updated, {$skipped} already matched.");
        } else {
            $this->info("Backfill complete. Updated {$updated} transactions, skipped {$skipped} already-correct rows.");
        }

        return 0;
    }
}