<?php

namespace Modules\Connector\Entities;

use Illuminate\Database\Eloquent\Model;

class ConnectorApiToken extends Model
{
    protected $table = 'connector_api_tokens';

    protected $fillable = [
        'business_id',
        'user_id',
        'token',
        'description',
        'is_active',
        'last_used_at',
        'expires_at',
    ];

    protected $hidden = ['token'];

    protected $casts = [
        'is_active'    => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];
}
