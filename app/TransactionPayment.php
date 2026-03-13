<?php

namespace App;

use App\Events\TransactionPaymentDeleted;
use App\Events\TransactionPaymentUpdated;
use App\Transaction;
use Illuminate\Support\Facades\Session;
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
            // Auto-link payments to configured default accounts by transaction type
            // when no explicit account has been chosen.
            try {
                if (empty($payment->account_id) && $payment->method != 'advance' && ! empty($payment->transaction_id)) {
                    $transaction = $payment->transaction ?: Transaction::find($payment->transaction_id);

                    if ($transaction) {
                        $transaction_type = $transaction->type ?? null;

                        // 1) Try per-business mapping from common_settings
                        $business_common = Session::get('business.common_settings', []);
                        if (! empty($transaction_type)
                            && ! empty($business_common['default_account_mappings'])
                            && ! empty($business_common['default_account_mappings'][$transaction_type])) {
                            $payment->account_id = $business_common['default_account_mappings'][$transaction_type];
                        } else {
                            // 2) Fallback to global config mapping (env-based)
                            $mappings = config('constants.default_account_mappings', []);
                            if (! empty($transaction_type) && ! empty($mappings[$transaction_type])) {
                                $payment->account_id = $mappings[$transaction_type];
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to set default account on TransactionPayment creating: '.$e->getMessage());
            }
        });
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
}
