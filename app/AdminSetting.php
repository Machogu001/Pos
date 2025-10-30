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
        'grace_period_days',
        'recent_limit'
    ];

    protected $casts = [
        'auto_renewal' => 'boolean',
    ];
}