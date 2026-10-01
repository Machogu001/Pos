<?php

namespace App\Listeners;

use App\Account;
use App\Business;
use App\Events\ExpenseCreatedOrModified;
use App\Events\PurchaseCreatedOrModified;
use App\Events\SellCreatedOrModified;
use App\TransactionPayment;
use App\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\Accounting\Entities\JournalEntry;
use Modules\Accounting\Entities\PaymentDetail;

class SyncAccountingJournalEntryMapping
{
    public function handleSell(SellCreatedOrModified $event)
    {
        $transaction = $event->transaction;

        if (empty($transaction) || $transaction->type !== 'sell' || $transaction->status !== 'final') {
            return true;
        }

        return $this->syncSellTransaction($transaction);
    }

    public function handlePurchase(PurchaseCreatedOrModified $event)
    {
        $transaction = $event->transaction;

        if (empty($transaction) || $transaction->type !== 'purchase' || !empty($event->isDeleted)) {
            return true;
        }

        return $this->syncTransaction($transaction, 'purchase', 'debit', 'purchase_payment');
    }

    public function handleExpense(ExpenseCreatedOrModified $event)
    {
        $transaction = $event->expense;

        if (empty($transaction) || !in_array($transaction->type, ['expense', 'expense_refund']) || !empty($event->isDeleted)) {
            return true;
        }

        return $this->syncTransaction($transaction, 'expense', 'debit', 'expense');
    }

    protected function syncTransaction($transaction, $mappingKey, $mapType, $mappingFor)
    {
        if (!$this->canSync()) {
            return true;
        }

        // Refresh transaction from database to get latest journal_entry_id
        $transaction = $transaction->fresh() ?? $transaction;
        
        if (!empty($transaction->journal_entry_id)) {
            return true;
        }

        $amount = round((float) $transaction->final_total, 4);
        if ($amount <= 0) {
            return true;
        }

        $business = Business::select('id', 'currency_id')->find($transaction->business_id);
        if (empty($business)) {
            return true;
        }

        $accountId = TransactionPayment::resolveDefaultAccountMapping($mappingKey, $transaction->business_id);
        if (empty($accountId)) {
            return true;
        }

        $account = Account::select('id', 'account_number')
            ->where('business_id', $transaction->business_id)
            ->find($accountId);
        if (empty($account) || empty($account->account_number)) {
            return true;
        }

        $chartOfAccount = ChartOfAccount::where('business_id', $transaction->business_id)
            ->where('active', 1)
            ->where('gl_code', (int) $account->account_number)
            ->first();

        if (empty($chartOfAccount)) {
            return true;
        }

        DB::transaction(function () use ($transaction, $business, $chartOfAccount, $amount, $mapType, $mappingFor) {
            // Double-check inside transaction for concurrent safety
            $currentTransaction = $transaction::lockForUpdate()->find($transaction->id);
            if (!empty($currentTransaction->journal_entry_id)) {
                return;
            }

            $paymentDetail = new PaymentDetail();
            $paymentDetail->created_by_id = (int) ($transaction->created_by ?: 1);
            $paymentDetail->payment_type_id = 1;
            $paymentDetail->transaction_type = "auto_map_{$mappingFor}_transaction_to_journal_entry";
            $paymentDetail->save();

            $entryDate = !empty($transaction->transaction_date)
                ? date('Y-m-d', strtotime($transaction->transaction_date))
                : date('Y-m-d');

            $journalEntry = new JournalEntry();
            $journalEntry->created_by_id = (int) ($transaction->created_by ?: 1);
            $journalEntry->transaction_number = get_uniqid();
            $journalEntry->payment_detail_id = $paymentDetail->id;
            $journalEntry->location_id = $transaction->location_id;
            $journalEntry->currency_id = $business->currency_id;
            $journalEntry->chart_of_account_id = $chartOfAccount->id;
            $journalEntry->transaction_type = "auto_map_{$mappingFor}_transaction_to_journal_entry";
            $journalEntry->date = $entryDate;
            $journalEntry->month = date('m', strtotime($entryDate));
            $journalEntry->year = date('Y', strtotime($entryDate));
            $journalEntry->debit = $mapType === 'debit' ? $amount : 0;
            $journalEntry->credit = $mapType === 'credit' ? $amount : 0;
            $journalEntry->manual_entry = 0;
            $journalEntry->notes = 'Auto-mapped by accounting integration';
            $journalEntry->save();

            $currentTransaction->journal_entry_id = $journalEntry->id;
            $currentTransaction->save();
        });

        return true;
    }

