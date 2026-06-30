<?php

namespace App\Services;

use App\Business;
use App\Events\PurchaseCreatedOrModified;
use App\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class PurchasePostingAuditService
{
    public function reconcileTransaction(Transaction $transaction): array
    {
        if (
            empty($transaction)
            || $transaction->type !== 'purchase'
        ) {
            return ['attempted' => false, 'fixed' => false, 'still_missing' => false];
        }

        $transactionId = (int) $transaction->id;
       
        // First check: are postings missing?
        $wasMissing = $this->isTransactionMissingPostings($transactionId);

        if (! $wasMissing) {
               // Postings already exist - no need for reconciliation
               return ['attempted' => false, 'fixed' => false, 'still_missing' => false];
        }

           // Postings were missing on first check - re-dispatch event and retry with backoff
           Log::warning('PurchasePostingAuditService - missing postings detected, re-dispatching event', [
               'transaction_id' => $transactionId,
           ]);
       
        event(new PurchaseCreatedOrModified($transaction->fresh() ?? $transaction));

        // Reconnect to DB to clear query cache and see listener's committed data
        DB::reconnect();

        $stillMissing = $this->isTransactionMissingPostings($transactionId);
       Log::info('PurchasePostingAuditService::reconcileTransaction - repair attempt completed', [
           'transaction_id' => $transactionId,
           'was_missing_initially' => true,
           'still_missing_after_retry' => $stillMissing,
       ]);


        return [
            'attempted' => true,
            'fixed' => ! $stillMissing,
            'still_missing' => $stillMissing,
        ];
    }

    public function summarize(?int $businessId = null): array
    {
        $businessSummaries = $this->getBusinessSummaries($businessId);

        return [
            'scope_business_id' => $businessId,
            'purchase_count' => (int) $businessSummaries->sum('purchase_count'),
            'missing_purchase_count' => (int) $businessSummaries->sum('missing_purchase_count'),
            'missing_inventory_count' => (int) $businessSummaries->sum('missing_inventory_count'),
            'affected_businesses' => $businessSummaries
                ->filter(function ($summary) {
                    return $summary['missing_purchase_count'] > 0 || $summary['missing_inventory_count'] > 0;
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
                if (empty($transaction) || $transaction->type !== 'purchase') {
                    continue;
                }

                event(new PurchaseCreatedOrModified($transaction));
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
        $hasMissing = $initialSummary['missing_purchase_count'] > 0 || $initialSummary['missing_inventory_count'] > 0;

        if ($hasMissing) {
            Log::warning('Missing purchase accounting postings detected.', [
                'business_id' => $businessId,
                'missing_purchase_count' => $initialSummary['missing_purchase_count'],
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

        Log::info('Purchase posting backfill completed.', [
            'business_id' => $businessId,
            'processed_count' => $result['processed_count'],
            'error_count' => $result['error_count'],
            'remaining_missing_purchase_count' => $result['summary']['missing_purchase_count'],
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
            $base = $this->basePurchaseQuery((int) $business->id);

            return [
                'business_id' => (int) $business->id,
                'business_name' => $business->name,
                'purchase_count' => (int) (clone $base)->count('t.id'),
                'missing_purchase_count' => (int) (clone $base)
                    ->leftJoin('account_transactions as atp', function ($join) {
                        $this->applyPostingJoin($join, 'atp', 'purchase_invoice');
                    })
                    ->whereNull('atp.id')
                    ->distinct()
                    ->count('t.id'),
                'missing_inventory_count' => (int) (clone $base)
                    ->leftJoin('account_transactions as ati', function ($join) {
                        $this->applyPostingJoin($join, 'ati', ['purchase_inventory', 'purchase_invoice_inventory']);
                    })
                    ->whereNull('ati.id')
                    ->distinct()
                    ->count('t.id'),
            ];
        });
    }

    protected function getMissingTransactions(?int $businessId = null): Collection
    {
        $query = $this->basePurchaseQuery($businessId)
            ->leftJoin('account_transactions as atp', function ($join) {
                $this->applyPostingJoin($join, 'atp', 'purchase_invoice');
            })
            ->leftJoin('account_transactions as ati', function ($join) {
                $this->applyPostingJoin($join, 'ati', ['purchase_inventory', 'purchase_invoice_inventory']);
            })
            ->where(function ($query) {
                $query->whereNull('atp.id')
                    ->orWhereNull('ati.id');
            })
            ->select('t.id', 't.invoice_no', 't.ref_no', 't.business_id')
            ->distinct()
            ->orderBy('t.id');

        return $query->get();
    }

    protected function isTransactionMissingPostings(int $transactionId): bool
    {
        $query = DB::table('transactions as t')
            ->where('t.id', $transactionId)
            ->where('t.type', 'purchase')
            ->leftJoin('account_transactions as atp', function ($join) {
                $this->applyPostingJoin($join, 'atp', 'purchase_invoice');
            })
            ->leftJoin('account_transactions as ati', function ($join) {
                $this->applyPostingJoin($join, 'ati', ['purchase_inventory', 'purchase_invoice_inventory']);
            })
            ->where(function ($query) {
                $query->whereNull('atp.id')
                    ->orWhereNull('ati.id');
            });

        return $query->exists();
    }

    protected function basePurchaseQuery(?int $businessId = null)
    {
        $query = DB::table('transactions as t')
            ->where('t.type', 'purchase');

        if ($this->transactionsHasDeletedAt()) {
            $query->whereNull('t.deleted_at');
        }

        if (! empty($businessId)) {
            $query->where('t.business_id', $businessId);
        }

        return $query;
    }

    protected function applyPostingJoin($join, string $alias, $reffNo): void
    {
        $join->on($alias . '.transaction_id', '=', 't.id')
            ->whereNull($alias . '.transaction_payment_id');

        if (is_array($reffNo)) {
            $join->whereIn($alias . '.reff_no', $reffNo);
        } else {
            $join->where($alias . '.reff_no', $reffNo);
        }

        if ($this->accountTransactionsHasDeletedAt()) {
            $join->whereNull($alias . '.deleted_at');
        }
    }

    protected function transactionsHasDeletedAt(): bool
    {
        return Schema::hasColumn('transactions', 'deleted_at');
    }

    protected function accountTransactionsHasDeletedAt(): bool
    {
        return Schema::hasColumn('account_transactions', 'deleted_at');
    }
}
