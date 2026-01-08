@extends('layouts.app')

@section('title', __('stocktake.stocktake') . ' - ' . $stocktake->reference_no)

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <!-- Card Header -->
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="fas fa-clipboard-list fs-4 text-primary me-3"></i>
                    <div>
                        <h3 class="mb-0 fw-semibold">@lang('stocktake.stocktake') - {{ $stocktake->reference_no }}</h3>
                        <p class="text-muted mb-0 small">
                            <i class="fas fa-store me-1"></i>{{ $stocktake->location->name }}
                            @if($stocktake->status === 'completed' && $stocktake->completed_at)
                                • <i class="fas fa-calendar-check me-1"></i>
                                {{ \Carbon\Carbon::parse($stocktake->completed_at)->format('M j, Y H:i') }}
                            @endif
                        </p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if($stocktake->status !== 'completed' && auth()->user()->can('stocktake.update'))
                        <a href="{{ route('stocktakes.edit', $stocktake->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-edit me-1"></i> @lang('messages.edit')
                        </a>
                    @endif
                    @if($stocktake->status === 'completed' && $stocktake->adjustment_transaction_id)
                        <button type="button" class="btn btn-sm btn-outline-info btn-modal" 
                                data-href="{{ route('stock-adjustment.show', $stocktake->adjustment_transaction_id) }}"
                                data-container=".view_modal">
                            <i class="fas fa-exchange-alt me-1"></i> @lang('stocktake.view_adjustment')
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Card Body -->
        <div class="card-body">
            <!-- Status Alert -->
            @if(session('status'))
                <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} alert-dismissible fade show mb-4" role="alert">
                    <i class="fas fa-{{ session('status.success') ? 'check-circle' : 'exclamation-triangle' }} me-2"></i>
                    {!! session('status.msg') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Summary Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 h-100 shadow-sm border-start border-3 border-primary">
                        <div class="card-body d-flex align-items-center">
                            <div class="bg-light-primary p-3 rounded-circle me-3">
                                <i class="fas fa-store fs-4 text-primary"></i>
                            </div>
                            <div>
                                <p class="mb-1 text-muted small">@lang('stocktake.location')</p>
                                <h6 class="mb-0 fw-semibold">{{ $stocktake->location->name }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card border-0 h-100 shadow-sm border-start border-3 border-info">
                        <div class="card-body d-flex align-items-center">
                            <div class="bg-light-info p-3 rounded-circle me-3">
                                <i class="far fa-calendar-alt fs-4 text-info"></i>
                            </div>
                            <div>
                                <p class="mb-1 text-muted small">@lang('stocktake.started_at')</p>
                                <h6 class="mb-0 fw-semibold">
                                    @if($stocktake->started_at)
                                        {{ \Carbon\Carbon::parse($stocktake->started_at)->format(session('business.date_format', 'd/m/Y') . ' H:i') }}
                                    @else
                                        -
                                    @endif
                                </h6>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card border-0 h-100 shadow-sm border-start border-3 {{ $stocktake->completed_at ? 'border-success' : 'border-secondary' }}">
                        <div class="card-body d-flex align-items-center">
                            <div class="bg-light-{{ $stocktake->completed_at ? 'success' : 'secondary' }} p-3 rounded-circle me-3">
                                <i class="far fa-calendar-check fs-4 text-{{ $stocktake->completed_at ? 'success' : 'secondary' }}"></i>
                            </div>
                            <div>
                                <p class="mb-1 text-muted small">@lang('stocktake.completed_at')</p>
                                <h6 class="mb-0 fw-semibold">
                                    @if($stocktake->completed_at)
                                        {{ \Carbon\Carbon::parse($stocktake->completed_at)->format(session('business.date_format', 'd/m/Y') . ' H:i') }}
                                    @else
                                        @lang('stocktake.not_completed')
                                    @endif
                                </h6>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 h-100 shadow-sm border-start border-3 border-warning">
                        <div class="card-body d-flex align-items-center">
                            <div class="bg-light-warning p-3 rounded-circle me-3">
                                <i class="fas fa-boxes fs-4 text-warning"></i>
                            </div>
                            <div>
                                <p class="mb-1 text-muted small">@lang('stocktake.total_items')</p>
                                <h6 class="mb-0 fw-semibold">{{ $stocktake->items->count() }}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status and User Information -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card border-start border-3 border-info">
                        <div class="card-body">
                            <h5 class="card-title text-muted fs-6 mb-3">@lang('stocktake.created_by')</h5>
                            <p class="mb-0 fs-5 fw-semibold">
                                <i class="fas fa-user-circle me-2 text-info"></i>
                                {{ $stocktake->createdBy->user_full_name ?? __('stocktake.unknown_user') }}
                            </p>
                            @if($stocktake->completedBy)
                            <p class="mb-0 text-muted small mt-2">
                                <i class="fas fa-user-check me-1 text-success"></i>
                                @lang('stocktake.completed_by'): {{ $stocktake->completedBy->user_full_name }}
                            </p>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card border-start border-3 
                        {{ $stocktake->status === 'completed' ? 'border-success' : 
                          ($stocktake->status === 'cancelled' ? 'border-danger' : 
                          ($stocktake->status === 'in_progress' ? 'border-warning' : 'border-secondary')) }}">
                        <div class="card-body">
                            <h5 class="card-title text-muted fs-6 mb-3">@lang('stocktake.status')</h5>
                            <p class="mb-0">
                                @include('stocktake.partials.status_badge', ['status' => $stocktake->status])
                            </p>
                            @if($stocktake->adjustment_transaction_id && $adjustmentTransaction)
                            <p class="mb-0 text-muted small mt-2">
                                <i class="fas fa-exchange-alt me-1 text-primary"></i>
                                @lang('stocktake.adjustment'): 
                                <a href="#" class="text-decoration-none btn-modal" 
                                   data-href="{{ route('stock-adjustment.show', $adjustmentTransaction->id) }}"
                                   data-container=".view_modal">
                                    {{ $adjustmentTransaction->ref_no }}
                                </a>
                            </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            @if($stocktake->additional_notes)
            <div class="alert alert-light border mb-4">
                <div class="d-flex">
                    <i class="fas fa-sticky-note text-info fs-4 me-3 mt-1"></i>
                    <div>
                        <h5 class="alert-heading mb-2">@lang('stocktake.notes')</h5>
                        <div class="text-muted">{{ $stocktake->additional_notes }}</div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Stocktake Summary - Only show for completed stocktakes -->
            @if($stocktake->status === 'completed')
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 h-100 shadow-sm border-start border-3 border-success">
                        <div class="card-body text-center">
                            <div class="text-success mb-2">
                                <i class="fas fa-check-circle fs-1"></i>
                            </div>
                            <h4 class="text-success fw-bold">{{ $exactCount }}</h4>
                            <p class="text-muted mb-0">@lang('stocktake.exact_match_items')</p>
                            <small class="text-muted">
                                @if($stocktake->items->count() > 0)
                                    {{ number_format(($exactCount / $stocktake->items->count()) * 100, 1) }}% @lang('stocktake.accuracy')
                                @else
                                    0% @lang('stocktake.accuracy')
                                @endif
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 h-100 shadow-sm border-start border-3 border-warning">
                        <div class="card-body text-center">
                            <div class="text-warning mb-2">
                                <i class="fas fa-plus-circle fs-1"></i>
                            </div>
                            <h4 class="text-warning fw-bold">{{ $overageCount }}</h4>
                            <p class="text-muted mb-0">@lang('stocktake.overage_items')</p>
                            <small class="text-muted">
                                @if($stocktake->items->count() > 0)
                                    {{ number_format(($overageCount / $stocktake->items->count()) * 100, 1) }}% @lang('stocktake.of_items')
                                @else
                                    0% @lang('stocktake.of_items')
                                @endif
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 h-100 shadow-sm border-start border-3 border-danger">
                        <div class="card-body text-center">
                            <div class="text-danger mb-2">
                                <i class="fas fa-minus-circle fs-1"></i>
                            </div>
                            <h4 class="text-danger fw-bold">{{ $shortageCount }}</h4>
                            <p class="text-muted mb-0">@lang('stocktake.shortage_items')</p>
                            <small class="text-muted">
                                @if($stocktake->items->count() > 0)
                                    {{ number_format(($shortageCount / $stocktake->items->count()) * 100, 1) }}% @lang('stocktake.of_items')
                                @else
                                    0% @lang('stocktake.of_items')
                                @endif
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Value and Variance Cards -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="tw-grid tw-grid-cols-1 tw-gap-4 sm:tw-grid-cols-2 sm:tw-gap-5">
                        
                        <!-- Total Value Card -->
                        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200">
                            <div class="tw-p-4 sm:tw-p-5">
                                <div class="tw-flex tw-items-center tw-gap-4">
                                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-12 tw-h-12 tw-rounded-full tw-shrink-0 tw-bg-blue-100 tw-text-blue-600">
                                        <i class="fas fa-coins tw-text-xl"></i>
                                    </div>
                                    <div class="tw-flex-1 tw-min-w-0">
                                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">
                                            @lang('stocktake.total_value')
                                        </p>
                                        <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-truncate tw-font-bold tw-tracking-tight">
                                            {{ session('currency.symbol') }}{{ number_format($totalValue, 2) }}
                                        </p>
                                        <p class="tw-text-xs tw-text-gray-500 tw-mt-1">
                                            @lang('stocktake.counted_stock_value')
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Value Variance Card -->
                        @if($totalValueVariance != 0)
                        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200">
                            <div class="tw-p-4 sm:tw-p-5">
                                <div class="tw-flex tw-items-center tw-gap-4">
                                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-12 tw-h-12 tw-rounded-full tw-shrink-0 {{ $totalValueVariance > 0 ? 'tw-bg-green-100 tw-text-green-600' : 'tw-bg-red-100 tw-text-red-600' }}">
                                        <i class="fas {{ $totalValueVariance > 0 ? 'fa-arrow-up' : 'fa-arrow-down' }} tw-text-xl"></i>
                                    </div>
                                    <div class="tw-flex-1 tw-min-w-0">
                                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">
                                            @lang('stocktake.total_value_variance')
                                        </p>
                                        <p class="tw-mt-0.5 tw-truncate tw-text-2xl tw-font-bold tw-tracking-tight {{ $totalValueVariance > 0 ? 'tw-text-green-600' : 'tw-text-red-600' }}">
                                            {{ $totalValueVariance > 0 ? '+' : '' }}{{ session('currency.symbol') }}{{ number_format(abs($totalValueVariance), 2) }}
                                        </p>
                                        <p class="tw-text-xs tw-text-gray-500 tw-mt-1">
                                            @if($totalValueVariance > 0)
                                                @lang('stocktake.positive_variance_value')
                                            @else
                                                @lang('stocktake.negative_variance_value')
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                    </div>
                </div>
            </div>
            @endif

            <!-- Stocktake Items Table -->
            <div class="card border-0 shadow-sm mt-2 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">
                            <i class="fas fa-boxes me-2 text-primary"></i>
                            @lang('stocktake.stocktake_items') ({{ $stocktake->items->count() }})
                        </h5>
                        @if($stocktake->status === 'completed')
                        <div class="text-muted small">
                            <i class="fas fa-info-circle me-1 text-info"></i>
                            @lang('stocktake.stock_adjustment_completed') 
                            @if($stocktake->completed_at)
                                {{ \Carbon\Carbon::parse($stocktake->completed_at)->format('M j, Y H:i') }}
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($stocktake->items->isEmpty())
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="fas fa-box-open fa-3x text-muted opacity-50"></i>
                            </div>
                            <h5 class="fw-semibold mb-2">@lang('stocktake.no_items_found')</h5>
                            <p class="text-muted">@lang('stocktake.no_items_description')</p>
                            @if($stocktake->status !== 'completed' && auth()->user()->can('stocktake.update'))
                                <a href="{{ route('stocktakes.edit', $stocktake->id) }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i> @lang('stocktake.add_items')
                                </a>
                            @endif
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="stocktake_items_table">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">@lang('stocktake.product_name')</th>
                                        @if($lot_enabled)
                                            <th>@lang('stocktake.product_lot_no')</th>
                                        @endif
                                        @if($expiry_enabled)
                                            <th>@lang('stocktake.product_expiry_date')</th>
                                        @endif
                                        <th>@lang('product.sku')</th>
                                        <th class="text-end">@lang('stocktake.system_quantity')</th>
                                        <th class="text-end">@lang('stocktake.counted_quantity')</th>
                                        <th class="text-end">@lang('stocktake.variance')</th>
                                        <th class="text-end">@lang('stocktake.variance_percentage')</th>
                                        <th class="text-end">@lang('stocktake.value_variance')</th>
                                        <th>@lang('stocktake.adjustment_type')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($stocktake->items as $item)
                                    @php
                                        $varianceType = $item->variance < 0 ? 'shortage' : ($item->variance > 0 ? 'overage' : 'exact');
                                        $rowClass = $item->variance < 0 ? 'table-danger-light' : ($item->variance > 0 ? 'table-success-light' : '');
                                        $textClass = $item->variance < 0 ? 'text-danger' : ($item->variance > 0 ? 'text-success' : 'text-muted');
                                        
                                        $unit_price = optional($item->variation)->sell_price_inc_tax ?? 0;
                                        $value_variance = $item->variance * $unit_price;
                                        
                                        // Calculate variance percentage
                                        $variancePercentage = 0;
                                        if ($item->system_quantity != 0) {
                                            $variancePercentage = ($item->variance / $item->system_quantity) * 100;
                                        } else if ($item->counted_quantity > 0) {
                                            $variancePercentage = 100; // Infinite variance when going from 0 to positive
                                        }
                                    @endphp
                                    <tr class="{{ $rowClass }}">
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                @if($item->product->image ?? false)
                                                    <div class="me-3">
                                                        <img src="{{ $item->product->image_url }}" alt="Product" class="rounded" width="40" height="40" style="object-fit: cover;">
                                                    </div>
                                                @else
                                                    <div class="me-3">
                                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                            <i class="fas fa-cube text-muted"></i>
                                                        </div>
                                                    </div>
                                                @endif
                                                <div>
                                                    <div class="fw-medium">{{ $item->product->name ?? 'N/A' }}</div>
                                                    @if(optional($item->variation)->name != 'DUMMY')
                                                        <small class="text-muted">{{ $item->variation->name }}</small>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        @if($lot_enabled)
                                            <td>
                                                @if($item->lot_number)
                                                    <span class="badge bg-light text-dark border">
                                                        {{ $item->lot_number }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        @endif
                                        @if($expiry_enabled)
                                            <td>
                                                @if($item->expiry_date)
                                                    @php
                                                        $expiryDate = \Carbon\Carbon::parse($item->expiry_date);
                                                        $isExpired = $expiryDate->isPast();
                                                        $isNearExpiry = $expiryDate->diffInDays(now()) <= 30;
                                                    @endphp
                                                    <span class="badge {{ $isExpired ? 'bg-danger' : ($isNearExpiry ? 'bg-warning' : 'bg-light text-dark border') }}">
                                                        {{ $expiryDate->format(session('business.date_format', 'd/m/Y')) }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        @endif
                                        <td>
                                            <code class="text-muted">{{ optional($item->variation)->sub_sku ?? 'N/A' }}</code>
                                        </td>
                                        <td class="text-end">
                                            <span class="text-muted">{{ number_format($item->system_quantity, 4) }}</span>
                                        </td>
                                        <td class="text-end fw-semibold">
                                            {{ number_format($item->counted_quantity, 4) }}
                                        </td>
                                        <td class="text-end fw-bold {{ $textClass }}">
                                            {{ $item->variance > 0 ? '+' : '' }}{{ number_format($item->variance, 4) }}
                                        </td>
                                        <td class="text-end">
                                            @if($item->system_quantity == 0 && $item->counted_quantity > 0)
                                                <span class="badge bg-info text-white" title="@lang('stocktake.infinite_variance_tooltip')">
                                                    ∞
                                                </span>
                                            @else
                                                <span class="badge bg-light text-dark">
                                                    {{ number_format($variancePercentage, 1) }}%
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold {{ $textClass }}">
                                            {{ $value_variance > 0 ? '+' : '' }}{{ session('currency.symbol') }}{{ number_format(abs($value_variance), 2) }}
                                        </td>
                                        <td>
                                            <span class="badge {{ $varianceType === 'overage' ? 'bg-success' : ($varianceType === 'shortage' ? 'bg-danger' : 'bg-info') }}">
                                                @lang('stocktake.' . $varianceType)
                                            </span>
                                            @if($stocktake->status === 'completed')
                                            <div class="text-muted small mt-1">
                                                <i class="fas fa-history me-1"></i>
                                                @lang('stocktake.adjusted')
                                            </div>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                @if($stocktake->status === 'completed')
                                <tfoot class="table-light">
                                    <tr>
                                        @php
                                            $colspan = 1 + ($lot_enabled ? 1 : 0) + ($expiry_enabled ? 1 : 0) + 1; // Product Name + Lot + Expiry + SKU
                                        @endphp
                                        <td colspan="{{ $colspan }}" class="fw-bold ps-4">
                                            @lang('stocktake.totals'):
                                        </td>
                                        <td class="text-end fw-bold">{{ number_format($stocktake->items->sum('system_quantity'), 4) }}</td>
                                        <td class="text-end fw-bold">{{ number_format($stocktake->items->sum('counted_quantity'), 4) }}</td>
                                        <td class="text-end fw-bold {{ $stocktake->items->sum('variance') < 0 ? 'text-danger' : ($stocktake->items->sum('variance') > 0 ? 'text-success' : 'text-muted') }}">
                                            {{ $stocktake->items->sum('variance') > 0 ? '+' : '' }}{{ number_format($stocktake->items->sum('variance'), 4) }}
                                        </td>
                                        <td class="text-end fw-bold">-</td>
                                        <td class="text-end fw-bold {{ $totalValueVariance < 0 ? 'text-danger' : ($totalValueVariance > 0 ? 'text-success' : 'text-muted') }}">
                                            {{ $totalValueVariance > 0 ? '+' : '' }}{{ session('currency.symbol') }}{{ number_format(abs($totalValueVariance), 2) }}
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                                @endif
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Stock History Section -->
            @if($stocktake->status === 'completed' && !empty($stockHistory) && count($stockHistory) > 0)
            <div class="card border-0 shadow-sm mt-4 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2 text-info"></i>
                        @lang('stocktake.stock_adjustment_history')
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>@lang('product.product')</th>
                                    <th>@lang('product.sku')</th>
                                    <th class="text-end">@lang('stocktake.old_quantity')</th>
                                    <th class="text-end">@lang('stocktake.new_quantity')</th>
                                    <th class="text-end">@lang('stocktake.adjustment')</th>
                                    <th>@lang('stocktake.adjusted_by')</th>
                                    <th>@lang('stocktake.date')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stockHistory as $history)
                                @php
                                    $history = (object) $history; // Convert to object if it's an array
                                @endphp
                                <tr>
                                    <td>
                                        {{ optional($history->product)->name ?? 'N/A' }}
                                        @if(optional($history->variation)->name && optional($history->variation)->name != 'DUMMY')
                                            - {{ optional($history->variation)->name }}
                                        @endif
                                    </td>
                                    <td>
                                        <code class="text-muted">
                                            {{ optional($history->variation)->sub_sku ?? optional($history->product)->sku ?? 'N/A' }}
                                        </code>
                                    </td>
                                    <td class="text-end">{{ number_format($history->old_quantity, 4) }}</td>
                                    <td class="text-end">{{ number_format($history->new_quantity, 4) }}</td>
                                    <td class="text-end fw-bold {{ $history->actual_adjustment > 0 ? 'text-success' : ($history->actual_adjustment < 0 ? 'text-danger' : 'text-muted') }}">
                                        {{ $history->actual_adjustment > 0 ? '+' : '' }}{{ number_format($history->actual_adjustment, 4) }}
                                    </td>
                                    <td>{{ $history->adjusted_by ?? 'System' }}</td>
                                    <td>
                                        @if(isset($history->created_at))
                                            {{ \Carbon\Carbon::parse($history->created_at)->format(session('business.date_format', 'd/m/Y H:i')) }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- History Link Section -->
            @if($stocktake->status === 'completed')
            <div class="alert alert-info border-0 mb-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-history fs-4 me-3 text-info"></i>
                    <div class="flex-grow-1">
                        <h6 class="alert-heading mb-1">@lang('stocktake.stock_adjustment_completed')</h6>
                        <p class="mb-0 small">
                            @lang('stocktake.inventory_adjusted') 
                            <a href="{{ route('stocktakes.history') }}?reference_no={{ $stocktake->reference_no }}" class="alert-link fw-semibold">
                                @lang('stocktake.view_history')
                            </a>
                            @lang('stocktake.for_complete_audit_trail').
                        </p>
                    </div>
                </div>
            </div>
            @endif

            <!-- Action Buttons -->
            <div class="d-flex align-items-center justify-content-between py-3 border-top">
                <a href="{{ route('stocktakes.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i> @lang('stocktake.back_to_list')
                </a>
                
                @if($stocktake->status !== 'completed' && auth()->user()->can('stocktake.complete'))
                    <div class="d-flex gap-2">
                        @if(auth()->user()->can('stocktake.update'))
                        <a href="{{ route('stocktakes.edit', $stocktake->id) }}" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i> @lang('messages.edit')
                        </a>
                        @endif
                        <button type="button" class="btn btn-success" id="complete_stocktake" 
                                data-url="{{ route('stocktakes.complete', $stocktake->id) }}">
                            <i class="fas fa-check-circle me-2"></i> @lang('stocktake.complete_stocktake')
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Initialize DataTable for stocktake items
    if ($('#stocktake_items_table').length && !$.fn.DataTable.isDataTable('#stocktake_items_table')) {
        $('#stocktake_items_table').DataTable({
            responsive: true,
            dom: "<'row'<'col-sm-12 col-md-6'B><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            buttons: [
                {
                    extend: 'excel',
                    className: 'btn btn-light border',
                    text: '<i class="fas fa-file-excel text-success me-2"></i> @lang("stocktake.export_stocktake")',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'pdf',
                    className: 'btn btn-light border',
                    text: '<i class="fas fa-file-pdf text-danger me-2"></i> PDF',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'print',
                    className: 'btn btn-light border',
                    text: '<i class="fas fa-print me-2"></i> @lang("stocktake.print_stocktake")',
                    exportOptions: {
                        columns: ':visible'
                    }
                },
                {
                    extend: 'colvis',
                    className: 'btn btn-light border',
                    text: '<i class="fas fa-columns me-2"></i> @lang("stocktake.columns")'
                }
            ],
            language: {
                search: '',
                searchPlaceholder: "@lang('stocktake.search_placeholder')",
                lengthMenu: "@lang('stocktake.show_entries') _MENU_",
                zeroRecords: '<div class="text-center py-4">' +
                                '<i class="fas fa-search fa-2x text-muted mb-3"></i>' +
                                '<h5 class="fw-semibold">@lang("stocktake.no_results_found")</h5>' +
                                '<p class="text-muted">@lang("stocktake.try_changing_search_or_filters")</p>' +
                             '</div>',
                info: "@lang('stocktake.showing_entries') _START_ @lang('stocktake.to') _END_ @lang('stocktake.of') _TOTAL_",
                infoEmpty: "@lang('stocktake.showing_entries') 0 @lang('stocktake.to') 0 @lang('stocktake.of') 0",
                paginate: {
                    first: '<i class="fas fa-angle-double-left"></i>',
                    last: '<i class="fas fa-angle-double-right"></i>',
                    next: '<i class="fas fa-angle-right"></i>',
                    previous: '<i class="fas fa-angle-left"></i>'
                }
            },
            order: [[0, 'asc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "@lang('stocktake.all')"]],
            columnDefs: [
                { responsivePriority: 1, targets: 0 }, // Product name
                { responsivePriority: 2, targets: -3 }, // Variance
                { responsivePriority: 3, targets: -4 }, // Counted quantity
                { responsivePriority: 4, targets: -5 }, // System quantity
                { orderable: false, targets: [1, 2, -1] } // Disable sorting for lot, expiry, adjustment type
            ],
            initComplete: function() {
                // Add custom search input styling
                $('.dataTables_filter input').addClass('form-control form-control-sm');
                $('.dataTables_length select').addClass('form-control form-control-sm');
            }
        });
    }

    // Complete Stocktake Confirmation
    $('#complete_stocktake').click(function() {
        const $button = $(this);
        const url = $button.data('url');
        
        Swal.fire({
            title: '@lang('stocktake.confirm_complete_title')',
            text: '@lang('stocktake.complete_confirmation_message')',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-check-circle me-1"></i> @lang('stocktake.yes_complete')',
            cancelButtonText: '<i class="fas fa-times me-1"></i> @lang('messages.cancel')',
            reverseButtons: true,
            backdrop: true,
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({})
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .catch(error => {
                    Swal.showValidationMessage(`Request failed: ${error}`);
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const response = result.value;
                if (response.success) {
                    Swal.fire({
                        title: '@lang('stocktake.completed_success')',
                        text: response.msg,
                        icon: 'success',
                        timer: 3000,
                        showConfirmButton: false,
                        willClose: () => {
                            if (response.redirect) {
                                window.location.href = response.redirect;
                            } else {
                                window.location.reload();
                            }
                        }
                    });
                } else {
                    Swal.fire({
                        title: '@lang('stocktake.error')',
                        text: response.msg || '@lang('stocktake.something_went_wrong')',
                        icon: 'error',
                        confirmButtonText: '@lang('messages.ok')'
                    });
                }
            }
        });
    });

    // Add row highlighting on hover for better UX
    $('#stocktake_items_table tbody tr').hover(
        function() {
            $(this).addClass('table-active');
        },
        function() {
            $(this).removeClass('table-active');
        }
    );

    // Auto-refresh page every 30 seconds if stocktake is in progress
    @if($stocktake->status === 'in_progress')
    setInterval(function() {
        window.location.reload();
    }, 30000);
    @endif
});
</script>

<style>
.table-danger-light {
    background-color: rgba(220, 53, 69, 0.05) !important;
}
.table-success-light {
    background-color: rgba(25, 135, 84, 0.05) !important;
}
.table-warning-light {
    background-color: rgba(255, 193, 7, 0.05) !important;
}

.bg-light-primary { background-color: rgba(13, 110, 253, 0.1) !important; }
.bg-light-info { background-color: rgba(23, 162, 184, 0.1) !important; }
.bg-light-success { background-color: rgba(25, 135, 84, 0.1) !important; }
.bg-light-danger { background-color: rgba(220, 53, 69, 0.1) !important; }
.bg-light-warning { background-color: rgba(255, 193, 7, 0.1) !important; }
.bg-light-secondary { background-color: rgba(108, 117, 125, 0.1) !important; }

.dataTables_wrapper .dataTables_filter input {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 0.375rem 0.75rem;
}

.dataTables_wrapper .dataTables_length select {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 0.375rem 2rem 0.375rem 0.75rem;
}

@media (max-width: 768px) {
    .card-body {
        padding: 1rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }
}
</style>
@endsection