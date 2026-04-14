<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MpesaPayment extends Model
{
    use HasFactory;

    public const TYPE_REGISTRATION = 'registration';
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_SELL = 'sell';
    public const TYPE_SUBSCRIPTION = 'subscription';

    protected $fillable = [
        'payer_name',
        'first_name',
        'middle_name',
        'last_name',
        'user_id',
        'business_id',
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

    protected static function booted(): void
    {
        static::saving(function (self $payment) {
            if (! empty($payment->phone_number)) {
                $payment->phone_number = self::normalizePhoneNumber($payment->phone_number) ?? $payment->phone_number;
            }
            $payment->payment_type = self::normalizePaymentType($payment);
        });
    }

    public static function normalizePhoneNumber(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', trim($phone));

        if (preg_match('/^(07|01)\d{8}$/', $digits)) {
            return '254' . substr($digits, 1);
        }

        if (preg_match('/^254(7|1)\d{8}$/', $digits)) {
            return $digits;
        }

        return null;
    }

    public static function normalizePaymentType(self $payment): string
    {
        $currentType = strtolower((string) $payment->payment_type);
        if (in_array($currentType, [self::TYPE_REGISTRATION, self::TYPE_PURCHASE, self::TYPE_SELL, self::TYPE_SUBSCRIPTION], true)) {
            return $currentType;
        }

        $accountReference = strtoupper((string) $payment->account_reference);

        if (! empty($payment->subscription_id) || str_starts_with($accountReference, 'SUB') || str_starts_with($accountReference, 'RENEW')) {
            return self::TYPE_SUBSCRIPTION;
        }

        if (! empty($payment->consumed_by_transaction_id)) {
            return self::TYPE_SELL;
        }

        if (! empty($payment->user_id) && empty($payment->business_id)) {
            return self::TYPE_SELL;
        }

        return self::TYPE_REGISTRATION;
    }

    /**
     * Get the user that owns the M-Pesa payment
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
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
