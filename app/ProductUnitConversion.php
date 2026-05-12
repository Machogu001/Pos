<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductUnitConversion extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'qty_per_base' => 'float',
        'is_purchase_default' => 'boolean',
        'is_sale_default' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
