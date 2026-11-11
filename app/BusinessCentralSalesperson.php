<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BusinessCentralSalesperson extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'blocked' => 'boolean',
        'available_in_bc' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function posUser()
    {
        return $this->belongsTo(User::class, 'pos_user_id');
    }
}
