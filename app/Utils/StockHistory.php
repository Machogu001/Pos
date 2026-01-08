<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class StockHistory extends Model
{
    use HasFactory;

    protected $table = 'stock_histories'; 

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
     * Boot the model with event handling
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure quantity is never negative when saving
        static::saving(function ($model) {
            if ($model->quantity < 0) {
                Log::warning('Prevented negative quantity in StockHistory', [
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
            $q->where('type', 'like', '%increase')
              ->orWhere('type', 'purchase')
              ->orWhere('type', 'sell_return')
              ->orWhere('type', 'stock_transfer_in')
              ->orWhere('type', 'opening_stock');
        });
    }

    // Scope for decreases (negative adjustments)
    public function scopeDecreases($query)
    {
        return $query->where(function($q) {
            $q->where('type', 'like', '%decrease')
              ->orWhere('type', 'sale')
              ->orWhere('type', 'purchase_return')
              ->orWhere('type', 'stock_transfer_out');
        });
    }

    // Scope for stocktake adjustments
    public function scopeStocktake($query)
    {
        return $query->where('type', 'like', 'stocktake%');
    }

    // Scope for stock adjustments
    public function scopeStockAdjustment($query)
    {
        return $query->where('type', 'like', '%adjustment%');
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
            $q->where('type', 'stocktake_increase')
              ->orWhere('type', 'stocktake_decrease');
        });
    }

    // Scope for completed stocktakes only
    public function scopeCompletedStocktakes($query)
    {
        return $query->stocktakeAdjustments()
            ->whereNotNull('reference_no')
            ->where('reference_no', 'like', 'ST-%');
    }

    /**
     * Accessors & Mutators
     */

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

    // Get formatted date
    public function getFormattedDateAttribute()
    {
        return $this->created_at->format('d-m-Y H:i');
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

    // Check if this record is from stocktake
    public function isStocktake()
    {
        return str_contains($this->type, 'stocktake');
    }

    // Get CSS class for display based on type
    public function getDisplayClassAttribute()
    {
        return $this->isDecreaseType() ? 'text-danger' : 'text-success';
    }

    /**
     * Business Logic Methods
     */

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
            'total_purchase' => (clone $query)->where('type', 'purchase')->sum('quantity'),
            'opening_stock' => (clone $query)->where('type', 'opening_stock')->sum('quantity'),
            'total_sell_return' => (clone $query)->where('type', 'sell_return')->sum('quantity'),
            'stock_transfers_in' => (clone $query)->where('type', 'stock_transfer_in')->sum('quantity'),
            'total_sold' => (clone $query)->where('type', 'sale')->sum('quantity'),
            'total_stock_adjustment' => (clone $query)->stockAdjustment()->sum('quantity'),
            'total_purchase_return' => (clone $query)->where('type', 'purchase_return')->sum('quantity'),
            'stock_transfers_out' => (clone $query)->where('type', 'stock_transfer_out')->sum('quantity'),
            'current_stock' => $currentStock,
            'total_in' => $totalIncreases,
            'total_out' => $totalDecreases,
        ];
    }

    /**
     * Stocktake-specific Methods
     */

    // Get stocktake variance summary - UPDATED VERSION
    public static function getStocktakeVarianceSummary($filters = [])
    {
        $query = self::completedStocktakes();

        // Apply filters
        if (!empty($filters['location_id'])) {
            $query->where('location_id', $filters['location_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $result = $query->selectRaw('
            COUNT(*) as total_items,
            SUM(ABS(actual_adjustment)) as total_variance_quantity,
            SUM(CASE WHEN actual_adjustment > 0 THEN 1 ELSE 0 END) as overage_count,
            SUM(CASE WHEN actual_adjustment < 0 THEN 1 ELSE 0 END) as shortage_count,
            SUM(CASE WHEN actual_adjustment = 0 THEN 1 ELSE 0 END) as exact_count,
            AVG(ABS(actual_adjustment)) as average_variance,
            COUNT(DISTINCT reference_no) as stocktake_count
        ')->first();

        // Ensure we always return an object with default values
        if (!$result) {
            return (object) [
                'total_items' => 0,
                'total_variance_quantity' => 0,
                'overage_count' => 0,
                'shortage_count' => 0,
                'exact_count' => 0,
                'average_variance' => 0,
                'stocktake_count' => 0
            ];
        }

        return $result;
    }

    // Get stocktake performance by location - UPDATED VERSION
    public static function getLocationStocktakePerformance($locationId = null, $days = 30)
    {
        $query = self::completedStocktakes()
            ->where('created_at', '>=', now()->subDays($days));

        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        return $query->selectRaw('
            location_id,
            COUNT(DISTINCT reference_no) as stocktake_count,
            COUNT(*) as total_items,
            SUM(ABS(actual_adjustment)) as total_variance,
            AVG(ABS(actual_adjustment)) as avg_variance_per_item,
            MAX(created_at) as last_stocktake_date
        ')
        ->groupBy('location_id')
        ->with('location:id,name')
        ->get();
    }

    // Get product variance history - UPDATED VERSION
    public static function getProductVarianceHistory($productId, $variationId = null, $limit = 20)
    {
        $query = self::completedStocktakes()
            ->where('product_id', $productId)
            ->with(['location', 'variation', 'product'])
            ->orderBy('created_at', 'desc');

        if ($variationId) {
            $query->where('variation_id', $variationId);
        }

        return $query->limit($limit)->get();
    }

    // Get stocktake timeline for dashboard - UPDATED VERSION
    public static function getStocktakeTimeline($businessId, $limit = 10)
    {
        return self::completedStocktakes()
            ->whereHas('product', function($q) use ($businessId) {
                $q->where('business_id', $businessId);
            })
            ->selectRaw('
                reference_no,
                location_id,
                MIN(created_at) as started_at,
                MAX(created_at) as completed_at,
                COUNT(*) as item_count,
                SUM(ABS(actual_adjustment)) as total_variance
            ')
            ->with(['location:id,name'])
            ->groupBy('reference_no', 'location_id')
            ->orderBy('completed_at', 'desc')
            ->limit($limit)
            ->get();
    }

    // Calculate accuracy rate for a stocktake - UPDATED VERSION
    public static function calculateStocktakeAccuracy($referenceNo)
    {
        $items = self::where('reference_no', $referenceNo)->get();
        
        if ($items->isEmpty()) {
            return 0;
        }

        $exactMatches = $items->where('actual_adjustment', 0)->count();
        
        return ($exactMatches / $items->count()) * 100;
    }

    // Get worst performing products (highest variance) - UPDATED VERSION
    public static function getWorstPerformingProducts($businessId, $limit = 10, $days = 30)
    {
        return self::completedStocktakes()
            ->whereHas('product', function($q) use ($businessId) {
                $q->where('business_id', $businessId);
            })
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('
                product_id,
                variation_id,
                COUNT(*) as stocktake_count,
                SUM(ABS(actual_adjustment)) as total_variance,
                AVG(ABS(actual_adjustment)) as avg_variance,
                MAX(ABS(actual_adjustment)) as max_variance
            ')
            ->with(['product:id,name,sku', 'variation:id,name,sub_sku'])
            ->groupBy('product_id', 'variation_id')
            ->orderBy('avg_variance', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Utility Methods for DataTable Compatibility
     */

    // Get product name with variation for display
    public function getProductNameFullAttribute()
    {
        if (!$this->relationLoaded('product') || !$this->relationLoaded('variation')) {
            return 'Product not loaded';
        }

        $productName = $this->product->name ?? 'Unknown Product';
        $variationName = $this->variation->name ?? 'DUMMY';
        
        if ($variationName != 'DUMMY') {
            return $productName . ' - ' . $variationName;
        }
        
        return $productName;
    }

    // Get location name for display
    public function getLocationNameAttribute()
    {
        if (!$this->relationLoaded('location')) {
            return 'Location not loaded';
        }
        
        return $this->location->name ?? 'Unknown Location';
    }

    // Get adjusted by username for display
    public function getAdjustedByAttribute()
    {
        if (!$this->relationLoaded('createdByUser')) {
            return 'User not loaded';
        }
        
        return $this->createdByUser->username ?? 'System';
    }

    /**
     * Data Export Methods
     */

    // Get data for export
    public function toExportArray()
    {
        return [
            'date' => $this->created_at->format('Y-m-d H:i'),
            'product_name' => $this->product_name_full,
            'sku' => $this->variation->sub_sku ?? $this->product->sku ?? 'N/A',
            'location' => $this->location_name,
            'old_quantity' => (float) $this->old_quantity,
            'new_quantity' => (float) $this->new_quantity,
            // Return raw numeric values for exports (normalization)
            'variance' => (float) $this->actual_adjustment,
            // variance_percentage: return numeric percent (e.g. 12.34) or null when not applicable
            'variance_percentage' => $this->old_quantity == 0 ? null : (float) (($this->actual_adjustment / $this->old_quantity) * 100),
            'adjusted_by' => $this->adjusted_by,
            'reference_no' => $this->reference_no,
            'type' => $this->display_type
        ];
    }

    /**
     * Validation Methods
     */

    // Validate stock history data consistency
    public function validateDataConsistency()
    {
        $errors = [];

        // Check if new_quantity = old_quantity + actual_adjustment
        $expectedNewQuantity = $this->old_quantity + $this->actual_adjustment;
        if (abs($this->new_quantity - $expectedNewQuantity) > 0.001) {
            $errors[] = "Data inconsistency: new_quantity ({$this->new_quantity}) doesn't match old_quantity ({$this->old_quantity}) + actual_adjustment ({$this->actual_adjustment})";
        }

        // Check if quantity matches the absolute value of actual_adjustment for stocktakes
        if ($this->isStocktake() && abs($this->quantity - abs($this->actual_adjustment)) > 0.001) {
            $errors[] = "Stocktake quantity ({$this->quantity}) doesn't match absolute actual_adjustment (" . abs($this->actual_adjustment) . ")";
        }

        return $errors;
    }

    /**
     * Bulk Operations
     */

    // Fix data inconsistencies in bulk
    public static function fixDataInconsistencies()
    {
        $fixedCount = 0;
        $inconsistentRecords = self::whereRaw('ABS(new_quantity - (old_quantity + actual_adjustment)) > 0.001')->get();

        foreach ($inconsistentRecords as $record) {
            $record->new_quantity = $record->old_quantity + $record->actual_adjustment;
            $record->save();
            $fixedCount++;
        }

        return $fixedCount;
    }
}