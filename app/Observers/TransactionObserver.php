<?php

namespace App\Observers;

use App\Events\ExpenseCreatedOrModified;
use App\Events\SellCreatedOrModified;
use App\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionObserver
{
    /**
     * Auto-sync accounting integrations for expenses created from any code path.
     */
    public function created(Transaction $transaction): void
    {
        if ($this->isExpenseTransaction($transaction)) {
            DB::afterCommit(function () use ($transaction) {
                event(new ExpenseCreatedOrModified($transaction->fresh() ?? $transaction));
            });
        }
    }

    /**
     * Keep accounting entries in sync when core expense fields are edited.
     */
    public function updated(Transaction $transaction): void
    {
        $expenseRelevantChanges = [
            'type',
            'status',
            'transaction_date',
            'location_id',
            'expense_category_id',
            'expense_sub_category_id',
            'expense_for',
            'contact_id',
            'final_total',
            'total_before_tax',
            'tax_id',
            'tax_amount',
            'additional_notes',
        ];

        if ($this->isExpenseTransaction($transaction) && $transaction->wasChanged($expenseRelevantChanges)) {
            DB::afterCommit(function () use ($transaction) {
                event(new ExpenseCreatedOrModified($transaction->fresh() ?? $transaction));
            });
        }

        $sellRelevantChanges = [
            'type',
            'status',
            'payment_status',
            'transaction_date',
            'location_id',
            'contact_id',
            'final_total',
            'total_before_tax',
            'tax_id',
            'tax_amount',
            'discount_type',
            'discount_amount',
            'shipping_charges',
            'round_off_amount',
            'rp_redeemed_amount',
            'additional_notes',
        ];

        if ($this->isFinalSellTransaction($transaction) && $transaction->wasChanged($sellRelevantChanges)) {
            DB::afterCommit(function () use ($transaction) {
                event(new SellCreatedOrModified($transaction->fresh() ?? $transaction));
            });
        }
    }

    /**
     * Remove related accounting records when expense transactions are deleted.
     */
    public function deleted(Transaction $transaction): void
    {
        if ($this->isExpenseTransaction($transaction)) {
            DB::afterCommit(function () use ($transaction) {
                event(new ExpenseCreatedOrModified($transaction, true));
            });
        }
    }

    protected function isExpenseTransaction(Transaction $transaction): bool
    {
        return in_array($transaction->type, ['expense', 'expense_refund'], true);
    }

    protected function isFinalSellTransaction(Transaction $transaction): bool
    {
        return $transaction->type === 'sell'
            && $transaction->status === 'final'
            && $transaction->sub_type !== 'subscription_invoice';
    }
}
