<?php

namespace Modules\Manufacturing\Entities;

use Illuminate\Database\Eloquent\Model;

class ManufacturingRecipe extends Model
{
    protected $table = 'manufacturing_recipes';

    protected $fillable = [
        'business_id',
        'product_id',
        'variation_id',
        'quantity',
        'notes',
    ];

    public function product()
    {
        return $this->belongsTo(\App\Product::class, 'product_id');
    }

    public function productions()
    {
        return $this->hasMany(ManufacturingProduction::class, 'recipe_id');
    }
}