    protected function syncSellTransaction($transaction)
    {
        if (! $this->canSync()) {
            return true;
        }

        $transaction = $transaction->fresh() ?? $transaction;

        if ((float) $transaction->final_total <= 0) {
            return true;
        }

        $business = Business::select('id', 'currency_id')->find($transaction->business_id);
        if (empty($business)) {
            return true;
        }

        $receivableChart = $this->resolveChartOfAccountForMapping('accounts_receivable', $transaction->business_id);
        $revenueChart = $this->resolveChartOfAccountForMapping('sell', $transaction->business_id);
        if (empty($receivableChart) || empty($revenueChart)) {
            return true;
        }

        $taxChart = $this->resolveChartOfAccountForMapping('sales_tax', $transaction->business_id);
        $cogsChart = $this->resolveChartOfAccountForMapping('cogs', $transaction->business_id);
        $inventoryChart = $this->resolveChartOfAccountForMapping('inventory', $transaction->business_id);

        $amountBeforeTax = round((float) (! is_null($transaction->total_before_tax)
            ? $transaction->total_before_tax
            : ((float) $transaction->final_total - (float) $transaction->tax_amount)), 4);
        $taxAmount = round((float) $transaction->tax_amount, 4);
        $cogsAmount = $this->calculateSellCogsAmount((int) $transaction->id);

        DB::transaction(function () use ($transaction, $business, $receivableChart, $revenueChart, $taxChart, $cogsChart, $inventoryChart, $amountBeforeTax, $taxAmount, $cogsAmount) {
            $currentTransaction = Transaction::lockForUpdate()->find($transaction->id);
            if (empty($currentTransaction)) {
                return;
            }

            $linkedJournalEntry = ! empty($currentTransaction->journal_entry_id)
                ? JournalEntry::find($currentTransaction->journal_entry_id)
                : null;

            if (! $this->sellJournalNeedsSync($currentTransaction, $linkedJournalEntry)) {
                return;
            }

            $transactionNumber = $linkedJournalEntry->transaction_number ?? get_uniqid();
            $entryDate = ! empty($currentTransaction->transaction_date)
                ? date('Y-m-d', strtotime($currentTransaction->transaction_date))
                : date('Y-m-d');
            $month = date('m', strtotime($entryDate));
            $year = date('Y', strtotime($entryDate));

            $paymentDetailId = null;
            if (! empty($linkedJournalEntry)) {
                $paymentDetailId = $linkedJournalEntry->payment_detail_id;
                JournalEntry::where('transaction_number', $transactionNumber)->delete();
            }

            if (empty($paymentDetailId)) {
                $paymentDetail = new PaymentDetail();
                $paymentDetail->created_by_id = (int) ($currentTransaction->created_by ?: 1);
                $paymentDetail->payment_type_id = 1;
                $paymentDetail->transaction_type = 'auto_map_sell_transaction_to_journal_entry';
                $paymentDetail->save();
                $paymentDetailId = $paymentDetail->id;
            } else {
                PaymentDetail::where('id', $paymentDetailId)->update([
                    'created_by_id' => (int) ($currentTransaction->created_by ?: 1),
                    'payment_type_id' => 1,
                    'transaction_type' => 'auto_map_sell_transaction_to_journal_entry',
                ]);
            }

            $entries = [
                [
                    'chart' => $receivableChart,
                    'debit' => round((float) $currentTransaction->final_total, 4),
                    'credit' => 0.0,
                ],
                [
                    'chart' => $revenueChart,
                    'debit' => 0.0,
                    'credit' => $amountBeforeTax,
                ],
            ];

            if ($taxAmount > 0 && ! empty($taxChart)) {
                $entries[] = [
                    'chart' => $taxChart,
                    'debit' => 0.0,
                    'credit' => $taxAmount,
                ];
            }

            if ($cogsAmount > 0 && ! empty($cogsChart) && ! empty($inventoryChart)) {
                $entries[] = [
                    'chart' => $cogsChart,
                    'debit' => $cogsAmount,
                    'credit' => 0.0,
                ];
                $entries[] = [
                    'chart' => $inventoryChart,
                    'debit' => 0.0,
                    'credit' => $cogsAmount,
                ];
            }

            $firstJournalEntryId = null;
            foreach ($entries as $entry) {
                if (($entry['debit'] ?? 0) <= 0 && ($entry['credit'] ?? 0) <= 0) {
                    continue;
                }

                $journalEntry = new JournalEntry();
                $journalEntry->created_by_id = (int) ($currentTransaction->created_by ?: 1);
                $journalEntry->transaction_number = $transactionNumber;
                $journalEntry->payment_detail_id = $paymentDetailId;
                $journalEntry->location_id = $currentTransaction->location_id;
                $journalEntry->currency_id = $business->currency_id;
                $journalEntry->chart_of_account_id = $entry['chart']->id;
                $journalEntry->transaction_type = 'auto_map_sell_transaction_to_journal_entry';
                $journalEntry->date = $entryDate;
                $journalEntry->month = $month;
                $journalEntry->year = $year;
                $journalEntry->debit = $entry['debit'] > 0 ? $entry['debit'] : 0;
                $journalEntry->credit = $entry['credit'] > 0 ? $entry['credit'] : 0;
                $journalEntry->manual_entry = 0;
                $journalEntry->notes = 'Auto-mapped by accounting integration';
                $journalEntry->save();

                if (empty($firstJournalEntryId)) {
                    $firstJournalEntryId = $journalEntry->id;
                }
            }

            if (! empty($firstJournalEntryId)) {
                $currentTransaction->journal_entry_id = $firstJournalEntryId;
                $currentTransaction->save();
            }
        });

        return true;
    }

