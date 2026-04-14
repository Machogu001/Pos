<?php

namespace App\Listeners;

use App\AccountTransaction;
use App\Events\SellCreatedOrModified;
use App\TransactionPayment;
use App\Utils\ModuleUtil;
use Illuminate\Support\Facades\DB;

class SyncSellDefaultAccountTransaction
{
    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    public function handle(SellCreatedOrModified $event)
    {
        $transaction = $event->transaction;

        if (
            empty($transaction)
            || $transaction->type !== 'sell'
            || $transaction->status !== 'final'
            || ! $this->moduleUtil->isModuleEnabled('account', $transaction->business_id)
        ) {
            return true;
        }

        $operationDate = $transaction->transaction_date;
        $createdBy = $transaction->created_by;
        $note = $transaction->ref_no;
        $amountBeforeTax = (float) (! is_null($transaction->total_before_tax)
            ? $transaction->total_before_tax
            : ((float) $transaction->final_total - (float) $transaction->tax_amount));
        $taxAmount = (float) $transaction->tax_amount;
        $cogsAmount = $this->getCogsAmount($transaction->id);

        $this->syncEntry(
            $transaction,
            'sell_invoice_receivable',
            'accounts_receivable',
            (float) $transaction->final_total,
            'debit',
            $operationDate,
            $createdBy,
            $note
        );

        $this->syncEntry(
            $transaction,
            'sell_invoice_income',
            'sell',
            $amountBeforeTax,
            'credit',
            $operationDate,
            $createdBy,
            $note
        );

        $this->syncEntry(
            $transaction,
            'sell_invoice_tax',
            'sales_tax',
            $taxAmount,
            'credit',
            $operationDate,
            $createdBy,
            $note
        );

        $this->syncEntry(
            $transaction,
            'sell_invoice_cogs',
            'cogs',
            $cogsAmount,
            'debit',
            $operationDate,
            $createdBy,
            $note
        );

        $this->syncEntry(
            $transaction,
            'sell_invoice_inventory',
            'inventory',
            $cogsAmount,
            'credit',
            $operationDate,
            $createdBy,
            $note
        );

        return true;
    }

    protected function getCogsAmount($transactionId)
    {
        $linkedCost = (float) DB::table('transaction_sell_lines_purchase_lines as tspl')
            ->join('purchase_lines as pl', 'tspl.purchase_line_id', '=', 'pl.id')
            ->join('transaction_sell_lines as tsl', 'tspl.sell_line_id', '=', 'tsl.id')
            ->where('tsl.transaction_id', $transactionId)
            ->sum(DB::raw('(tspl.quantity - tspl.qty_returned) * (pl.purchase_price + COALESCE(pl.item_tax, 0))'));

        $fallbackCost = (float) DB::table('transaction_sell_lines as tsl')
            ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->where('tsl.transaction_id', $transactionId)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('transaction_sell_lines_purchase_lines as tspl')
                    ->whereColumn('tspl.sell_line_id', 'tsl.id');
            })
            ->sum(DB::raw('(tsl.quantity - COALESCE(tsl.quantity_returned, 0)) * COALESCE(NULLIF(v.dpp_inc_tax, 0), v.default_purchase_price, 0)'));

        return round($linkedCost + $fallbackCost, 4);
    }

    protected function syncEntry($transaction, $subType, $mappingKey, $amount, $type, $operationDate, $createdBy, $note)
    {
        $accountTransaction = AccountTransaction::where('transaction_id', $transaction->id)
            ->whereNull('transaction_payment_id')
            ->where('reff_no', $subType)
            ->first();

        $accountId = TransactionPayment::resolveDefaultAccountMapping($mappingKey, $transaction->business_id);
        $amount = round((float) $amount, 4);

        if (empty($accountId) || $amount <= 0) {
            if (! empty($accountTransaction)) {
                $accountTransaction->delete();
            }

            return null;
        }

        $data = [
            'amount' => $amount,
            'account_id' => $accountId,
            'type' => $type,
            'reff_no' => $subType,
            'operation_date' => $operationDate,
            'created_by' => $createdBy,
            'transaction_id' => $transaction->id,
            'note' => $note,
        ];

        if (! empty($accountTransaction)) {
            $accountTransaction->amount = $data['amount'];
            $accountTransaction->account_id = $data['account_id'];
            $accountTransaction->type = $data['type'];
            $accountTransaction->reff_no = $data['reff_no'];
            $accountTransaction->operation_date = $data['operation_date'];
            $accountTransaction->created_by = $data['created_by'];
            $accountTransaction->note = $data['note'];
            $accountTransaction->save();

            return $accountTransaction;
        }

        return AccountTransaction::createAccountTransaction($data);
    }
}