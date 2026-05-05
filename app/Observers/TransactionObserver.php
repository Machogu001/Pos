<?php

namespace App\Observers;

use App\Events\ExpenseCreatedOrModified;
use App\Transaction;

class TransactionObserver
{
    /**
     * Auto-sync accounting integrations for expenses created from any code path.
     */
    public function created(Transaction $transaction): void
    {
        if ($this->isExpenseTransaction($transaction)) {
            event(new ExpenseCreatedOrModified($transaction));
        }
    }

    /**
     * Keep accounting entries in sync when core expense fields are edited.
     */
    public function updated(Transaction $transaction): void
    {
        if (! $this->isExpenseTransaction($transaction)) {
            return;
        }

        $relevantChanges = [
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

        if ($transaction->wasChanged($relevantChanges)) {
            event(new ExpenseCreatedOrModified($transaction));
        }
    }

    /**
     * Remove related accounting records when expense transactions are deleted.
     */
    public function deleted(Transaction $transaction): void
    {
        if ($this->isExpenseTransaction($transaction)) {
            event(new ExpenseCreatedOrModified($transaction, true));
        }
    }

    protected function isExpenseTransaction(Transaction $transaction): bool
    {
        return in_array($transaction->type, ['expense', 'expense_refund'], true);
    }
}
