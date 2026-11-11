<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BusinessCentralMapping extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];
}
