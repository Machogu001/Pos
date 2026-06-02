<?php

namespace App\Services;

use App\Business;
use App\Events\SellCreatedOrModified;
use App\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SellPostingAuditService
{
    public function summarize(?int $businessId = null): array
    {
        $businessSummaries = $this->getBusinessSummaries($businessId);

        return [
            'scope_business_id' => $businessId,
            'final_non_subscription_sell_count' => (int) $businessSummaries->sum('final_non_subscription_sell_count'),
            'missing_cogs_count' => (int) $businessSummaries->sum('missing_cogs_count'),
            'missing_inventory_count' => (int) $businessSummaries->sum('missing_inventory_count'),
            'affected_businesses' => $businessSummaries
                ->filter(function ($summary) {
                    return $summary['missing_cogs_count'] > 0 || $summary['missing_inventory_count'] > 0;
                })
                ->values()
                ->all(),
        ];
    }

    public function backfill(?int $businessId = null, bool $dryRun = false): array
    {
        $missingTransactions = $this->getMissingTransactions($businessId);
        $processed = 0;
        $errors = [];

        foreach ($missingTransactions as $row) {
            if ($dryRun) {
                continue;
            }

            try {
                $transaction = Transaction::find($row->id);
                if (empty($transaction) || $transaction->type !== 'sell' || $transaction->status !== 'final') {
                    continue;
                }

                event(new SellCreatedOrModified($transaction));
                $processed++;
            } catch (\Throwable $e) {
                $errors[] = [
                    'id' => (int) $row->id,
                    'invoice_no' => $row->invoice_no,
                    'message' => $e->getMessage(),
                ];
            }
        }

        $summary = $this->summarize($businessId);

        return [
            'scope_business_id' => $businessId,
            'initial_missing_count' => $missingTransactions->count(),
            'processed_count' => $dryRun ? 0 : $processed,
            'error_count' => count($errors),
            'errors' => $errors,
            'summary' => $summary,
        ];
    }

    public function alertAndOptionallyBackfill(?int $businessId = null, bool $fix = false, bool $dryRun = false): array
    {
        $initialSummary = $this->summarize($businessId);
        $hasMissing = $initialSummary['missing_cogs_count'] > 0 || $initialSummary['missing_inventory_count'] > 0;

        if ($hasMissing) {
            Log::warning('Missing item-sell accounting postings detected.', [
                'business_id' => $businessId,
                'missing_cogs_count' => $initialSummary['missing_cogs_count'],
                'missing_inventory_count' => $initialSummary['missing_inventory_count'],
                'affected_businesses' => $initialSummary['affected_businesses'],
            ]);
        }

        if (! $fix || ! $hasMissing) {
            return [
                'fixed' => false,
                'initial_summary' => $initialSummary,
                'result' => null,
            ];
        }

        $result = $this->backfill($businessId, $dryRun);

        Log::info('Sell posting backfill completed.', [
            'business_id' => $businessId,
            'processed_count' => $result['processed_count'],
            'error_count' => $result['error_count'],
            'remaining_missing_cogs_count' => $result['summary']['missing_cogs_count'],
            'remaining_missing_inventory_count' => $result['summary']['missing_inventory_count'],
            'dry_run' => $dryRun,
        ]);

        return [
            'fixed' => ! $dryRun,
            'initial_summary' => $initialSummary,
            'result' => $result,
        ];
    }

    protected function getBusinessSummaries(?int $businessId = null): Collection
    {
        $businesses = Business::query()
            ->select('id', 'name')
            ->when(! empty($businessId), function ($query) use ($businessId) {
                $query->where('id', $businessId);
            })
            ->get();

        return $businesses->map(function ($business) {
            $base = $this->baseSellQuery((int) $business->id);

            return [
                'business_id' => (int) $business->id,
                'business_name' => $business->name,
                'final_non_subscription_sell_count' => (int) (clone $base)->count('t.id'),
                'missing_cogs_count' => (int) (clone $base)
                    ->leftJoin('account_transactions as atc', function ($join) {
                        $this->applyPostingJoin($join, 'atc', 'sell_invoice_cogs');
                    })
                    ->whereNull('atc.id')
                    ->distinct()
                    ->count('t.id'),
                'missing_inventory_count' => (int) (clone $base)
                    ->leftJoin('account_transactions as ati', function ($join) {
                        $this->applyPostingJoin($join, 'ati', 'sell_invoice_inventory');
                    })
                    ->whereNull('ati.id')
                    ->distinct()
                    ->count('t.id'),
            ];
        });
    }

    protected function getMissingTransactions(?int $businessId = null): Collection
    {
        $query = $this->baseSellQuery($businessId)
            ->leftJoin('account_transactions as atc', function ($join) {
                $this->applyPostingJoin($join, 'atc', 'sell_invoice_cogs');
            })
            ->leftJoin('account_transactions as ati', function ($join) {
                $this->applyPostingJoin($join, 'ati', 'sell_invoice_inventory');
            })
            ->where(function ($query) {
                $query->whereNull('atc.id')
                    ->orWhereNull('ati.id');
            })
            ->select('t.id', 't.invoice_no', 't.ref_no', 't.business_id')
            ->distinct()
            ->orderBy('t.id');

        return $query->get();
    }

    protected function baseSellQuery(?int $businessId = null)
    {
        $query = DB::table('transactions as t')
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where(function ($subQuery) {
                $subQuery->whereNull('t.sub_type')
                    ->orWhere('t.sub_type', '!=', 'subscription_invoice');
            });

        if ($this->transactionsHasDeletedAt()) {
            $query->whereNull('t.deleted_at');
        }

        if (! empty($businessId)) {
            $query->where('t.business_id', $businessId);
        }

        return $query;
    }

    protected function applyPostingJoin($join, string $alias, string $reffNo): void
    {
        $join->on($alias . '.transaction_id', '=', 't.id')
            ->where($alias . '.reff_no', $reffNo)
            ->whereNull($alias . '.transaction_payment_id');

        if ($this->accountTransactionsHasDeletedAt()) {
            $join->whereNull($alias . '.deleted_at');
        }
    }

    protected function transactionsHasDeletedAt(): bool
    {
        static $hasDeletedAt;

        if ($hasDeletedAt === null) {
            $hasDeletedAt = Schema::hasColumn('transactions', 'deleted_at');
        }

        return $hasDeletedAt;
    }

    protected function accountTransactionsHasDeletedAt(): bool
    {
        static $hasDeletedAt;

        if ($hasDeletedAt === null) {
            $hasDeletedAt = Schema::hasColumn('account_transactions', 'deleted_at');
        }

        return $hasDeletedAt;
    }
}
