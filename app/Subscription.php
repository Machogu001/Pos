<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_name',
        'billing_cycle',
        'amount',
        'start_date',
        'end_date',
        'status',
        'mpesa_receipt',
        'checkout_request_id',
        'activated_at',
        'is_renewal',
        'previous_subscription_id',
        'cancelled_at',
        // tracking / linking fields added for automated invoicing
        'invoice_sent_at',
        'reminder_sent_at',
        'pending_invoice_transaction_id',
        'pending_mpesa_payment_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payment()
    {
        return $this->hasOne(MpesaPayment::class);
    }

    public function payments()
    {
        return $this->hasMany(MpesaPayment::class);
    }

    public function previousSubscription()
    {
        return $this->belongsTo(Subscription::class, 'previous_subscription_id');
    }

    public function renewals()
    {
        return $this->hasMany(Subscription::class, 'previous_subscription_id');
    }
}