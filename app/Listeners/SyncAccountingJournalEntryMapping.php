<?php

namespace App\Listeners;

use App\Account;
use App\Business;
use App\Events\ExpenseCreatedOrModified;
use App\Events\PurchaseCreatedOrModified;
use App\Events\SellCreatedOrModified;
use App\TransactionPayment;
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

        return $this->syncTransaction($transaction, 'sell', 'credit', 'payment');
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

    protected function canSync()
    {
        return Schema::hasTable('chart_of_accounts')
            && Schema::hasTable('journal_entries')
            && Schema::hasTable('payment_details')
            && Schema::hasColumn('transactions', 'journal_entry_id');
    }
}
