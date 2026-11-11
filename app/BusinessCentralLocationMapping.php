<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BusinessCentralLocationMapping extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'enabled' => 'boolean',
        'last_inventory_sync_at' => 'datetime',
    ];

    public function posLocation()
    {
        return $this->belongsTo(BusinessLocation::class, 'pos_location_id');
    }
}
