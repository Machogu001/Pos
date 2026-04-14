<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class AccountTransaction extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'operation_date' => 'datetime',
    ];

    public function media()
    {
        return $this->morphMany(\App\Media::class, 'model');
    }

    public function transaction()
    {
        return $this->belongsTo(\App\Transaction::class, 'transaction_id');
    }

    /**
     * Gives account transaction type from payment transaction type
     *
     * @param  string  $payment_transaction_type
     * @return string
     */
    public static function getAccountTransactionType($tansaction_type)
    {
        $account_transaction_types = [
            'sell' => 'credit',
            'purchase' => 'debit',
            'expense' => 'debit',
            'purchase_return' => 'credit',
            'sell_return' => 'debit',
            'payroll' => 'debit',
            'expense_refund' => 'credit',
            'hms_booking' => 'credit',
        ];

        return $account_transaction_types[$tansaction_type];
    }

    /**
     * Creates new account transaction
     *
     * @return obj
     */
    public static function createAccountTransaction($data)
    {
        $transaction_data = [
            'amount' => $data['amount'],
            'account_id' => $data['account_id'],
            'type' => $data['type'],
            'sub_type' => ! empty($data['sub_type']) ? $data['sub_type'] : null,
            'reff_no' => ! empty($data['reff_no']) ? $data['reff_no'] : null,
            'operation_date' => ! empty($data['operation_date']) ? $data['operation_date'] : \Carbon::now(),
            'created_by' => $data['created_by'],
            'transaction_id' => ! empty($data['transaction_id']) ? $data['transaction_id'] : null,
            'transaction_payment_id' => ! empty($data['transaction_payment_id']) ? $data['transaction_payment_id'] : null,
            'note' => ! empty($data['note']) ? $data['note'] : null,
            'transfer_transaction_id' => ! empty($data['transfer_transaction_id']) ? $data['transfer_transaction_id'] : null,
        ];

        $account_transaction = AccountTransaction::create($transaction_data);

        return $account_transaction;
    }

    /**
     * Updates transaction payment from transaction payment
     *
     * @param  obj  $transaction_payment
     * @param  array  $inputs
     * @param  string  $transaction_type
     * @return string
     */
    public static function updateAccountTransaction($transaction_payment, $transaction_type)
    {
        return self::syncPaymentAccountTransactions($transaction_payment, $transaction_type);
    }

    public static function syncPaymentAccountTransactions($transactionPayment, $transactionType = null, $accountId = null)
    {
        $transactionType = $transactionType ?: optional($transactionPayment->transaction)->type;
        $paymentAccountId = ! empty($accountId) ? $accountId : $transactionPayment->account_id;

        $entries = self::buildPaymentEntries($transactionPayment, $transactionType, $paymentAccountId);
        $existingEntries = self::where('transaction_payment_id', $transactionPayment->id)->get();

        return self::syncTransactionPaymentEntries($transactionPayment, $entries, $existingEntries);
    }

    protected static function buildPaymentEntries($transactionPayment, $transactionType, $paymentAccountId)
    {
        $businessId = $transactionPayment->business_id ?: optional($transactionPayment->transaction)->business_id;
        $amount = round((float) $transactionPayment->amount, 4);

        if ($amount <= 0 || $transactionPayment->method === 'advance') {
            return [];
        }

        if ($transactionType === 'sell') {
            return array_values(array_filter([
                self::makePaymentEntry($paymentAccountId, 'debit', 'sell_payment_account', $amount),
                self::makePaymentEntry(
                    TransactionPayment::resolveDefaultAccountMapping('accounts_receivable', $businessId),
                    'credit',
                    'sell_payment_receivable',
                    $amount
                ),
            ]));
        }

        if ($transactionType === 'purchase') {
            return array_values(array_filter([
                self::makePaymentEntry($paymentAccountId, 'credit', 'purchase_payment_account', $amount),
                self::makePaymentEntry(
                    TransactionPayment::resolveDefaultAccountMapping('purchase', $businessId),
                    'debit',
                    'purchase_payment_payable',
                    $amount
                ),
            ]));
        }

        $type = empty($transactionType)
            ? $transactionPayment->payment_type
            : self::getAccountTransactionType($transactionType);

        if (! empty($transactionPayment->transaction) && $transactionPayment->transaction->type == 'sell' && $transactionPayment->is_return == 1) {
            $type = 'debit';
        }

        $legacyEntry = self::makePaymentEntry($paymentAccountId, $type, null, $amount);

        return empty($legacyEntry) ? [] : [$legacyEntry];
    }

    protected static function syncTransactionPaymentEntries($transactionPayment, array $entries, Collection $existingEntries)
    {
        $existingBySubType = $existingEntries->keyBy(function ($entry) {
            return ! empty($entry->note) ? $entry->note : '__legacy__';
        });

        $keepIds = [];

        foreach ($entries as $entry) {
            $entryKey = $entry['note'] ?? '__legacy__';
            $accountTransaction = $existingBySubType->get($entryKey);
            $payload = [
                'amount' => $entry['amount'],
                'account_id' => $entry['account_id'],
                'type' => $entry['type'],
                'operation_date' => $transactionPayment->paid_on,
                'created_by' => $transactionPayment->created_by,
                'transaction_id' => $transactionPayment->transaction_id,
                'transaction_payment_id' => $transactionPayment->id,
                'note' => $entry['note'] ?? null,
            ];

            if (! empty($accountTransaction)) {
                $accountTransaction->amount = $payload['amount'];
                $accountTransaction->account_id = $payload['account_id'];
                $accountTransaction->type = $payload['type'];
                $accountTransaction->operation_date = $payload['operation_date'];
                $accountTransaction->created_by = $payload['created_by'];
                $accountTransaction->transaction_id = $payload['transaction_id'];
                $accountTransaction->note = $payload['note'];
                $accountTransaction->save();
                $keepIds[] = $accountTransaction->id;
                continue;
            }

            $keepIds[] = self::createAccountTransaction($payload)->id;
        }

        self::where('transaction_payment_id', $transactionPayment->id)
            ->whereNotIn('id', $keepIds)
            ->delete();

        return self::whereIn('id', $keepIds)->get();
    }

    protected static function makePaymentEntry($accountId, $type, $note, $amount)
    {
        if (empty($accountId) || empty($type) || $amount <= 0) {
            return null;
        }

        return [
            'account_id' => $accountId,
            'type' => $type,
            'note' => $note,
            'amount' => $amount,
        ];
    }

    public function transfer_transaction()
    {
        return $this->belongsTo(\App\AccountTransaction::class, 'transfer_transaction_id');
    }

    public function account()
    {
        return $this->belongsTo(\App\Account::class, 'account_id');
    }
}
