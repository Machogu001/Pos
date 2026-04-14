<?php

namespace App\Listeners;

use App\AccountTransaction;
use App\Events\ExpenseCreatedOrModified;
use App\TransactionPayment;
use App\Utils\ModuleUtil;

class SyncExpenseDefaultAccountTransaction
{
    protected $moduleUtil;

    protected $subTypes = [
        'expense_invoice_payable',
        'expense_invoice_expense',
        'expense_invoice_tax',
        'expense_refund_invoice_payable',
        'expense_refund_invoice_expense',
        'expense_refund_invoice_tax',
    ];

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Keep invoice-level expense postings in sync with the expense.
     */
    public function handle(ExpenseCreatedOrModified $event)
    {
        $transaction = $event->expense;

        if (
            empty($transaction)
            || ! in_array($transaction->type, ['expense', 'expense_refund'])
            || ! $this->moduleUtil->isModuleEnabled('account', $transaction->business_id)
        ) {
            return true;
        }

        $accountTransactionQuery = AccountTransaction::where('transaction_id', $transaction->id)
            ->whereNull('transaction_payment_id')
            ->whereIn('sub_type', $this->subTypes);

        if ($event->isDeleted) {
            $accountTransactionQuery->delete();

            return true;
        }

        $operationDate = $transaction->transaction_date;
        $createdBy = $transaction->created_by;
        $note = $transaction->ref_no;
        $amountBeforeTax = (float) (! is_null($transaction->total_before_tax)
            ? $transaction->total_before_tax
            : ((float) $transaction->final_total - (float) $transaction->tax_amount));
        $taxAmount = (float) $transaction->tax_amount;
        $isRefund = $transaction->type === 'expense_refund';

        $subTypePrefix = $isRefund ? 'expense_refund_invoice' : 'expense_invoice';
        $payableType = $isRefund ? 'debit' : 'credit';
        $expenseType = $isRefund ? 'credit' : 'debit';
        $taxType = $isRefund ? 'credit' : 'debit';

        $this->syncEntry(
            $transaction,
            $subTypePrefix.'_payable',
            'purchase',
            (float) $transaction->final_total,
            $payableType,
            $operationDate,
            $createdBy,
            $note
        );

        $this->syncEntry(
            $transaction,
            $subTypePrefix.'_expense',
            'expense',
            $amountBeforeTax,
            $expenseType,
            $operationDate,
            $createdBy,
            $note
        );

        $this->syncEntry(
            $transaction,
            $subTypePrefix.'_tax',
            'purchase_tax',
            $taxAmount,
            $taxType,
            $operationDate,
            $createdBy,
            $note
        );

        return true;
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