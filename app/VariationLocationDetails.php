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
}