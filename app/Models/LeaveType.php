<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'name',
        'title',
        'allowed_days',
        'description',
    ];

    public function setTitleAttribute($value): void
    {
        $this->attributes['name'] = $value;
    }

    public function getTitleAttribute(): ?string
    {
        return $this->attributes['name'] ?? null;
    }
}