    protected function resolveChartOfAccountForMapping(string $mappingKey, int $businessId): ?ChartOfAccount
    {
        $accountId = TransactionPayment::resolveDefaultAccountMapping($mappingKey, $businessId);
        if (empty($accountId)) {
            return null;
        }

        $account = Account::select('id', 'account_number')
            ->where('business_id', $businessId)
            ->find($accountId);

        if (empty($account) || empty($account->account_number)) {
            return null;
        }

        return ChartOfAccount::where('business_id', $businessId)
            ->where('active', 1)
            ->where('gl_code', (int) $account->account_number)
            ->first();
    }

    protected function calculateSellCogsAmount(int $transactionId): float
    {
        $amount = (float) DB::table('transaction_sell_lines as tsl')
            ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->where('tsl.transaction_id', $transactionId)
            ->where(function ($query) {
                $query->whereNull('tsl.children_type')
                    ->orWhere('tsl.children_type', '!=', 'combo');
            })
            ->sum(DB::raw('(tsl.quantity - COALESCE(tsl.quantity_returned, 0)) * COALESCE(NULLIF(v.dpp_inc_tax, 0), v.default_purchase_price, 0)'));

        return round(max(0, $amount), 4);
    }

    protected function sellJournalNeedsSync($transaction, ?JournalEntry $linkedJournalEntry): bool
    {
        if (empty($linkedJournalEntry)) {
            return true;
        }

        $groupEntries = JournalEntry::where('transaction_number', $linkedJournalEntry->transaction_number)->get();
        if ($groupEntries->isEmpty()) {
            return true;
        }

        $hasReceivable = false;
        $hasRevenue = false;
        $hasCogs = false;
        $hasInventory = false;

        foreach ($groupEntries as $entry) {
            $chartId = (int) $entry->chart_of_account_id;
            if ($chartId === (int) ($this->resolveChartOfAccountForMapping('accounts_receivable', (int) $transaction->business_id)->id ?? 0) && (float) $entry->debit > 0) {
                $hasReceivable = true;
            }
            if ($chartId === (int) ($this->resolveChartOfAccountForMapping('sell', (int) $transaction->business_id)->id ?? 0) && (float) $entry->credit > 0) {
                $hasRevenue = true;
            }
            if ($chartId === (int) ($this->resolveChartOfAccountForMapping('cogs', (int) $transaction->business_id)->id ?? 0) && (float) $entry->debit > 0) {
                $hasCogs = true;
            }
            if ($chartId === (int) ($this->resolveChartOfAccountForMapping('inventory', (int) $transaction->business_id)->id ?? 0) && (float) $entry->credit > 0) {
                $hasInventory = true;
            }
        }

        $expectedCogs = $this->calculateSellCogsAmount((int) $transaction->id);

        if (! $hasReceivable || ! $hasRevenue) {
            return true;
        }

        if ($expectedCogs > 0 && (! $hasCogs || ! $hasInventory)) {
            return true;
        }

        return false;
    }

    protected function canSync()
    {
        return Schema::hasTable('chart_of_accounts')
            && Schema::hasTable('journal_entries')
            && Schema::hasTable('payment_details')
            && Schema::hasColumn('transactions', 'journal_entry_id');
    }
}
