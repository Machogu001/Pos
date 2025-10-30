<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Variation extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'combo_variations' => 'array',
    ];

    public function product_variation()
    {
        return $this->belongsTo(\App\ProductVariation::class);
    }

    public function product()
    {
        return $this->belongsTo(\App\Product::class, 'product_id');
    }

    public function sell_lines()
    {
        return $this->hasMany(\App\TransactionSellLine::class);
    }

    public function variation_location_details()
    {
        return $this->hasMany(\App\VariationLocationDetails::class);
    }

    public function group_prices()
    {
        return $this->hasMany(\App\VariationGroupPrice::class, 'variation_id');
    }

    public function media()
    {
        return $this->morphMany(\App\Media::class, 'model');
    }

    /**
     * Optional self-reference to fix missing 'variation' relationship usage.
     * Use this only if something is calling `$variation->variation`.
     */
    public function variation()
    {
        return $this; // or return $this->belongsTo(self::class, 'id');
    }

    public function getFullNameAttribute()
    {
        $name = $this->product->name;
        if ($this->product->type == 'variable') {
            $name .= ' - '.$this->product_variation->name.' - '.$this->name;
        }
        $name .= ' ('.$this->sub_sku.')';

        return $name;
    }
}
