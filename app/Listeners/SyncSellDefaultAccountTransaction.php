<?php

namespace App\Listeners;

use App\AccountTransaction;
use App\Events\SellCreatedOrModified;
use App\TransactionPayment;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncSellDefaultAccountTransaction
{
    protected $moduleUtil;

    protected $businessUtil;

    public function __construct(ModuleUtil $moduleUtil, BusinessUtil $businessUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->businessUtil = $businessUtil;
    }

    public function handle(SellCreatedOrModified $event)
    {
        $transaction = $event->transaction;

        $skipReason = null;

        if (empty($transaction)) {
            $skipReason = 'empty_transaction';
        } elseif ($transaction->type !== 'sell') {
            $skipReason = 'not_sell_type';
        } elseif ($transaction->status !== 'final') {
            $skipReason = 'status_not_final';
        } elseif ($transaction->sub_type === 'subscription_invoice') {
            $skipReason = 'subscription_invoice';
        } elseif (! $this->moduleUtil->isModuleEnabled('account', $transaction->business_id)) {
            $skipReason = 'module_disabled';
        }

        if (! empty($skipReason)) {
            Log::debug('SyncSellDefaultAccountTransaction::handle skipped', [
                'transaction_id' => $transaction->id ?? null,
                'business_id' => $transaction->business_id ?? null,
                'reason' => $skipReason,
                'type' => $transaction->type ?? null,
                'status' => $transaction->status ?? null,
                'sub_type' => $transaction->sub_type ?? null,
            ]);
            return true;
        }

        $operationDate = $transaction->transaction_date;
        $createdBy = $transaction->created_by;
        $note = $transaction->ref_no;

        // Ensure the business has the core accounting mappings needed for sell postings.
        $this->businessUtil->provisionDefaultAccountMappings($transaction->business_id, $createdBy);

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
        $currentUnitCost = (float) DB::table('transaction_sell_lines as tsl')
            ->join('variations as v', 'tsl.variation_id', '=', 'v.id')
            ->where('tsl.transaction_id', $transactionId)
            ->where(function ($query) {
                $query->whereNull('tsl.children_type')
                    ->orWhere('tsl.children_type', '!=', 'combo');
            })
            ->sum(DB::raw('(tsl.quantity - COALESCE(tsl.quantity_returned, 0)) * COALESCE(NULLIF(v.dpp_inc_tax, 0), v.default_purchase_price, 0)'));

        return round(max(0, $currentUnitCost), 4);
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
            Log::debug('SyncSellDefaultAccountTransaction::syncEntry skipped', [
                'transaction_id' => $transaction->id,
                'business_id' => $transaction->business_id,
                'posting' => $subType,
                'mapping_key' => $mappingKey,
                'reason' => empty($accountId) ? 'missing_mapping' : 'zero_amount',
                'account_id' => $accountId,
                'amount' => $amount,
                'deleted_existing' => ! empty($accountTransaction),
            ]);

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