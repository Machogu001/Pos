<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Product extends Model
{
    use SoftDeletes;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be appended.
     *
     * @var array
     */
    protected $appends = ['image_url'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'sub_unit_ids' => 'array',
        'is_inactive' => 'boolean',
        'not_for_selling' => 'boolean',
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['unit'];

    /**
     * Get the products image URL.
     *
     * @return string
     */
    public function getImageUrlAttribute()
    {
        if (!empty($this->image)) {
            return asset('/uploads/img/'.rawurlencode($this->image));
        }
        return asset('/img/default.png');
    }

    /**
     * Get the products image path.
     *
     * @return string|null
     */
    public function getImagePathAttribute()
    {
        if (!empty($this->image)) {
            return public_path('uploads').'/'.config('constants.product_img_path').'/'.$this->image;
        }
        return null;
    }

    /**
     * Get the variations for the product.
     */
    public function variations(): HasMany
    {
        return $this->hasMany(Variation::class);
    }

    /**
     * Get the product variations.
     */
    public function product_variations(): HasMany
    {
        return $this->hasMany(ProductVariation::class);
    }

    /**
     * Get the brand associated with the product.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the primary unit associated with the product.
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /**
     * Get the secondary unit associated with the product.
     */
    public function second_unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'secondary_unit_id');
    }

    /**
     * Get product-specific unit conversion rows (BC-style Qty. per Unit of Measure).
     */
    public function unit_conversions(): HasMany
    {
        return $this->hasMany(ProductUnitConversion::class);
    }

    /**
     * Get default purchase unit conversion for this product.
     */
    public function purchase_unit_conversion(): HasOne
    {
        return $this->hasOne(ProductUnitConversion::class)->where('is_purchase_default', true);
    }

    /**
     * Get default sale unit conversion for this product.
     */
    public function sale_unit_conversion(): HasOne
    {
        return $this->hasOne(ProductUnitConversion::class)->where('is_sale_default', true);
    }

    /**
     * Get the category associated with the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the sub-category associated with the product.
     */
    public function sub_category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }

    /**
     * Get the tax rate associated with the product.
     */
    public function product_tax(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class, 'tax', 'id');
    }

    /**
     * Get the modifier products associated with this product.
     */
    public function modifier_products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'res_product_modifier_sets',
            'modifier_set_id',
            'product_id'
        );
    }

    /**
     * Get the modifier sets associated with this product.
     */
    public function modifier_sets(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'res_product_modifier_sets',
            'product_id',
            'modifier_set_id'
        );
    }

    /**
     * Get the purchase lines for the product.
     */
    public function purchase_lines(): HasMany
    {
        return $this->hasMany(PurchaseLine::class);
    }

    /**
     * Get the business locations where this product is available.
     */
    public function product_locations(): BelongsToMany
    {
        return $this->belongsToMany(
            BusinessLocation::class,
            'product_locations',
            'product_id',
            'location_id'
        );
    }

    /**
     * Get the warranty associated with the product.
     */
    public function warranty(): BelongsTo
    {
        return $this->belongsTo(Warranty::class);
    }

    /**
     * Get all media for the product.
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }

    /**
     * Get rack details for the product.
     */
    public function rack_details(): HasMany
    {
        return $this->hasMany(ProductRack::class);
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive($query)
    {
        return $query->where('is_inactive', false);
    }

    /**
     * Scope a query to only include inactive products.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_inactive', true);
    }

    /**
     * Scope a query to only include products for sales.
     */
    public function scopeProductForSales($query)
    {
        return $query->where('not_for_selling', false);
    }

    /**
     * Scope a query to only include products not for sales.
     */
    public function scopeProductNotForSales($query)
    {
        return $query->where('not_for_selling', true);
    }

    /**
     * Scope a query to only include products available for a location.
     */
    public function scopeForLocation($query, $location_id)
    {
        return $query->whereHas('product_locations', function($q) use ($location_id) {
            $q->where('product_locations.location_id', $location_id);
        });
    }

    /**
     * Get the stock quantity for a specific location.
     */
    public function getStockQuantity($location_id)
    {
        return $this->variations()
            ->with(['variation_location_details' => function($q) use ($location_id) {
                $q->where('location_id', $location_id);
            }])
            ->get()
            ->sum(function($variation) {
                return $variation->variation_location_details->sum('qty_available');
            });
    }
}