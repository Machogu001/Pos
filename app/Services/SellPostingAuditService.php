<?php

namespace App\Services;

use App\Business;
use App\Events\SellCreatedOrModified;
use App\Listeners\SyncSellDefaultAccountTransaction;
use App\Transaction;
use App\TransactionPayment;
use App\Utils\ModuleUtil;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SellPostingAuditService
{
    public function reconcileTransaction(Transaction $transaction): array
    {
        if (
            empty($transaction)
            || $transaction->type !== 'sell'
            || $transaction->status !== 'final'
            || $transaction->sub_type === 'subscription_invoice'
        ) {
            return ['attempted' => false, 'fixed' => false, 'still_missing' => false];
        }

        $transactionId = (int) $transaction->id;
        $businessId = (int) ($transaction->business_id ?? 0);

        if (! $this->isAccountModuleEnabledForBusiness($businessId)) {
            return ['attempted' => false, 'fixed' => false, 'still_missing' => false];
        }
        
        $wasMissing = $this->isTransactionMissingPostings($transactionId);

        if (! $wasMissing) {
            return ['attempted' => false, 'fixed' => false, 'still_missing' => false];
        }

        $missingBeforeRepair = $this->getMissingPostingTypes($transactionId);
        $diagnosticsBeforeRepair = $this->diagnoseMissingReason($transaction->fresh() ?? $transaction, $missingBeforeRepair);

        // Postings were missing on first check - re-dispatch event and retry with backoff
        Log::warning('SellPostingAuditService - missing postings detected, re-dispatching event', [
            'transaction_id' => $transactionId,
            'missing_postings' => $missingBeforeRepair,
            'diagnostics' => $diagnosticsBeforeRepair,
        ]);
        
        event(new SellCreatedOrModified($transaction->fresh() ?? $transaction));

        // Retry a few times with brief backoff; event listeners can finish a little later.
        $stillMissing = true;
        foreach ([100000, 250000, 500000] as $sleepMicros) {
            DB::reconnect();
            usleep($sleepMicros);
            $stillMissing = $this->isTransactionMissingPostings($transactionId);
            if (! $stillMissing) {
                break;
            }
        }

        // Last-resort forced sync to avoid leaving transactions missing postings.
        if ($stillMissing) {
            try {
                app(SyncSellDefaultAccountTransaction::class)
                    ->handle(new SellCreatedOrModified($transaction->fresh() ?? $transaction));
                DB::reconnect();
                $stillMissing = $this->isTransactionMissingPostings($transactionId);
            } catch (\Throwable $e) {
                Log::error('SellPostingAuditService::reconcileTransaction - forced sync failed', [
                    'transaction_id' => $transactionId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $missingAfterRepair = $this->getMissingPostingTypes($transactionId);
        $diagnosticsAfterRepair = $this->diagnoseMissingReason($transaction->fresh() ?? $transaction, $missingAfterRepair);

        if ($stillMissing) {
            Log::error('SellPostingAuditService::reconcileTransaction - missing postings remain after repair attempt', [
                'transaction_id' => $transactionId,
                'missing_postings_before_retry' => $missingBeforeRepair,
                'missing_postings_after_retry' => $missingAfterRepair,
                'diagnostics' => $diagnosticsAfterRepair,
            ]);
        } else {
            Log::info('SellPostingAuditService::reconcileTransaction - missing postings repaired', [
                'transaction_id' => $transactionId,
                'missing_postings_before_retry' => $missingBeforeRepair,
                'diagnostics' => $diagnosticsBeforeRepair,
            ]);
        }

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
            ->get()
            ->filter(function ($business) {
                return $this->isAccountModuleEnabledForBusiness((int) $business->id);
            })
            ->values();

        if ($businesses->isEmpty()) {
            return collect();
        }

        $summaryRows = $this->baseSellQuery($businessId)
            ->whereIn('t.business_id', $businesses->pluck('id')->all())
            ->leftJoin('account_transactions as atc', function ($join) {
                $this->applyPostingJoin($join, 'atc', 'sell_invoice_cogs');
            })
            ->leftJoin('account_transactions as ati', function ($join) {
                $this->applyPostingJoin($join, 'ati', 'sell_invoice_inventory');
            })
            ->selectRaw('t.business_id')
            ->selectRaw('COUNT(DISTINCT t.id) as final_non_subscription_sell_count')
            ->selectRaw('COUNT(DISTINCT CASE WHEN atc.id IS NULL THEN t.id END) as missing_cogs_count')
            ->selectRaw('COUNT(DISTINCT CASE WHEN ati.id IS NULL THEN t.id END) as missing_inventory_count')
            ->groupBy('t.business_id')
            ->get()
            ->keyBy('business_id');

        return $businesses->map(function ($business) use ($summaryRows) {
            $summary = $summaryRows->get($business->id);

            return [
                'business_id' => (int) $business->id,
                'business_name' => $business->name,
                'final_non_subscription_sell_count' => (int) ($summary->final_non_subscription_sell_count ?? 0),
                'missing_cogs_count' => (int) ($summary->missing_cogs_count ?? 0),
                'missing_inventory_count' => (int) ($summary->missing_inventory_count ?? 0),
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

        if (empty($businessId)) {
            $enabledBusinessIds = Business::query()
                ->select('id')
                ->get()
                ->filter(function ($business) {
                    return $this->isAccountModuleEnabledForBusiness((int) $business->id);
                })
                ->pluck('id')
                ->all();

            if (empty($enabledBusinessIds)) {
                return collect();
            }

            $query->whereIn('t.business_id', $enabledBusinessIds);
        } elseif (! $this->isAccountModuleEnabledForBusiness((int) $businessId)) {
            return collect();
        }

        return $query->get();
    }

    protected function isTransactionMissingPostings(int $transactionId): bool
    {
        $query = DB::table('transactions as t')
            ->where('t.id', $transactionId)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where(function ($subQuery) {
                $subQuery->whereNull('t.sub_type')
                    ->orWhere('t.sub_type', '!=', 'subscription_invoice');
            })
            ->leftJoin('account_transactions as atc', function ($join) {
                $this->applyPostingJoin($join, 'atc', 'sell_invoice_cogs');
            })
            ->leftJoin('account_transactions as ati', function ($join) {
                $this->applyPostingJoin($join, 'ati', 'sell_invoice_inventory');
            })
            ->where(function ($query) {
                $query->whereNull('atc.id')
                    ->orWhereNull('ati.id');
            });

        return $query->exists();
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

    protected function getMissingPostingTypes(int $transactionId): array
    {
        $row = DB::table('transactions as t')
            ->where('t.id', $transactionId)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where(function ($subQuery) {
                $subQuery->whereNull('t.sub_type')
                    ->orWhere('t.sub_type', '!=', 'subscription_invoice');
            })
            ->leftJoin('account_transactions as atc', function ($join) {
                $this->applyPostingJoin($join, 'atc', 'sell_invoice_cogs');
            })
            ->leftJoin('account_transactions as ati', function ($join) {
                $this->applyPostingJoin($join, 'ati', 'sell_invoice_inventory');
            })
            ->select('atc.id as cogs_id', 'ati.id as inventory_id')
            ->first();

        if (empty($row)) {
            return [];
        }

        $missing = [];
        if (empty($row->cogs_id)) {
            $missing[] = 'sell_invoice_cogs';
        }
        if (empty($row->inventory_id)) {
            $missing[] = 'sell_invoice_inventory';
        }

        return $missing;
    }

    protected function diagnoseMissingReason(Transaction $transaction, array $missingPostings): array
    {
        if (empty($missingPostings)) {
            return [];
        }

        $businessId = (int) ($transaction->business_id ?? 0);
        $moduleEnabled = app(ModuleUtil::class)->isModuleEnabled('account', $businessId);
        $cogsMapping = (int) (TransactionPayment::resolveDefaultAccountMapping('cogs', $businessId) ?: 0);
        $inventoryMapping = (int) (TransactionPayment::resolveDefaultAccountMapping('inventory', $businessId) ?: 0);
        $cogsAmount = (float) DB::table('transaction_sell_lines as tsl')
            ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->where('tsl.transaction_id', $transaction->id)
            ->where(function ($query) {
                $query->whereNull('tsl.children_type')
                    ->orWhere('tsl.children_type', '!=', 'combo');
            })
            ->sum(DB::raw('(tsl.quantity - COALESCE(tsl.quantity_returned, 0)) * COALESCE(NULLIF(v.dpp_inc_tax, 0), v.default_purchase_price, 0)'));

        $reasons = [];
        if (! $moduleEnabled) {
            $reasons[] = 'module_disabled';
        }
        if (in_array('sell_invoice_cogs', $missingPostings, true) && $cogsMapping <= 0) {
            $reasons[] = 'missing_mapping:cogs';
        }
        if (in_array('sell_invoice_inventory', $missingPostings, true) && $inventoryMapping <= 0) {
            $reasons[] = 'missing_mapping:inventory';
        }
        if ($cogsAmount <= 0) {
            $reasons[] = 'zero_cogs_amount';
        }
        if (empty($reasons)) {
            $reasons[] = 'unknown_check_listener_logs';
        }

        return [
            'module_enabled' => $moduleEnabled,
            'cogs_mapping' => $cogsMapping,
            'inventory_mapping' => $inventoryMapping,
            'cogs_amount' => round($cogsAmount, 4),
            'reasons' => $reasons,
        ];
    }

    protected function isAccountModuleEnabledForBusiness(int $businessId): bool
    {
        static $moduleEnabledByBusiness = [];

        if ($businessId <= 0) {
            return false;
        }

        if (array_key_exists($businessId, $moduleEnabledByBusiness)) {
            return $moduleEnabledByBusiness[$businessId];
        }

        try {
            $moduleEnabledByBusiness[$businessId] = app(ModuleUtil::class)->isModuleEnabled('account', $businessId);
        } catch (\Throwable $e) {
            $moduleEnabledByBusiness[$businessId] = false;
        }

        return $moduleEnabledByBusiness[$businessId];
    }
}
