<?php

namespace App\Observers;

use App\Events\SellCreatedOrModified;
use App\Transaction;
use App\TransactionSellLine;

class TransactionSellLineObserver
{
    public function created(TransactionSellLine $sellLine): void
    {
        $this->syncFinalSell($sellLine);
    }

    public function updated(TransactionSellLine $sellLine): void
    {
        $this->syncFinalSell($sellLine);
    }

    public function deleted(TransactionSellLine $sellLine): void
    {
        $this->syncFinalSell($sellLine);
    }

    protected function syncFinalSell(TransactionSellLine $sellLine): void
    {
        $transaction = $sellLine->relationLoaded('transaction')
            ? $sellLine->transaction
            : Transaction::find($sellLine->transaction_id);

        if (
            empty($transaction)
            || $transaction->type !== 'sell'
            || $transaction->status !== 'final'
            || $transaction->sub_type === 'subscription_invoice'
        ) {
            return;
        }

        event(new SellCreatedOrModified($transaction));
    }
}
