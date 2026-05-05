<?php

namespace Modules\Manufacturing\Entities;

use Illuminate\Database\Eloquent\Model;

class ManufacturingProduction extends Model
{
    protected $table = 'manufacturing_productions';

    protected $fillable = [
        'business_id',
        'location_id',
        'recipe_id',
        'quantity_produced',
        'status',
        'created_by',
        'produced_at',
        'notes',
    ];

    public function recipe()
    {
        return $this->belongsTo(ManufacturingRecipe::class, 'recipe_id');
    }
}
