<?php

namespace Modules\Superadmin\Entities;

use Illuminate\Database\Eloquent\Model;

class UpdateClient extends Model
{
    protected $table    = 'superadmin_update_clients';
    protected $fillable = [
        'name', 'url', 'webhook_secret',
        'last_version', 'last_push_status', 'last_pushed_at', 'is_active',
    ];
    protected $hidden = ['webhook_secret'];
    protected $casts  = [
        'is_active'      => 'boolean',
        'last_pushed_at' => 'datetime',
    ];
}
