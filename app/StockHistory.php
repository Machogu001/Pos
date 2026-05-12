<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockHistory extends Model
{
    use HasFactory;

    private const VARIANCE_EPSILON = 0.0001;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_id',
        'product_variation_id',
        'variation_id',
        'location_id',
        'quantity',        // Should always be positive
        'old_quantity',
        'new_quantity',
        'type',            // e.g., stock_adjustment, sale, purchase, stocktake_increase, stocktake_decrease
        'transaction_id',  // optional reference to related transaction
        'lot_number',
        'expiry_date',
        'created_by',
        'updated_by',
        'reason',          // optional notes or reason
        'actual_adjustment', // Can be negative for internal tracking
        'reference_no',    // Transaction or stocktake reference
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'quantity' => 'decimal:8',
        'old_quantity' => 'decimal:8',
        'new_quantity' => 'decimal:8',
        'actual_adjustment' => 'decimal:8',
        'expiry_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'product_name_full',
        'sku',
        'location_name',
        'adjusted_by',
        'variance_percentage',
        'display_quantity',
        'display_type',
        'simple_type',
        'adjustment_direction',
        'absolute_quantity',
        'formatted_date',
        'description',
        'display_class'
    ];

    /**
     * Boot the model with event handling
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure quantity is never negative when saving
        static::saving(function ($model) {
            if ($model->quantity < 0) {
                \Log::warning('Prevented negative quantity in StockHistory', [
                    'stock_history_id' => $model->id,
                    'product_id' => $model->product_id,
                    'attempted_quantity' => $model->quantity,
                    'type' => $model->type
                ]);
                
                // Store the original negative value in actual_adjustment
                if (empty($model->actual_adjustment)) {
                    $model->actual_adjustment = $model->quantity;
                }
                
                $model->quantity = abs($model->quantity);
                
                // Auto-correct type based on actual adjustment
                if ($model->actual_adjustment < 0 && !str_contains($model->type, 'decrease')) {
                    $model->type = str_replace('increase', 'decrease', $model->type) ?? $model->type . '_decrease';
                } elseif ($model->actual_adjustment > 0 && !str_contains($model->type, 'increase')) {
                    $model->type = str_replace('decrease', 'increase', $model->type) ?? $model->type . '_increase';
                }
            }
            
            // Ensure actual_adjustment is set if not provided
            if (empty($model->actual_adjustment)) {
                $model->actual_adjustment = $model->isDecreaseType() ? -$model->quantity : $model->quantity;
            }
        });

        // Set default values before creating
        static::creating(function ($model) {
            if (empty($model->created_by) && auth()->check()) {
                $model->created_by = auth()->id();
            }
            if (empty($model->updated_by) && auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        // Update updated_by before saving
        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    /**
     * Relationships
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    public function variation()
    {
        return $this->belongsTo(Variation::class);
    }

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */

    // Scope for increases (positive adjustments)
    public function scopeIncreases($query)
    {
        return $query->where(function($q) {
            $q->where('stock_histories.type', 'like', '%increase')
              ->orWhere('stock_histories.type', 'purchase')
              ->orWhere('stock_histories.type', 'sell_return')
              ->orWhere('stock_histories.type', 'stock_transfer_in')
              ->orWhere('stock_histories.type', 'opening_stock');
        });
    }

    // Scope for decreases (negative adjustments)
    public function scopeDecreases($query)
    {
        return $query->where(function($q) {
            $q->where('stock_histories.type', 'like', '%decrease')
              ->orWhere('stock_histories.type', 'sale')
              ->orWhere('stock_histories.type', 'purchase_return')
              ->orWhere('stock_histories.type', 'stock_transfer_out');
        });
    }

    // Scope for stocktake adjustments
    public function scopeStocktake($query)
    {
        return $query->where('stock_histories.type', 'like', 'stocktake%');
    }

    // Scope for stock adjustments
    public function scopeStockAdjustment($query)
    {
        return $query->where('stock_histories.type', 'like', '%adjustment%');
    }

    // Scope for specific product
    public function scopeForProduct($query, $product_id)
    {
        return $query->where('product_id', $product_id);
    }

    // Scope for specific variation
    public function scopeForVariation($query, $variation_id)
    {
        return $query->where('variation_id', $variation_id);
    }

    // Scope for specific location
    public function scopeForLocation($query, $location_id)
    {
        return $query->where('location_id', $location_id);
    }

    // Scope for recent entries
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Scope for stocktake adjustments with proper filtering
    public function scopeStocktakeAdjustments($query)
    {
        return $query->where(function($q) {
            $q->where('stock_histories.type', 'stocktake_increase')
              ->orWhere('stock_histories.type', 'stocktake_decrease');
        });
    }

    // Scope for completed stocktakes only
  public function scopeCompletedStocktakes($query)
{
    return $query
        ->where(function ($q) {
            // Include both explicit stocktake types and stock adjustment types created by stocktake completion
                        $q->where('stock_histories.type', 'stocktake_increase')
                            ->orWhere('stock_histories.type', 'stocktake_decrease')
                            ->orWhere('stock_histories.type', 'stock_adjustment_increase')
                            ->orWhere('stock_histories.type', 'stock_adjustment_decrease');
        })
        ->whereNotNull('reference_no')
        ->where('reference_no', 'like', 'ST-%');
}

    /**
     * NEW: Scope for history page with all required relationships
     */
   // In StockHistory model
