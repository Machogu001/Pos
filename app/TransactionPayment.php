<?php

namespace App;

use App\Events\TransactionPaymentDeleted;
use App\Events\TransactionPaymentUpdated;
use App\Business;
use App\Transaction;
use App\BusinessLocation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class TransactionPayment extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            try {
                if (empty($payment->account_id) && $payment->method != 'advance' && ! empty($payment->transaction_id)) {
                    $transaction = $payment->transaction ?: Transaction::find($payment->transaction_id);

                    if ($transaction) {
                        $payment->account_id = static::resolveDefaultAccountId(
                            $payment->method,
                            $transaction->location ?? null,
                            $transaction->business_id ?? null,
                            $transaction->type ?? null
                        );
                    }
                } elseif (empty($payment->account_id) && $payment->method != 'advance') {
                    $payment->account_id = static::resolveDefaultAccountId(
                        $payment->method,
                        null,
                        $payment->business_id ?? null
                    );
                }
            } catch (\Exception $e) {
                Log::error('Failed to set default account on TransactionPayment creating: '.$e->getMessage());
            }
        });
    }

    /**
     * Resolve the default payment account for a payment method.
     *
     * @param  string|null  $method
     * @param  \App\BusinessLocation|int|null  $location
     * @param  int|null  $business_id
     * @param  string|null  $transactionType
     * @return int|null
     */
    public static function resolveDefaultAccountId($method, $location = null, $business_id = null, $transactionType = null)
    {
        if (empty($method) || $method === 'advance') {
            return null;
        }

        if (empty($business_id) && auth()->check()) {
            $business_id = auth()->user()->business_id;
        }

        if (is_numeric($location)) {
            $location = BusinessLocation::find($location);
        }

        if (empty($location) && auth()->check()) {
            $location = auth()->user()->getDefaultLocation();
        }

        if (empty($location) && ! empty($business_id)) {
            $location = BusinessLocation::where('business_id', $business_id)
                ->where('is_active', 1)
                ->first();
        }

        if (empty($location) || empty($location->default_payment_accounts)) {
            $paymentAccountId = static::resolveDefaultAccountMapping('payment', $business_id);

            if (! empty($paymentAccountId)) {
                return $paymentAccountId;
            }

            return static::resolveTransactionTypeDefaultAccountId($transactionType, $business_id);
        }

        $default_payment_accounts = json_decode($location->default_payment_accounts, true) ?: [];
        if (! empty($default_payment_accounts[$method]['is_enabled']) && ! empty($default_payment_accounts[$method]['account'])) {
            return (int) $default_payment_accounts[$method]['account'];
        }

        $paymentAccountId = static::resolveDefaultAccountMapping('payment', $business_id);

        if (! empty($paymentAccountId)) {
            return $paymentAccountId;
        }

        return static::resolveTransactionTypeDefaultAccountId($transactionType, $business_id);
    }

    /**
     * Resolve a business-level default account by transaction type.
     * Used as a fallback when no location/payment-method account is configured,
     * and for invoice-level postings such as vendor payables.
     *
     * @param  string|null  $transactionType
     * @param  int|null  $business_id
     * @return int|null
     */
    public static function resolveTransactionTypeDefaultAccountId($transactionType, $business_id = null)
    {
        if (empty($transactionType)) {
            return null;
        }

        $mappingKey = [
            'expense_refund' => 'expense',
            'purchase_return' => 'purchase',
            'sell_return' => 'sell',
        ][$transactionType] ?? $transactionType;

        $accountId = static::resolveDefaultAccountMapping($mappingKey, $business_id);

        if (! empty($accountId)) {
            return $accountId;
        }

        return static::resolveDefaultAccountMapping('payment', $business_id);
    }

    /**
     * Resolve a business-level default account by mapping key.
     *
     * @param  string|null  $mappingKey
     * @param  int|null  $business_id
     * @return int|null
     */
    public static function resolveDefaultAccountMapping($mappingKey, $business_id = null)
    {
        if (empty($mappingKey)) {
            return null;
        }

        $mappingKey = [
            'expense_refund' => 'expense',
            'purchase_return' => 'purchase',
            'sell_return' => 'sell',
        ][$mappingKey] ?? $mappingKey;

        $mappingFallbacks = [
            'purchase_tax' => ['tax'],
            'sales_tax' => ['tax'],
        ];

        if (empty($business_id) && auth()->check()) {
            $business_id = auth()->user()->business_id;
        }

        if (! empty($business_id)) {
            $business = Business::select('id', 'common_settings')->find($business_id);
            $typeMappings = ! empty($business->common_settings['default_account_mappings'])
                ? $business->common_settings['default_account_mappings']
                : [];

            if (! empty($typeMappings[$mappingKey])) {
                return (int) $typeMappings[$mappingKey];
            }

            foreach ($mappingFallbacks[$mappingKey] ?? [] as $fallbackKey) {
                if (! empty($typeMappings[$fallbackKey])) {
                    return (int) $typeMappings[$fallbackKey];
                }
            }
        }

        $defaultMapping = config('constants.default_account_mappings.'.$mappingKey);

        if (! empty($defaultMapping)) {
            return (int) $defaultMapping;
        }

        foreach ($mappingFallbacks[$mappingKey] ?? [] as $fallbackKey) {
            $defaultFallbackMapping = config('constants.default_account_mappings.'.$fallbackKey);

            if (! empty($defaultFallbackMapping)) {
                return (int) $defaultFallbackMapping;
            }
        }

        return null;
    }

    /**
     * Get the phone record associated with the user.
     */
    public function payment_account()
    {
        return $this->belongsTo(\App\Account::class, 'account_id');
    }

    /**
     * Get the transaction related to this payment.
     */
    public function transaction()
    {
        return $this->belongsTo(\App\Transaction::class, 'transaction_id');
    }

    /**
     * Get the user.
     */
    public function created_user()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    /**
     * Get child payments
     */
    public function child_payments()
    {
        return $this->hasMany(\App\TransactionPayment::class, 'parent_id');
    }

    /**
     * Retrieves documents path if exists
     */
    public function getDocumentPathAttribute()
    {
        $path = ! empty($this->document) ? asset('/uploads/documents/'.$this->document) : null;

        return $path;
    }

    /**
     * Removes timestamp from document name
     */
    public function getDocumentNameAttribute()
    {
        $document_name = ! empty(explode('_', $this->document, 2)[1]) ? explode('_', $this->document, 2)[1] : $this->document;

        return $document_name;
    }

    public static function deletePayment($payment)
    {
        //Update parent payment if exists
        if (! empty($payment->parent_id)) {
            $parent_payment = TransactionPayment::find($payment->parent_id);
            $parent_payment->amount -= $payment->amount;

            if ($parent_payment->amount <= 0) {
                $parent_payment->delete();
                event(new TransactionPaymentDeleted($parent_payment));
            } else {
                $parent_payment->save();
                //Add event to update parent payment account transaction
                event(new TransactionPaymentUpdated($parent_payment, null));
            }
        }

        $payment->delete();

        $transactionUtil = new \App\Utils\TransactionUtil();

        if (! empty($payment->transaction_id)) {
            //update payment status
            $transaction = $payment->load('transaction')->transaction;
            $transaction_before = $transaction->replicate();

            $payment_status = $transactionUtil->updatePaymentStatus($payment->transaction_id);

            $transaction->payment_status = $payment_status;

            $transactionUtil->activityLog($transaction, 'payment_edited', $transaction_before);
        }

        $log_properities = [
            'id' => $payment->id,
            'ref_no' => $payment->payment_ref_no,
        ];
        $transactionUtil->activityLog($payment, 'payment_deleted', null, $log_properities);

        //Add event to delete account transaction
        event(new TransactionPaymentDeleted($payment));
    }

    public function denominations()
    {
        return $this->morphMany(\App\CashDenomination::class, 'model');
    }

    /**
     * Account transactions created from this payment.
     */
    public function account_transactions()
    {
        return $this->hasMany(\App\AccountTransaction::class, 'transaction_payment_id');
    }
}
