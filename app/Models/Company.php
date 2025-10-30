<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        "name",'email','phone','country','business_id'
    ];

    /**
     * Linked business (if this company represents a business)
     */
    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }



}
