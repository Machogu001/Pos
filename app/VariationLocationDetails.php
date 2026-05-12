<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VariationLocationDetails extends Model
{
    protected $table = 'variation_location_details';
    
    protected $guarded = ['id'];
    
    protected $casts = [
        'qty_available' => 'float',
        'reserved_quantity' => 'float'
    ];
    
    // Relationship to variation
    public function variation()
    {
        return $this->belongsTo(Variation::class);
    }
    
    // Relationship to location
    public function location()
    {
        return $this->belongsTo(BusinessLocation::class);
    }
    
    // Accessor for available stock
    public function getAvailableStockAttribute()
    {
        return $this->qty_available - $this->reserved_quantity;
    }

    /**
     * Convert base stock quantity to a larger unit + remainder.
     */
    public function getStockBreakdownByMultiplier($multiplier, $use_available_stock = true)
    {
        $multiplier = (float) $multiplier;
        $base_qty = (float) ($use_available_stock ? $this->available_stock : $this->qty_available);

        if ($multiplier <= 0) {
            return [
                'full_units' => 0,
                'remainder' => $base_qty,
                'base_qty' => $base_qty,
                'multiplier' => 1,
            ];
        }

        $full_units = (int) floor($base_qty / $multiplier);
        $remainder = fmod($base_qty, $multiplier);

        return [
            'full_units' => $full_units,
            'remainder' => $remainder,
            'base_qty' => $base_qty,
            'multiplier' => $multiplier,
        ];
    }

    /**
     * Check whether base stock is an exact multiple of the given pack size.
     */
    public function isFullPackByMultiplier($multiplier, $use_available_stock = true)
    {
        $multiplier = (float) $multiplier;
        if ($multiplier <= 0) {
            return false;
        }

        $base_qty = (float) ($use_available_stock ? $this->available_stock : $this->qty_available);

        return fmod($base_qty, $multiplier) == 0.0;
    }
}