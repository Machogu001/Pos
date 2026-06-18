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
        'payroll_expense_account_id',
        'payroll_clearing_account_id',
        'payroll_auto_post',
        // HRM defaults
        'default_annual_leave',
        'hrm_theme',
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
    ,'subscription_round_precision',
    // Subscription-specific M-Pesa credentials
    'subscription_mpesa_consumer_key',
    'subscription_mpesa_consumer_secret',
    'subscription_mpesa_shortcode',
    'subscription_mpesa_passkey',
    'subscription_mpesa_callback',
    'subscription_mpesa_shortcode_type',  // 'paybill' or 'till'
    'subscription_mpesa_store_number',    // For Till: PartyB store/head-office number
    // eTIMS Integration Settings
    'etims_api_url',
    'etims_api_token',
    'etims_branch_id',
    'etims_auto_transmit',
        'auto_close_register',
        'auto_close_register_time',
        'accounting_backfill_enabled',
        'accounting_backfill_frequency',
        'accounting_backfill_time',
        'accounting_backfill_last_run_at',
        'stock_costing_backfill_enabled',
        'stock_costing_backfill_frequency',
        'stock_costing_backfill_time',
        'stock_costing_backfill_business_id',
        'stock_costing_backfill_location_id',
        'stock_costing_backfill_last_run_at',
    ];

    protected $casts = [
        'auto_renewal' => 'boolean',
        'subscription_required' => 'boolean',
        'payroll_auto_post' => 'boolean',
        'etims_auto_transmit' => 'boolean',
        'auto_close_register' => 'boolean',
        'accounting_backfill_enabled' => 'boolean',
        'accounting_backfill_last_run_at' => 'datetime',
        'stock_costing_backfill_enabled' => 'boolean',
        'stock_costing_backfill_last_run_at' => 'datetime',
    ];
}