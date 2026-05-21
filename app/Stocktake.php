<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Stocktake
 * @package App
 */
class Stocktake extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'reference_no',
        'status',
        'location_id',
        'created_by',
        'business_id',
        'started_at',
        'completed_at',
        'completed_by',
        'additional_notes',
        'transaction_date',
        'adjustment_transaction_id',
        'cancelled_at',
        'cancelled_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'transaction_date' => 'datetime',
        'deleted_at' => 'datetime',
        'status' => 'string',
    ];

    protected $appends = [
        'status_badge',
        'total_variance_value',
        'formatted_transaction_date',
        'formatted_completed_at',
        'formatted_started_at',
        'formatted_cancelled_at',
        'is_completed',
        'is_draft',
        'is_in_progress',
        'is_cancelled',
        'product_count',
        'adjustment_ref',
    ];

    // ──────── BOOT METHOD ────────

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->reference_no = $model->reference_no ?? static::generateReferenceNumber();
            $model->transaction_date = $model->transaction_date ?? now();
            $model->status = $model->status ?? self::STATUS_DRAFT;
            $model->created_by = $model->created_by ?? auth()->id();
            $model->business_id = $model->business_id ?? (auth()->check() ? auth()->user()->business_id : null);
        });

        static::deleting(function ($model) {
            if ($model->isForceDeleting()) {
                $model->items()->each->forceDelete();
                $model->adjustments()->each->forceDelete();
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('status')) {
                if ($model->status === self::STATUS_IN_PROGRESS && !$model->started_at) {
                    $model->started_at = now();
                }
            }
        });
    }

    // ──────── GENERATOR ────────

    public static function generateReferenceNumber(): string
    {
        $prefix = config('constants.stocktake.reference_prefix', 'ST');
        $date = now()->format('ymd');
        $random = Str::upper(Str::random(4));

        return "{$prefix}-{$date}-{$random}";
    }

    // ──────── SCOPES ────────

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeForLocation(Builder $query, int $locationId): Builder
    {
        return $query->where('location_id', $locationId);
    }

    public function scopeForBusiness(Builder $query, int $businessId): Builder
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeWithVariance(Builder $query): Builder
    {
        return $query->whereHas('items', fn ($q) => $q->where('variance', '!=', 0));
    }

    public function scopeRecentFirst(Builder $query): Builder
    {
        return $query->orderByDesc('transaction_date');
    }

    public function scopeWithItems(Builder $query): Builder
    {
        return $query->with(['items' => function ($q) {
            $q->with(['product:id,name,sku', 'variation:id,name,sub_sku']);
        }]);
    }

    // ──────── RELATIONSHIPS ────────

    public function location(): BelongsTo
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id')
                    ->withDefault(['name' => 'N/A']);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StocktakeItem::class)
                    ->with(['product:id,name,sku,image', 'variation:id,name,sub_sku,default_sell_price,sell_price_inc_tax,default_purchase_price']);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')
                    ->withDefault(['user_full_name' => 'System']);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by')
                    ->withDefault(['user_full_name' => 'N/A']);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by')
                    ->withDefault(['user_full_name' => 'N/A']);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(Transaction::class, 'stocktake_id')
                    ->where('type', 'stock_adjustment')
                    ->with(['stock_adjustment_lines']);
    }

    public function adjustmentTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'adjustment_transaction_id')
                    ->withDefault();
    }

    // ──────── STATUS HELPERS ────────

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function complete(?int $completedBy = null, ?int $adjustmentTransactionId = null): bool
    {
        if ($this->isDraft()) {
            throw new \LogicException('Cannot complete a draft stocktake without starting it first.');
        }

        $updateData = [
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'completed_by' => $completedBy ?? auth()->id(),
        ];

        if ($adjustmentTransactionId) {
            $updateData['adjustment_transaction_id'] = $adjustmentTransactionId;
        }

        return $this->update($updateData);
    }

    public function start(): bool
    {
        if (!$this->isDraft()) {
            throw new \LogicException('Only draft stocktakes can be started.');
        }

        return $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);
    }

    public function cancel(?int $cancelledBy = null): bool
    {
        if ($this->isCompleted()) {
            throw new \LogicException('Cannot cancel a completed stocktake.');
        }

        return $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => $cancelledBy ?? auth()->id(),
        ]);
    }

    // ──────── ACCESSORS ────────

    public function getVarianceAttribute(): float
    {
        return $this->items->sum('variance');
    }

    public function getTotalVarianceValueAttribute(): float
    {
        return $this->items->sum(fn ($item) => $item->value_variance);
    }

    public function getTotalCountedValueAttribute(): float
    {
        return $this->items->sum(fn ($item) =>
            $item->counted_quantity * optional($item->variation)->default_sell_price
        );
    }

    public function getTotalCountedPurchaseValueAttribute(): float
    {
        return $this->items->sum(fn ($item) =>
            $item->counted_quantity * $item->purchase_price
        );
    }

    public function getTotalSystemValueAttribute(): float
    {
        return $this->items->sum(fn ($item) =>
            $item->system_quantity * optional($item->variation)->default_sell_price
        );
    }

    public function getStatusBadgeAttribute(): string
    {
        $status = $this->status ?? self::STATUS_DRAFT;

        $classes = [
            self::STATUS_COMPLETED   => 'badge-success',
            self::STATUS_IN_PROGRESS => 'badge-warning',
            self::STATUS_DRAFT       => 'badge-secondary',
            self::STATUS_CANCELLED   => 'badge-danger',
        ];

        $class = $classes[$status] ?? 'badge-light text-dark';
        $label = ucfirst(str_replace('_', ' ', $status));

        return '<span class="badge ' . $class . '">' . $label . '</span>';
    }

    public function getFormattedTransactionDateAttribute(): ?string
    {
        return optional($this->transaction_date)->format('d/m/Y H:i:s');
    }

    public function getFormattedCompletedAtAttribute(): ?string
    {
        return optional($this->completed_at)->format('d/m/Y H:i:s');
    }

    public function getFormattedStartedAtAttribute(): ?string
    {
        return optional($this->started_at)->format('d/m/Y H:i:s');
    }

    public function getFormattedCancelledAtAttribute(): ?string
    {
        return optional($this->cancelled_at)->format('d/m/Y H:i:s');
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->isCompleted();
    }

    public function getIsDraftAttribute(): bool
    {
        return $this->isDraft();
    }

    public function getIsInProgressAttribute(): bool
    {
        return $this->isInProgress();
    }

    public function getIsCancelledAttribute(): bool
    {
        return $this->isCancelled();
    }

    public function getProductCountAttribute(): int
    {
        return $this->items->count();
    }

    public function getAdjustmentRefAttribute(): string
    {
        if ($this->isCompleted() && $this->adjustment_transaction_id) {
            try {
                $transaction = Transaction::find($this->adjustment_transaction_id);
                if ($transaction) {
                    // Check if the route exists before trying to use it
                    if (\Route::has('stock-adjustments.show')) {
                        return '<a href="'.route('stock-adjustments.show', $transaction->id).'">'.$transaction->ref_no.'</a>';
                    } else {
                        // Fallback: just show the reference number without link
                        return $transaction->ref_no;
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Error loading adjustment transaction', [
                    'stocktake_id' => $this->id,
                    'transaction_id' => $this->adjustment_transaction_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        return '-';
    }

    // ──────── BUSINESS LOGIC ────────

    public function hasVariance(): bool
    {
        return $this->items()->where('variance', '!=', 0)->exists();
    }

    public function getItemsWithVariance()
    {
        return $this->items()
                    ->with(['variation', 'product'])
                    ->where('variance', '!=', 0)
                    ->get();
    }

    public function createAdjustment(array $adjustmentData = []): ?Transaction
    {
        if (!$this->isCompleted()) {
            throw new \LogicException('Cannot create adjustment for incomplete stocktake.');
        }

        $transaction = $this->adjustments()->create(array_merge([
            'business_id'       => $this->business_id,
            'location_id'       => $this->location_id,
            'type'              => 'stock_adjustment',
            'status'            => 'final',
            'payment_status'    => 'paid',
            'transaction_date'  => now(),
            'created_by'        => auth()->id(),
            'is_stocktake'      => 1,
            'additional_notes'  => 'Stocktake adjustment for ' . $this->reference_no,
        ], $adjustmentData));

        // Update the stocktake with the adjustment transaction ID
        if ($transaction) {
            $this->update(['adjustment_transaction_id' => $transaction->id]);
        }

        return $transaction;
    }

    public function canBeDeleted(): bool
    {
        return $this->isDraft() || ($this->isInProgress() && $this->items()->doesntExist());
    }

    public function canBeEdited(): bool
    {
        return !$this->isCompleted() && !$this->isCancelled();
    }

    public function canBeCompleted(): bool
    {
        return $this->isInProgress() && $this->items()->exists();
    }

    public function canBeCancelled(): bool
    {
        return !$this->isCompleted() && !$this->isCancelled();
    }

    public function getVarianceSummary(): array
    {
        $exactCount = 0;
        $overageCount = 0;
        $shortageCount = 0;
        $totalValueVariance = 0;

        foreach ($this->items as $item) {
            if ($item->variance == 0) {
                $exactCount++;
            } elseif ($item->variance > 0) {
                $overageCount++;
            } else {
                $shortageCount++;
            }

            $unit_price = optional($item->variation)->sell_price_inc_tax ?? 0;
            $totalValueVariance += $item->variance * $unit_price;
        }

        return [
            'exact_count' => $exactCount,
            'overage_count' => $overageCount,
            'shortage_count' => $shortageCount,
            'total_value_variance' => $totalValueVariance,
            'total_items' => $this->items->count(),
        ];
    }

    /**
     * Sync system quantities with current stock levels
     */
    public function syncSystemQuantities(): bool
    {
        if ($this->isCompleted()) {
            throw new \LogicException('Cannot sync system quantities for completed stocktake.');
        }

        try {
            foreach ($this->items as $item) {
                $currentSystemQty = $item->getCurrentSystemQuantity();
                $item->adjustSystemQuantity($currentSystemQty);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Error syncing system quantities', [
                'stocktake_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Add item to stocktake
     */
    public function addItem(array $itemData): StocktakeItem
    {
        if ($this->isCompleted()) {
            throw new \LogicException('Cannot add items to completed stocktake.');
        }

        return StocktakeItem::createForStocktake($this->id, $itemData);
    }

    /**
     * Remove item from stocktake
     */
    public function removeItem(int $itemId): bool
    {
        if ($this->isCompleted()) {
            throw new \LogicException('Cannot remove items from completed stocktake.');
        }

        return $this->items()->where('id', $itemId)->delete();
    }

    /**
     * Get stocktake statistics
     */
    public function getStatistics(): array
    {
        $itemsWithVariance = $this->items()->withVariance()->count();
        $totalVarianceValue = $this->getTotalVarianceValueAttribute();
        $varianceSummary = $this->getVarianceSummary();

        return [
            'total_items' => $this->items->count(),
            'items_with_variance' => $itemsWithVariance,
            'total_variance_value' => $totalVarianceValue,
            'variance_summary' => $varianceSummary,
            'completion_percentage' => $this->isCompleted() ? 100 : ($this->items->count() > 0 ? 75 : 0),
        ];
    }
}