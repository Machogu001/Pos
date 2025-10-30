<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Stocktake extends Model
{
    use SoftDeletes;

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
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'transaction_date' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'status_badge',
        'total_variance_value',
        'formatted_transaction_date',
        'formatted_completed_at',
    ];

    // ──────── BOOT ────────

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->reference_no = $model->reference_no ?? static::generateReferenceNumber();
            $model->transaction_date = $model->transaction_date ?? now();
            $model->status = $model->status ?? 'draft';
        });

        static::deleting(function ($model) {
            if ($model->isForceDeleting()) {
                $model->items()->forceDelete();
                $model->adjustments()->forceDelete();
            }
        });
    }

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
        return $query->where('status', 'completed');
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
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
        return $query->whereHas('items', function ($q) {
            $q->where('variance', '!=', 0);
        });
    }

    // ──────── RELATIONSHIPS ────────

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id')
            ->withDefault(['name' => 'N/A']);
    }

    public function items()
    {
        return $this->hasMany(StocktakeItem::class)
            ->with(['product', 'variation']);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')
            ->withDefault(['user_full_name' => 'System']);
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by')
            ->withDefault(['user_full_name' => 'N/A']);
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function adjustments()
    {
        return $this->hasMany(Transaction::class, 'stocktake_id')
            ->where('type', 'stock_adjustment')
            ->with(['stock_adjustment_lines']);
    }

    // ──────── STATUS HELPERS ────────

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function complete(?int $completedBy = null): bool
    {
        return $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => $completedBy ?? auth()->id(),
        ]);
    }

    // ──────── ACCESSORS ────────

    public function getVarianceAttribute(): float
    {
        return $this->items->sum('variance');
    }

    public function getTotalVarianceValueAttribute(): float
    {
        return $this->items->sum('value_variance');
    }

    public function getTotalCountedValueAttribute(): float
    {
        return $this->items->sum(function ($item) {
            return $item->counted_quantity * $item->effective_price;
        });
    }

    public function getTotalSystemValueAttribute(): float
    {
        return $this->items->sum(function ($item) {
            return $item->system_quantity * $item->effective_price;
        });
    }

    public function getStatusBadgeAttribute(): string
    {
        $classes = [
            'completed' => 'bg-success',
            'in_progress' => 'bg-warning',
            'draft' => 'bg-secondary',
        ];

        $class = $classes[$this->status] ?? 'bg-light text-dark';

        return '<span class="badge ' . $class . '">' . ucfirst(str_replace('_', ' ', $this->status)) . '</span>';
    }

    public function getFormattedTransactionDateAttribute(): ?string
    {
        return $this->transaction_date?->format(config('constants.default_date_format', 'm/d/Y H:i'));
    }

    public function getFormattedCompletedAtAttribute(): ?string
    {
        return $this->completed_at?->format(config('constants.default_date_format', 'm/d/Y H:i'));
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
            return null;
        }

        return $this->adjustments()->create(array_merge([
            'business_id' => $this->business_id,
            'location_id' => $this->location_id,
            'type' => 'stock_adjustment',
            'status' => 'final',
            'payment_status' => 'paid',
            'transaction_date' => now(),
            'created_by' => auth()->id(),
            'additional_notes' => 'Stocktake adjustment for ' . $this->reference_no,
        ], $adjustmentData));
    }
}
