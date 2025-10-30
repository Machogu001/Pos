<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Carbon\Carbon;

class StocktakeItem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'stocktake_id',
        'product_id',
        'product_variation_id',
        'variation_id',
        'system_quantity',
        'counted_quantity',
        'variance',
        'notes',
        'lot_number',
        'expiry_date'
    ];

    protected $casts = [
        'system_quantity' => 'decimal:4',
        'counted_quantity' => 'decimal:4',
        'variance' => 'decimal:4',
        'expiry_date' => 'date',
    ];

    protected $appends = [
        'variance_percentage',
        'value_variance',
        'formatted_expiry_date',
        'status',
        'product_image_url',
        'effective_price',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function stocktake(): BelongsTo
    {
        return $this->belongsTo(Stocktake::class)->withDefault([
            'name' => '[Deleted Stocktake]'
        ]);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id')
            ->withTrashed()
            ->withDefault([
                'name' => '[Deleted Product]',
                'sku' => 'N/A',
                'image' => null,
                'enable_stock' => true
            ]);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(Variation::class, 'variation_id')
            ->withTrashed()
            ->withDefault([
                'name' => '[Deleted Variation]',
                'sub_sku' => 'N/A',
                'default_sell_price' => 0,
                'sell_price_inc_tax' => 0,
                'default_purchase_price' => 0
            ]);
    }

    public function productVariation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id')
            ->withTrashed()
            ->withDefault([
                'name' => '[Deleted Product Variation]'
            ]);
    }

    public function location(): HasOneThrough
    {
        return $this->hasOneThrough(
            BusinessLocation::class,
            Stocktake::class,
            'id',
            'id',
            'stocktake_id',
            'location_id'
        )->withDefault([
            'name' => '[Unknown Location]'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeWithVariance(Builder $query, $minVariance = null): Builder
    {
        $query->where('variance', '!=', 0);

        if (!is_null($minVariance)) {
            $query->whereRaw('ABS(variance) >= ?', [$minVariance]);
        }

        return $query;
    }

    public function scopePositiveVariance(Builder $query): Builder
    {
        return $query->where('variance', '>', 0);
    }

    public function scopeNegativeVariance(Builder $query): Builder
    {
        return $query->where('variance', '<', 0);
    }

    public function scopeForProduct(Builder $query, $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForVariation(Builder $query, $variationId): Builder
    {
        return $query->where('variation_id', $variationId);
    }

    public function scopeForProductVariation(Builder $query, $productVariationId): Builder
    {
        return $query->where('product_variation_id', $productVariationId);
    }

    public function scopeWithProductDetails(Builder $query): Builder
    {
        return $query->with([
            'product' => fn($q) => $q->withTrashed()->select('id', 'name', 'sku', 'unit_id', 'image', 'enable_stock'),
            'variation' => fn($q) => $q->withTrashed()->select('id', 'name', 'product_id', 'sub_sku', 'default_sell_price', 'sell_price_inc_tax', 'default_purchase_price'),
            'productVariation' => fn($q) => $q->withTrashed()
        ]);
    }

    public function scopeWithLotExpiry(Builder $query): Builder
    {
        return $query->where(function($q) {
            $q->whereNotNull('lot_number')
              ->orWhereNotNull('expiry_date');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getVariancePercentageAttribute(): float
    {
        if ((float) $this->system_quantity === 0.0) {
            return (float) ($this->counted_quantity > 0 ? 100 : 0);
        }

        return round(($this->variance / $this->system_quantity) * 100, 2);
    }

    public function getValueVarianceAttribute(): float
    {
        return round($this->variance * $this->effective_price, 2);
    }

    public function getEffectivePriceAttribute(): float
    {
        // Try variation price first, then product price
        $variation_price = optional($this->variation)->sell_price_inc_tax 
            ?? optional($this->variation)->default_sell_price 
            ?? 0;
            
        $product_price = optional($this->product)->default_sell_price ?? 0;

        return $variation_price ?: $product_price;
    }

    public function getPurchasePriceAttribute(): float
    {
        return optional($this->variation)->default_purchase_price ?? 0;
    }

    public function getFormattedExpiryDateAttribute(): ?string
    {
        return $this->expiry_date?->format('Y-m-d');
    }

    public function getDisplayExpiryDateAttribute(): ?string
    {
        if (!$this->expiry_date) {
            return null;
        }

        $today = Carbon::today();
        $expiryDate = Carbon::parse($this->expiry_date);

        if ($expiryDate->lessThan($today)) {
            return '<span class="text-danger">' . $expiryDate->format('Y-m-d') . ' (Expired)</span>';
        } elseif ($expiryDate->diffInDays($today) <= 30) {
            return '<span class="text-warning">' . $expiryDate->format('Y-m-d') . ' (Soon)</span>';
        }

        return $expiryDate->format('Y-m-d');
    }

    public function getStatusAttribute(): string
    {
        return match (true) {
            $this->variance > 0 => 'over',
            $this->variance < 0 => 'under',
            default => 'exact'
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'over' => '<span class="badge badge-success">Over</span>',
            'under' => '<span class="badge badge-danger">Under</span>',
            'exact' => '<span class="badge badge-info">Exact</span>',
            default => '<span class="badge badge-secondary">Unknown</span>'
        };
    }

    public function getProductImageUrlAttribute(): string
    {
        if ($this->relationLoaded('product') && !empty($this->product->image)) {
            return asset('/uploads/img/' . rawurlencode($this->product->image));
        }

        return asset('/img/default.png');
    }

    public function getProductNameAttribute(): string
    {
        if ($this->relationLoaded('product') && $this->relationLoaded('variation')) {
            $productName = $this->product->name ?? 'N/A';
            $variationName = $this->variation->name ?? 'DUMMY';
            
            if ($variationName !== 'DUMMY') {
                return $productName . ' - ' . $variationName;
            }
            
            return $productName;
        }

        return 'Product Name Not Available';
    }

    public function getSkuAttribute(): string
    {
        if ($this->relationLoaded('variation') && !empty($this->variation->sub_sku)) {
            return $this->variation->sub_sku;
        }

        if ($this->relationLoaded('product')) {
            return $this->product->sku ?? 'N/A';
        }

        return 'N/A';
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    public function calculateVariance(): self
    {
        $this->variance = $this->counted_quantity - $this->system_quantity;
        return $this;
    }

    public function isCountedHigher(): bool
    {
        return $this->variance > 0;
    }

    public function isCountedLower(): bool
    {
        return $this->variance < 0;
    }

    public function isCountedExact(): bool
    {
        return $this->variance == 0;
    }

    public function hasLotExpiry(): bool
    {
        return !empty($this->lot_number) || !empty($this->expiry_date);
    }

    public function isExpired(): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return Carbon::parse($this->expiry_date)->lessThan(Carbon::today());
    }

    public function willExpireSoon(int $days = 30): bool
    {
        if (!$this->expiry_date) {
            return false;
        }

        return Carbon::parse($this->expiry_date)->diffInDays(Carbon::today()) <= $days;
    }

    /**
     * Create a stocktake item and ensure product_variation_id is stored
     */
    public static function createForStocktake(int $stocktakeId, array $productData): self
    {
        return self::create([
            'stocktake_id' => $stocktakeId,
            'product_id' => $productData['product_id'],
            'product_variation_id' => $productData['product_variation_id'],
            'variation_id' => $productData['variation_id'],
            'system_quantity' => $productData['system_quantity'],
            'counted_quantity' => $productData['counted_quantity'],
            'variance' => $productData['counted_quantity'] - $productData['system_quantity'],
            'notes' => $productData['notes'] ?? null,
            'lot_number' => $productData['lot_number'] ?? null,
            'expiry_date' => $productData['expiry_date'] ?? null,
        ]);
    }

    public function updateCount(float $countedQuantity, ?string $notes = null, ?string $lotNumber = null, ?string $expiryDate = null): self
    {
        $this->counted_quantity = $countedQuantity;
        $this->notes = $notes;
        
        if (!is_null($lotNumber)) {
            $this->lot_number = $lotNumber;
        }
        
        if (!is_null($expiryDate)) {
            $this->expiry_date = $expiryDate;
        }
        
        $this->calculateVariance();
        $this->save();

        return $this;
    }

    public function adjustSystemQuantity(float $newSystemQuantity): self
    {
        $this->system_quantity = $newSystemQuantity;
        $this->calculateVariance();
        $this->save();

        return $this;
    }

    /**
     * Get the current stock quantity from the system
     */
    public function getCurrentSystemQuantity(): float
    {
        try {
            // This would use your ProductUtil to get current stock
            // You might need to inject ProductUtil or call it statically
            if (method_exists(app('productUtil'), 'getStockByVariation')) {
                return app('productUtil')->getStockByVariation(
                    $this->variation_id,
                    $this->stocktake->location_id,
                    $this->lot_number,
                    $this->expiry_date
                );
            }
            
            return $this->system_quantity;
        } catch (\Exception $e) {
            \Log::error('Error getting current system quantity', [
                'stocktake_item_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return $this->system_quantity;
        }
    }

    /**
     * Check if this item needs adjustment
     */
    public function needsAdjustment(): bool
    {
        return $this->variance != 0;
    }

    /**
     * Get adjustment amount (signed)
     */
    public function getAdjustmentAmount(): float
    {
        return $this->variance;
    }

    /**
     * Get absolute adjustment amount
     */
    public function getAbsoluteAdjustmentAmount(): float
    {
        return abs($this->variance);
    }

    /**
     * Get adjustment type
     */
    public function getAdjustmentType(): string
    {
        return $this->variance > 0 ? 'increase' : ($this->variance < 0 ? 'decrease' : 'none');
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->calculateVariance();
            
            // Ensure counted quantity is not negative
            if ($model->counted_quantity < 0) {
                $model->counted_quantity = 0;
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty(['system_quantity', 'counted_quantity'])) {
                $model->calculateVariance();
            }
            
            // Ensure counted quantity is not negative
            if ($model->isDirty('counted_quantity') && $model->counted_quantity < 0) {
                $model->counted_quantity = 0;
            }
        });

        static::saving(function ($model) {
            // Format expiry date if it's a string
            if ($model->expiry_date && is_string($model->expiry_date)) {
                try {
                    $model->expiry_date = Carbon::parse($model->expiry_date)->format('Y-m-d');
                } catch (\Exception $e) {
                    // If parsing fails, set to null
                    $model->expiry_date = null;
                }
            }
        });
    }
}