public function scopeForHistoryPage($query)
{
    // Use the actual table name for joins/selects to avoid ambiguous or incorrect aliases
    return $query->select([
            'stock_histories.*',
            'products.name as product_name',
            'variations.name as variation_name',
            'variations.sub_sku as variation_sku',
            'business_locations.name as location_name',
            'users.username as adjusted_by',
            DB::raw('CONCAT(products.name, 
                CASE WHEN variations.name != "DUMMY" THEN CONCAT(" - ", variations.name) ELSE "" END
            ) as product_name_full'),
            DB::raw('COALESCE(variations.sub_sku, products.sku) as sku'),
            DB::raw('COALESCE(variations.sell_price_inc_tax, variations.default_sell_price, 0) as selling_price'),
            DB::raw('COALESCE((SELECT purchase_price_inc_tax FROM purchase_lines WHERE variation_id = stock_histories.variation_id ORDER BY id DESC LIMIT 1), (SELECT purchase_price_inc_tax FROM purchase_lines WHERE product_id = stock_histories.product_id ORDER BY id DESC LIMIT 1), 0) as last_purchased_price'),
            // Compute variance quantity as new_quantity - old_quantity, and use it for amount and percentage
            DB::raw('(COALESCE(variations.sell_price_inc_tax, variations.default_sell_price, 0) * (CASE WHEN ABS(stock_histories.new_quantity - stock_histories.old_quantity) < 0.0001 THEN 0 ELSE (stock_histories.new_quantity - stock_histories.old_quantity) END)) as variance_amount'),
            DB::raw('CASE 
                WHEN stock_histories.old_quantity = 0 AND stock_histories.new_quantity > 0 THEN (ABS(stock_histories.new_quantity - stock_histories.old_quantity) / NULLIF(stock_histories.new_quantity, 0)) * 100 
                WHEN stock_histories.old_quantity = 0 THEN NULL 
                ELSE (ABS(stock_histories.new_quantity - stock_histories.old_quantity) / NULLIF(stock_histories.old_quantity, 0)) * 100 
            END as variance_percentage')
        ])
        ->leftJoin('products', 'stock_histories.product_id', '=', 'products.id')
        ->leftJoin('variations', 'stock_histories.variation_id', '=', 'variations.id')
        ->leftJoin('business_locations', 'stock_histories.location_id', '=', 'business_locations.id')
    ->leftJoin('users', 'stock_histories.created_by', '=', 'users.id')
    ->where(function($q) {
        $q->where('stock_histories.type', 'like', 'stocktake%')
          ->orWhere('stock_histories.type', 'like', 'stock_adjustment%');
    });
}

    /**
     * Accessors & Mutators
     */

    // Get product name with variation for display
    public function getProductNameFullAttribute()
    {
            // If a pre-selected attribute from a query exists, use it first
            if (array_key_exists('product_name_full', $this->attributes) && !empty($this->attributes['product_name_full'])) {
                return $this->attributes['product_name_full'];
            }

            if ($this->relationLoaded('product') && $this->product) {
                $name = $this->product->name;
                if ($this->relationLoaded('variation') && $this->variation && $this->variation->name != 'DUMMY') {
                    $name .= ' - ' . $this->variation->name;
                }
                return $name;
            }

            return 'N/A';
    }

    // Get SKU for display (product SKU or variation sub_sku)
    public function getSkuAttribute()
    {
        // Prefer selected attribute from query
        if (array_key_exists('sku', $this->attributes) && !empty($this->attributes['sku'])) {
            return $this->attributes['sku'];
        }

        if ($this->relationLoaded('variation') && $this->variation && $this->variation->sub_sku) {
            return $this->variation->sub_sku;
        }
        
        if ($this->relationLoaded('product') && $this->product && $this->product->sku) {
            return $this->product->sku;
        }
        
        return 'N/A';
    }

    // Get location name for display
    public function getLocationNameAttribute()
    {
        if (array_key_exists('location_name', $this->attributes) && !empty($this->attributes['location_name'])) {
            return $this->attributes['location_name'];
        }

        if ($this->relationLoaded('location') && $this->location) {
            return $this->location->name;
        }
        return 'N/A';
    }

    // Get adjusted by user name
    public function getAdjustedByAttribute()
    {
        if (array_key_exists('adjusted_by', $this->attributes) && !empty($this->attributes['adjusted_by'])) {
            return $this->attributes['adjusted_by'];
        }

        if ($this->relationLoaded('createdByUser') && $this->createdByUser) {
            return $this->createdByUser->name;
        }
        return 'System';
    }

    // Calculate variance percentage
    public function getVariancePercentageAttribute()
    {
        // If precomputed value exists from a select, return it
        if (array_key_exists('variance_percentage', $this->attributes) && $this->attributes['variance_percentage'] !== null) {
            return (float) $this->attributes['variance_percentage'];
        }

        // If old_quantity is zero or null, fall back to using new_quantity for percentage
        $oldQty = isset($this->old_quantity) ? floatval($this->old_quantity) : 0.0;
        $newQty = isset($this->new_quantity) ? floatval($this->new_quantity) : 0.0;
        $adj = isset($this->actual_adjustment) ? abs(floatval($this->actual_adjustment)) : 0.0;

        if ($oldQty === 0.0) {
            if ($newQty > 0.0) {
                return ($adj / $newQty) * 100;
            }
            return null;
        }

        return ($adj / $oldQty) * 100;
    }

    // Get display quantity with proper sign
    public function getDisplayQuantityAttribute()
    {
        // Always show positive quantity with proper sign based on type
        if ($this->isDecreaseType()) {
            return '-' . $this->quantity;
        }
        return '+' . $this->quantity;
    }

    // Get display type (clean version)
    public function getDisplayTypeAttribute()
    {
        $baseType = str_replace(['_increase', '_decrease'], '', $this->type);
        $cleanType = ucwords(str_replace('_', ' ', $baseType));
        
        // Add direction for clarity
        if ($this->isDecreaseType()) {
            return $cleanType . ' - Decrease';
        } else {
            return $cleanType . ' - Increase';
        }
    }

    // Get simple type without direction
    public function getSimpleTypeAttribute()
    {
        $baseType = str_replace(['_increase', '_decrease'], '', $this->type);
        return ucwords(str_replace('_', ' ', $baseType));
    }

    // Get adjustment direction
    public function getAdjustmentDirectionAttribute()
    {
        return $this->isDecreaseType() ? 'decrease' : 'increase';
    }

    // Get absolute quantity (always positive)
    public function getAbsoluteQuantityAttribute()
    {
        return abs($this->quantity);
    }

    // Get formatted date
    public function getFormattedDateAttribute()
    {
        return $this->created_at ? $this->created_at->format('d-m-Y H:i') : 'N/A';
    }

    // Get description for display
    public function getDescriptionAttribute()
    {
        $descriptions = [
            'stocktake_increase' => 'Stocktake Adjustment',
            'stocktake_decrease' => 'Stocktake Adjustment',
            'purchase' => 'Purchase',
            'sale' => 'Sale',
            'stock_adjustment' => 'Stock Adjustment',
            'purchase_return' => 'Purchase Return',
            'sell_return' => 'Sell Return',
            'opening_stock' => 'Opening Stock',
            'stock_transfer_in' => 'Stock Transfer In',
            'stock_transfer_out' => 'Stock Transfer Out',
        ];

        return $descriptions[$this->type] ?? $this->simple_type;
    }

    // Get CSS class for display based on type
    public function getDisplayClassAttribute()
    {
        return $this->isDecreaseType() ? 'text-danger' : 'text-success';
    }

    /**
     * Business Logic Methods
     */

    // Check if this is a decrease type
    public function isDecreaseType()
    {
        return str_contains($this->type, 'decrease') || 
               in_array($this->type, ['sale', 'purchase_return', 'stock_transfer_out']);
    }

    // Check if this is an increase type
    public function isIncreaseType()
    {
        return str_contains($this->type, 'increase') || 
               in_array($this->type, ['purchase', 'sell_return', 'stock_adjustment', 'stock_transfer_in', 'opening_stock']);
    }

    // Create a stock history entry with proper validation
    public static function createStockHistory($data)
    {
        // Ensure quantity is positive
        if (isset($data['quantity']) && $data['quantity'] < 0) {
            $data['actual_adjustment'] = $data['quantity']; // Store original negative value
            $data['quantity'] = abs($data['quantity']);
            
            // Auto-correct type if needed
            if (isset($data['type']) && !str_contains($data['type'], 'decrease')) {
                $data['type'] = $data['type'] . '_decrease';
            }
        } elseif (isset($data['quantity']) && $data['quantity'] > 0) {
            // Ensure type reflects increase for positive quantities
            if (isset($data['type']) && !str_contains($data['type'], 'increase') && 
                !in_array($data['type'], ['purchase', 'sell_return', 'stock_adjustment'])) {
                $data['type'] = $data['type'] . '_increase';
            }
        }

        return static::create($data);
    }

    // Create stocktake-specific history
    public static function createStocktakeHistory($stocktake, $item, $adjustment_amount, $current_stock)
    {
        $quantity = abs($adjustment_amount);
        $type = $adjustment_amount > 0 ? 'stocktake_increase' : 'stocktake_decrease';
        
        return static::createStockHistory([
            'product_id' => $item->product_id,
            'product_variation_id' => $item->product_variation_id,
            'variation_id' => $item->variation_id,
            'location_id' => $stocktake->location_id,
            'quantity' => $quantity,
            'old_quantity' => $current_stock,
            'new_quantity' => $item->counted_quantity,
            'type' => $type,
            'transaction_id' => null, // Stocktake may not have transaction ID
            'reference_no' => $stocktake->reference_no,
            'actual_adjustment' => $adjustment_amount,
            'reason' => 'Stocktake adjustment - Counted: ' . $item->counted_quantity . ', System: ' . $current_stock,
            'created_by' => auth()->id(),
        ]);
    }

    // Get current stock based on history
    public static function getCurrentStock($product_id, $variation_id = null, $location_id = null)
    {
        $query = static::forProduct($product_id);

        if ($variation_id) {
            $query->forVariation($variation_id);
        }

        if ($location_id) {
            $query->forLocation($location_id);
        }

        $totalIn = (clone $query)->increases()->sum('quantity');
        $totalOut = (clone $query)->decreases()->sum('quantity');

        return $totalIn - $totalOut;
    }

    // Get stock history summary
    public static function getStockSummary($product_id, $variation_id = null, $location_id = null)
    {
        $query = static::forProduct($product_id);

        if ($variation_id) {
            $query->forVariation($variation_id);
        }

        if ($location_id) {
            $query->forLocation($location_id);
        }

        $totalIncreases = (clone $query)->increases()->sum('quantity');
        $totalDecreases = (clone $query)->decreases()->sum('quantity');
        $currentStock = $totalIncreases - $totalDecreases;

        return [
            'total_purchase' => (clone $query)->where('stock_histories.type', 'purchase')->sum('quantity'),
            'opening_stock' => (clone $query)->where('stock_histories.type', 'opening_stock')->sum('quantity'),
            'total_sell_return' => (clone $query)->where('stock_histories.type', 'sell_return')->sum('quantity'),
            'stock_transfers_in' => (clone $query)->where('stock_histories.type', 'stock_transfer_in')->sum('quantity'),
            'total_sold' => (clone $query)->where('stock_histories.type', 'sale')->sum('quantity'),
            'total_stock_adjustment' => (clone $query)->stockAdjustment()->sum('quantity'),
            'total_purchase_return' => (clone $query)->where('stock_histories.type', 'purchase_return')->sum('quantity'),
            'stock_transfers_out' => (clone $query)->where('stock_histories.type', 'stock_transfer_out')->sum('quantity'),
            'current_stock' => $currentStock,
            'total_in' => $totalIncreases,
            'total_out' => $totalDecreases,
        ];
    }

    /**
     * Stocktake-specific Methods
     */

    // Check if this record is from stocktake
    public function isStocktake()
    {
        return str_contains($this->type, 'stocktake');
    }

    // Get stocktake variance summary
    public static function getStocktakeVarianceSummary($filters = [])
    {
        $query = self::completedStocktakes();

        // Apply filters
        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('stock_histories.created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('stock_histories.created_at', '<=', $filters['date_to']);
        }

        return $query->selectRaw('
            COUNT(*) as total_items,
            SUM(CASE WHEN ABS(actual_adjustment) < 0.0001 THEN 0 ELSE ABS(actual_adjustment) END) as total_variance_quantity,
            SUM(CASE WHEN actual_adjustment >= 0.0001 THEN 1 ELSE 0 END) as overage_count,
            SUM(CASE WHEN actual_adjustment <= -0.0001 THEN 1 ELSE 0 END) as shortage_count,
            SUM(CASE WHEN ABS(actual_adjustment) < 0.0001 THEN 1 ELSE 0 END) as exact_count,
            AVG(CASE WHEN ABS(actual_adjustment) < 0.0001 THEN 0 ELSE ABS(actual_adjustment) END) as average_variance,
            COUNT(DISTINCT reference_no) as stocktake_count
        ')->first();
    }

    // Get stocktake performance by location
    public static function getLocationStocktakePerformance($locationId = null, $days = 30)
    {
        $query = self::completedStocktakes()
            ->where('stock_histories.created_at', '>=', now()->subDays($days));

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return $query->selectRaw('
            location_id,
            COUNT(DISTINCT reference_no) as stocktake_count,
            COUNT(*) as total_items,
            SUM(CASE WHEN ABS(actual_adjustment) < 0.0001 THEN 0 ELSE ABS(actual_adjustment) END) as total_variance,
            AVG(CASE WHEN ABS(actual_adjustment) < 0.0001 THEN 0 ELSE ABS(actual_adjustment) END) as avg_variance_per_item,
            MAX(stock_histories.created_at) as last_stocktake_date
        ')
        ->groupBy('location_id')
        ->with('location:id,name')
        ->get();
    }

    // Get product variance history
    public static function getProductVarianceHistory($productId, $variationId = null, $limit = 20)
    {
        $query = self::completedStocktakes()
            ->where('product_id', $productId)
            ->with(['location', 'variation'])
            ->orderBy('stock_histories.created_at', 'desc');

        if ($variationId) {
            $query->where('variation_id', $variationId);
        }

        return $query->limit($limit)->get();
    }

    // Get stocktake timeline for dashboard
    public static function getStocktakeTimeline($businessId, $limit = 10)
    {
        return self::completedStocktakes()
            ->whereHas('product', function($q) use ($businessId) {
                $q->where('business_id', $businessId);
            })
            ->selectRaw('
                reference_no,
                location_id,
                MIN(stock_histories.created_at) as started_at,
                MAX(stock_histories.created_at) as completed_at,
                COUNT(*) as item_count,
                SUM(CASE WHEN ABS(actual_adjustment) < 0.0001 THEN 0 ELSE ABS(actual_adjustment) END) as total_variance
            ')
            ->with(['location:id,name'])
            ->groupBy('reference_no', 'location_id')
            ->orderBy('completed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    // Calculate accuracy rate for a stocktake
    public static function calculateStocktakeAccuracy($referenceNo)
    {
        $items = self::where('reference_no', $referenceNo)->get();
        
        if ($items->isEmpty()) {
            return 0;
        }

        $exactMatches = $items->filter(function ($item) {
            return abs((float) $item->actual_adjustment) < self::VARIANCE_EPSILON;
        })->count();
        
        return ($exactMatches / $items->count()) * 100;
    }

    // Get worst performing products (highest variance)
    public static function getWorstPerformingProducts($businessId, $limit = 10, $days = 30)
    {
        // Join product and variation to include display fields (product name, variation name, sku)
        return self::completedStocktakes()
            ->whereHas('product', function($q) use ($businessId) {
                $q->where('business_id', $businessId);
            })
            ->where('stock_histories.created_at', '>=', now()->subDays($days))
            ->leftJoin('products', 'stock_histories.product_id', '=', 'products.id')
            ->leftJoin('variations', 'stock_histories.variation_id', '=', 'variations.id')
            ->selectRaw('
                stock_histories.product_id as product_id,
                stock_histories.variation_id as variation_id,
                COUNT(*) as stocktake_count,
                SUM(CASE WHEN ABS(stock_histories.actual_adjustment) < 0.0001 THEN 0 ELSE ABS(stock_histories.actual_adjustment) END) as total_variance,
                AVG(CASE WHEN ABS(stock_histories.actual_adjustment) < 0.0001 THEN 0 ELSE ABS(stock_histories.actual_adjustment) END) as avg_variance,
                MAX(CASE WHEN ABS(stock_histories.actual_adjustment) < 0.0001 THEN 0 ELSE ABS(stock_histories.actual_adjustment) END) as max_variance,
                products.name as product_name,
                variations.name as variation_name,
                COALESCE(variations.sub_sku, products.sku) as sku,
                CONCAT(products.name, CASE WHEN COALESCE(variations.name, "") != "" AND COALESCE(variations.name, "") != "DUMMY" THEN CONCAT(" - ", variations.name) ELSE "" END) as product_name_full
            ')
            ->groupBy('stock_histories.product_id', 'stock_histories.variation_id', 'products.name', 'variations.name', 'sku')
            ->orderBy('avg_variance', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * NEW: Export data for variance report
     */
    public static function getExportData($filters = [])
    {
        return self::forHistoryPage()
            ->when(!empty($filters['location_id']), function($query) use ($filters) {
                return $query->where('location_id', $filters['location_id']);
            })
            ->when(!empty($filters['date_from']), function($query) use ($filters) {
                return $query->whereDate('stock_histories.created_at', '>=', $filters['date_from']);
            })
            ->when(!empty($filters['date_to']), function($query) use ($filters) {
                return $query->whereDate('stock_histories.created_at', '<=', $filters['date_to']);
            })
            ->get();
    }
}