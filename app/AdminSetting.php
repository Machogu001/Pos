<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_price',
        'quarterly_price',
        'yearly_price',
        'registration_price',
        'auto_renewal',
        'subscription_required',
        'grace_period_days',
        'recent_limit',
        // HRM defaults
        'default_annual_leave',
        // Company / Invoice fields
        'company_name',
        'company_logo',
        'company_contact_phone',
        'company_contact_email',
        'invoice_pin',
    'invoice_footer',
    'statement_footer',
    // Subscription invoice sequence settings
    'subscription_invoice_prefix',
    'subscription_invoice_next',
    // Subscription VAT percent (0-100)
    'subscription_vat_percent'
    ,'subscription_round_precision'
    ];

    protected $casts = [
        'auto_renewal' => 'boolean',
        'subscription_required' => 'boolean',
    ];
}