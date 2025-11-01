<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PwaEvent extends Model
{
    protected $table = 'pwa_events';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
    ];
}
