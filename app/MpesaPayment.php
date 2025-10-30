<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpesaPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payer_name',
        'first_name',
        'middle_name',
        'last_name',
        'user_id',
        'phone_number',
        'amount',
        'account_reference',
        'consumed_by_transaction_id',
        'consumed_at',
        'payment_type',
        'mpesa_receipt_number',
        'merchant_request_id',
        'checkout_request_id',
        'result_code',
        'result_desc',
        'transaction_status',
        'paid_at',
        'subscription_id', // Make sure this is in fillable
    ];

    /**
     * Get the user that owns the M-Pesa payment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the subscription associated with the payment
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
    public function payment()
{
    return $this->hasOne(MpesaPayment::class);
}

}
