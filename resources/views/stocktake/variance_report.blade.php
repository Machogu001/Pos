@extends('layouts.app')
@section('title', __('stocktake.variance_report'))

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <!-- Card Header -->
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="fas fa-chart-bar fs-4 text-primary me-3"></i>
                    <div>
                        <h3 class="mb-0 fw-semibold">@lang('stocktake.variance_report')</h3>
                        <p class="text-muted mb-0 small">@lang('stocktake.variance_report_description')</p>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <a href="{{ route('stocktakes.history') }}" class="btn btn-info me-2">
                        <i class="fas fa-history me-2"></i> @lang('stocktake.stocktake_history')
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-success dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-file-export me-2"></i> @lang('stocktake.export')
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown">
                            <li>
                                <a class="dropdown-item" href="#" onclick="confirmExport('excel')">
                                    <i class="fas fa-file-excel text-success me-2"></i> Excel
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="confirmExport('csv')">
                                    <i class="fas fa-file-csv text-info me-2"></i> CSV
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="window.print()">
                                    <i class="fas fa-print text-secondary me-2"></i> @lang('stocktake.print')
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Body -->
        <div class="card-body">
            <!-- Status Messages -->
            @if(session('status'))
                <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} alert-dismissible fade show" role="alert">
                    <i class="fas fa-{{ session('status.success') ? 'check-circle' : 'exclamation-triangle' }} me-2"></i>
                    {!! session('status.msg') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(($worstPerformers && $worstPerformers->isEmpty()) && ($locationPerformance && $locationPerformance->isEmpty()))
                <div class="alert alert-info">
                    <div class="text-center py-4">
                        <i class="fas fa-chart-bar fa-3x text-info mb-3"></i>
                        <h4 class="fw-semibold">@lang('stocktake.no_variance_data')</h4>
                        <p class="text-muted mb-3">@lang('stocktake.no_variance_data_description')</p>
                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                            <a href="{{ route('stocktakes.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i> @lang('stocktake.create_stocktake')
                            </a>
                            <a href="{{ route('stocktakes.index') }}" class="btn btn-outline-primary">
                                <i class="fas fa-list me-2"></i> @lang('stocktake.view_stocktakes')
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Filters Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 bg-light">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-filter text-primary me-2"></i>@lang('stocktake.filters')
                            </h5>
                            <form id="variance_filter_form" method="GET" action="{{ route('stocktakes.variance_report') }}">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">@lang('stocktake.location')</label>
                                        <select name="location_id" class="form-control select2">
                                            <option value="">@lang('stocktake.all_locations')</option>
                                            @foreach($locations as $key => $value)
                                                <option value="{{ $key }}" 
                                                    {{ request('location_id') == $key ? 'selected' : '' }}>
                                                    {{ $value }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">@lang('stocktake.date_from')</label>
                                        <input type="date" name="date_from" class="form-control" 
                                            value="{{ request('date_from', now()->subDays(30)->format('Y-m-d')) }}"
                                            max="{{ now()->format('Y-m-d') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">@lang('stocktake.date_to')</label>
                                        <input type="date" name="date_to" class="form-control" 
                                            value="{{ request('date_to', now()->format('Y-m-d')) }}"
                                            max="{{ now()->format('Y-m-d') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">@lang('stocktake.price_basis')</label>
                                        <select name="price_basis" class="form-control">
                                            <option value="selling" {{ request('price_basis') == 'selling' ? 'selected' : '' }}>@lang('stocktake.price_basis_selling')</option>
                                            <option value="purchase" {{ request('price_basis') == 'purchase' ? 'selected' : '' }}>@lang('stocktake.price_basis_purchase')</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary flex-fill">
                                                <i class="fas fa-filter me-2"></i> @lang('stocktake.apply_filters')
                                            </button>
                                            <a href="{{ route('stocktakes.variance_report') }}" id="reset_filters" class="btn btn-outline-secondary">
                                                <i class="fas fa-redo me-2"></i> @lang('stocktake.reset')
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            @if(($worstPerformers && !$worstPerformers->isEmpty()) || ($locationPerformance && !$locationPerformance->isEmpty()))
            <div class="row g-3 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 h-100 shadow-sm border-start border-3 border-danger">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-light-danger p-3 rounded-circle me-3">
                                    <i class="fas fa-exclamation-triangle fs-4 text-danger"></i>
                                </div>
                                <div>
                                    <p class="mb-1 text-muted small">@lang('stocktake.worst_performers')</p>
                                    <h4 class="mb-0 fw-bold text-danger">{{ $worstPerformers ? $worstPerformers->count() : 0 }}</h4>
                                    <small class="text-muted">@lang('stocktake.highest_variance_items')</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 h-100 shadow-sm border-start border-3 border-warning">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-light-warning p-3 rounded-circle me-3">
                                    <i class="fas fa-store fs-4 text-warning"></i>
                                </div>
                                <div>
                                    <p class="mb-1 text-muted small">@lang('stocktake.locations_analyzed')</p>
                                    <h4 class="mb-0 fw-bold text-warning">{{ $locationPerformance ? $locationPerformance->count() : 0 }}</h4>
                                    <small class="text-muted">@lang('stocktake.performance_tracked')</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 h-100 shadow-sm border-start border-3 border-info">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-light-info p-3 rounded-circle me-3">
                                    <i class="fas fa-chart-line fs-4 text-info"></i>
                                </div>
                                <div>
                                    <p class="mb-1 text-muted small">@lang('stocktake.recent_stocktakes')</p>
                                    <h4 class="mb-0 fw-bold text-info">{{ $timeline ? $timeline->count() : 0 }}</h4>
                                    <small class="text-muted">@lang('stocktake.last_30_days')</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 h-100 shadow-sm border-start border-3 border-success">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-light-success p-3 rounded-circle me-3">
                                    <i class="fas fa-percentage fs-4 text-success"></i>
                                </div>
                                <div>
                                    <p class="mb-1 text-muted small">@lang('stocktake.avg_variance_rate')</p>
                                    <h4 class="mb-0 fw-bold text-success">{{ number_format($averageVarianceRate ?? 0, 1) }}%</h4>
                                    <small class="text-muted">@lang('stocktake.across_all_locations')</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Worst Performing Products -->
            @if($worstPerformers && !$worstPerformers->isEmpty())
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3 border-bottom">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h5 class="mb-0">
                                        <i class="fas fa-exclamation-circle text-danger me-2"></i>
                                        @lang('stocktake.worst_performing_products')
                                    </h5>
                                    <p class="text-muted mb-0 small">@lang('stocktake.top_10_highest_variance')</p>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary" onclick="toggleTable('worst_performers')" data-bs-toggle="tooltip" title="Expand view">
                                        <i class="fas fa-expand-alt"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" onclick="exportTableToExcel('worst_performers', 'worst_performers')" data-bs-toggle="tooltip" title="Export to Excel">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="worst_performers">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-4">@lang('stocktake.product_name')</th>
                                            <th>@lang('stocktake.sku')</th>
                                            <th class="text-end">@lang('stocktake.stocktake_count')</th>
                                            <th class="text-end">@lang('stocktake.avg_variance')</th>
                                            <th class="text-end">@lang('stocktake.max_variance')</th>
                                            <th class="text-end">@lang('stocktake.total_variance')</th>
                                            <th class="text-end">@lang('stocktake.value_amount')</th>
                                            <th class="text-center">@lang('stocktake.risk_level')</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($worstPerformers as $index => $product)
                                        <tr class="{{ $index % 2 === 0 ? 'table-light' : '' }}">
                                            <td class="ps-4">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-light-primary rounded-circle p-2 me-3">
                                                        <i class="fas fa-cube text-primary"></i>
                                                    </div>
                                                    <div>
                                                        @php
                                                            $pname = $product->product_name_full ?? ($product->product->name ?? ($product->product_name ?? 'N/A'));
                                                            $vname = $product->variation_name ?? ($product->variation->name ?? null);
                                                        @endphp
                                                        <div class="fw-medium text-dark">{{ $pname }}</div>
                                                        @if(!empty($vname) && $vname != 'DUMMY')
                                                            <small class="text-muted">{{ $vname }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $psku = $product->sku ?? ($product->variation->sub_sku ?? ($product->product->sku ?? 'N/A'));
                                                @endphp
                                                <code class="text-muted bg-light px-2 py-1 rounded">{{ $psku }}</code>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-secondary rounded-pill">{{ $product->stocktake_count ?? 0 }}</span>
                                            </td>
                                            <td class="text-end fw-bold {{ ($product->avg_variance ?? 0) > 5 ? 'text-danger' : (($product->avg_variance ?? 0) > 2 ? 'text-warning' : 'text-info') }}">
                                                {{ number_format($product->avg_variance ?? 0, 2) }}
                                            </td>
                                            <td class="text-end fw-bold text-danger">
                                                <i class="fas fa-arrow-up me-1"></i>{{ number_format($product->max_variance ?? 0, 2) }}
                                            </td>
                                            <td class="text-end fw-bold text-danger">
                                                <i class="fas fa-chart-bar me-1"></i>{{ number_format($product->total_variance ?? 0, 2) }}
                                            </td>
                                            <td class="text-end fw-bold text-dark">
                                                <i class="fas fa-money-bill-wave me-1"></i>{{ session('currency.symbol') }}{{ $product->variance_amount ?? ($product->variance_amount_raw ? number_format($product->variance_amount_raw, 2) : '0.00') }}
                                            </td>
                                            <td class="text-center">
                                                @if(($product->avg_variance ?? 0) > 10)
                                                    <span class="badge bg-danger rounded-pill py-2 px-3">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>@lang('stocktake.high_risk')
                                                    </span>
                                                @elseif(($product->avg_variance ?? 0) > 5)
                                                    <span class="badge bg-warning rounded-pill py-2 px-3">
                                                        <i class="fas fa-exclamation-circle me-1"></i>@lang('stocktake.medium_risk')
                                                    </span>
                                                @else
                                                    <span class="badge bg-info rounded-pill py-2 px-3">
                                                        <i class="fas fa-info-circle me-1"></i>@lang('stocktake.low_risk')
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @elseif($locationPerformance && !$locationPerformance->isEmpty())
                <div class="alert alert-warning">
                    <div class="text-center py-3">
                        <i class="fas fa-exclamation-triangle fa-2x text-warning mb-2"></i>
                        <h5 class="fw-semibold">@lang('stocktake.no_worst_performers')</h5>
                        <p class="text-muted mb-0">@lang('stocktake.no_worst_performers_description')</p>
                    </div>
                </div>
            @endif

            <!-- Location Performance and Timeline -->
            <div class="row g-4">
                <!-- Location Performance -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h5 class="mb-0">
                                        <i class="fas fa-store text-warning me-2"></i>
                                        @lang('stocktake.location_performance')
                                    </h5>
                                    <p class="text-muted mb-0 small">@lang('stocktake.last_30_days')</p>
                                </div>
                                @if($locationPerformance && !$locationPerformance->isEmpty())
                                <div class="d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary" onclick="toggleTable('location_performance')" data-bs-toggle="tooltip" title="Expand view">
                                        <i class="fas fa-expand-alt"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" onclick="exportTableToExcel('location_performance', 'location_performance')" data-bs-toggle="tooltip" title="Export to Excel">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="card-body p-0">
                            @if(!$locationPerformance || $locationPerformance->isEmpty())
                                <div class="text-center py-5">
                                    <i class="fas fa-store-alt fa-3x text-muted opacity-50 mb-3"></i>
                                    <h5 class="fw-semibold text-muted">@lang('stocktake.no_location_data')</h5>
                                    <p class="text-muted">@lang('stocktake.no_items_description')</p>
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" id="location_performance">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4">@lang('stocktake.location')</th>
                                                <th class="text-end">@lang('stocktake.stocktake_count')</th>
                                                <th class="text-end">@lang('stocktake.total_items')</th>
                                                <th class="text-end">@lang('stocktake.avg_variance_per_item')</th>
                                                <th class="text-end">@lang('stocktake.last_stocktake')</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($locationPerformance as $index => $location)
                                            <tr class="{{ $index % 2 === 0 ? 'table-light' : '' }}">
                                                <td class="ps-4 fw-medium">
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-light-warning rounded-circle p-2 me-3">
                                                            <i class="fas fa-store text-warning"></i>
                                                        </div>
                                                        <span class="text-dark">{{ $location->location_name ?? 'N/A' }}</span>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <span class="badge bg-secondary rounded-pill">{{ $location->stocktake_count ?? 0 }}</span>
                                                </td>
                                                <td class="text-end fw-medium">{{ number_format($location->total_items ?? 0) }}</td>
                                                <td class="text-end fw-bold {{ ($location->avg_variance_per_item ?? 0) > 5 ? 'text-danger' : (($location->avg_variance_per_item ?? 0) > 2 ? 'text-warning' : 'text-success') }}">
                                                    <i class="fas fa-chart-line me-1"></i>{{ number_format($location->avg_variance_per_item ?? 0, 2) }}
                                                </td>
                                                <td class="text-end">
                                                    @if($location->last_stocktake_date)
                                                        <span class="badge bg-light text-dark">
                                                            {{ \Carbon\Carbon::parse($location->last_stocktake_date)->format('M j, Y') }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">N/A</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Recent Stocktake Timeline -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 border-bottom">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h5 class="mb-0">
                                        <i class="fas fa-history text-info me-2"></i>
                                        @lang('stocktake.recent_stocktakes')
                                    </h5>
                                    <p class="text-muted mb-0 small">@lang('stocktake.latest_5_stocktakes')</p>
                                </div>
                                @if($timeline && !$timeline->isEmpty())
                                <button class="btn btn-sm btn-outline-primary" onclick="toggleTimeline()" data-bs-toggle="tooltip" title="Expand view">
                                    <i class="fas fa-expand-alt"></i>
                                </button>
                                @endif
                            </div>
                        </div>
                        <div class="card-body">
                            @if(!$timeline || $timeline->isEmpty())
                                <div class="text-center py-5">
                                    <i class="fas fa-clipboard-list fa-3x text-muted opacity-50 mb-3"></i>
                                    <h5 class="fw-semibold text-muted">@lang('stocktake.no_recent_stocktakes')</h5>
                                    <p class="text-muted">@lang('stocktake.no_items_description')</p>
                                </div>
                            @else
                                <div class="list-group list-group-flush" id="timeline_list">
                                    @foreach($timeline as $stocktake)
                                    <div class="list-group-item px-0 border-0 mb-3">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                    <h6 class="mb-1 fw-semibold">
                                                    @if(!empty($stocktake->id))
                                                        <a href="{{ route('stocktakes.show', $stocktake->id) }}" class="text-decoration-none text-dark">
                                                            {{ $stocktake->reference_no }}
                                                        </a>
                                                    @else
                                                        <span class="text-dark">{{ $stocktake->reference_no }}</span>
                                                    @endif
                                                </h6>
                                                <p class="mb-1 text-muted small">
                                                    <i class="fas fa-store me-1"></i>{{ $stocktake->location_name ?? 'N/A' }}
                                                </p>
                                                <div class="d-flex text-muted small flex-wrap gap-2">
                                                    <span class="badge bg-light text-dark">
                                                        <i class="fas fa-cube me-1"></i>{{ $stocktake->item_count ?? 0 }} @lang('stocktake.items')
                                                    </span>
                                                    <span class="badge bg-light text-dark">
                                                        <i class="fas fa-balance-scale me-1"></i>{{ number_format($stocktake->total_variance ?? 0, 2) }} @lang('stocktake.total_variance')
                                                    </span>
                                                    <span class="badge bg-light text-dark">
                                                        <i class="fas fa-money-bill-wave me-1"></i>{{ $stocktake->variance_amount ?? ($stocktake->variance_amount_raw ? number_format($stocktake->variance_amount_raw, 2) : '0.00') }} @lang('stocktake.value_amount')
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="text-end ms-3">
                                                <small class="text-muted d-block mb-1">
                                                    @if($stocktake->completed_at)
                                                        {{ \Carbon\Carbon::parse($stocktake->completed_at)->format('M j, Y') }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </small>
                                                <span class="badge bg-{{ ($stocktake->total_variance ?? 0) == 0 ? 'success' : (($stocktake->total_variance ?? 0) > 0 ? 'warning' : 'danger') }} rounded-pill py-2 px-3">
                                                    {{ number_format($stocktake->accuracy_rate ?? 0, 1) }}%
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .bg-light-primary { background-color: rgba(13, 110, 253, 0.1) !important; }
    .bg-light-info { background-color: rgba(23, 162, 184, 0.1) !important; }
    .bg-light-success { background-color: rgba(25, 135, 84, 0.1) !important; }
    .bg-light-danger { background-color: rgba(220, 53, 69, 0.1) !important; }
    .bg-light-warning { background-color: rgba(255, 193, 7, 0.1) !important; }
    
    .table-expanded {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 90%;
        height: 80%;
        z-index: 1050;
        background: white;
        border-radius: 8px;
        box-shadow: 0 0 20px rgba(0,0,0,0.3);
        overflow: auto;
    }
    
    .table-expanded .table-responsive {
        height: calc(100% - 60px);
    }
    
    .table-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1040;
        display: none;
    }

    .alert {
        border: none;
        border-radius: 8px;
    }

    .alert-info {
        background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
        color: #0c5460;
    }

    .alert-warning {
        background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
        color: #856404;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.075);
        transform: translateY(-1px);
        transition: all 0.2s ease;
    }

    .badge {
        font-size: 0.75em;
    }

    .card {
        transition: box-shadow 0.2s ease-in-out;
    }

    .card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }

    .btn {
        transition: all 0.2s ease-in-out;
    }

    .list-group-item {
        transition: all 0.2s ease;
        border-radius: 8px !important;
        margin-bottom: 0.5rem;
    }

    .list-group-item:hover {
        background-color: #f8f9fa;
        transform: translateX(5px);
    }

    @media (max-width: 768px) {
        .container-fluid {
            padding-left: 1rem;
            padding-right: 1rem;
        }
        
        .card-header .d-flex {
            flex-direction: column;
            gap: 1rem;
            align-items: flex-start !important;
        }
        
        .card-header .d-flex > div:last-child {
            width: 100%;
            justify-content: flex-start;
        }
        
        .row.g-3 > [class*="col-"] {
            margin-bottom: 1rem;
        }
        
        .table-responsive {
            font-size: 0.875rem;
        }
        
        .btn-group .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    }
    
    @media print {
        .card-header, .btn, .form-group, .dropdown, .bg-light, .alert, .table-overlay {
            display: none !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        .table-responsive {
            overflow: visible !important;
        }
        .card-body {
            padding: 0 !important;
        }
        .table th {
            background-color: #f8f9fa !important;
        }
    }

    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        border-radius: 8px;
    }
</style>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    // Initialize select2 for filters
    if ($.fn.select2) {
        $('.select2').select2({
            width: '100%',
            theme: 'bootstrap-5',
            placeholder: '@lang("stocktake.select_location")',
            allowClear: true
        });
    }

    // Filter form submission
    $('#variance_filter_form').on('submit', function(e) {
        e.preventDefault();
        
        const locationId = $('select[name="location_id"]').val();
        const dateFrom = $('input[name="date_from"]').val();
        const dateTo = $('input[name="date_to"]').val();
        
        let url = new URL(window.location.href);
        let params = new URLSearchParams(url.search);
        
        if (locationId) params.set('location_id', locationId);
        else params.delete('location_id');
        
        if (dateFrom) params.set('date_from', dateFrom);
        else params.delete('date_from');
        
        if (dateTo) params.set('date_to', dateTo);
        else params.delete('date_to');
        
        // Show loading state
        showLoadingState();
        
        window.location.href = url.pathname + '?' + params.toString();
    });

    // Reset filters - now using anchor tag
    $('#reset_filters').on('click', function(e) {
        e.preventDefault();
        showLoadingState();
        window.location.href = "{{ route('stocktakes.variance_report') }}";
    });

    // Add row highlighting
    $('table tbody tr').hover(
        function() {
            $(this).addClass('table-active');
        },
        function() {
            $(this).removeClass('table-active');
        }
    );

    // Initialize tooltips
    if ($('[data-bs-toggle="tooltip"]').length > 0) {
        $('[data-bs-toggle="tooltip"]').tooltip();
    }

    // Auto-refresh data every 60 seconds if page is visible
    setInterval(function() {
        if (document.visibilityState === 'visible') {
            // Check if we have any data and reload the page to get fresh data
            if ($('.card').length > 0 && !$('.loading-overlay').length) {
                window.location.reload();
            }
        }
    }, 60000);
});

function showLoadingState() {
    // Add loading overlay to the entire card
    $('.card').addClass('position-relative');
    $('.card').append(`
        <div class="loading-overlay">
            <div class="text-center">
                <div class="spinner-border text-primary mb-2" role="status"></div>
                <div class="text-muted">{{ __('stocktake.loading_variance_report') }}</div>
            </div>
        </div>
    `);
}

function toggleTable(tableId) {
    const table = $('#' + tableId);
    const tableContainer = table.closest('.table-responsive');
    
    if (tableContainer.hasClass('table-expanded')) {
        tableContainer.removeClass('table-expanded');
        $('.table-overlay').remove();
    } else {
        // Create overlay
        $('body').append('<div class="table-overlay" onclick="closeExpandedTables()"></div>');
        $('.table-overlay').fadeIn();
        
        // Expand table
        tableContainer.addClass('table-expanded');
        
        // Initialize DataTable if available and not already initialized
        if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#' + tableId)) {
            $('#' + tableId).DataTable({
                dom: "<'row'<'col-sm-12 col-md-6'B><'col-sm-12 col-md-6'f>>" +
                     "<'row'<'col-sm-12'tr>>" +
                     "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                buttons: [
                    {
                        extend: 'excel',
                        className: 'btn btn-light border',
                        text: '<i class="fas fa-file-excel text-success me-2"></i> Excel',
                        exportOptions: {
                            columns: ':visible',
                            format: {
                                body: function(data, row, column, node) {
                                    // Remove HTML tags for export
                                    return $(data).text() || data;
                                }
                            }
                        }
                    },
                    {
                        extend: 'print',
                        className: 'btn btn-light border',
                        text: '<i class="fas fa-print me-2"></i> Print',
                        exportOptions: {
                            columns: ':visible'
                        }
                    },
                    {
                        extend: 'colvis',
                        className: 'btn btn-light border',
                        text: '<i class="fas fa-columns me-2"></i> Columns'
                    }
                ],
                pageLength: 25,
                responsive: true,
                language: {
                    search: 'Search:',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: 'Showing 0 to 0 of 0 entries',
                    infoFiltered: '(filtered from _MAX_ total entries)',
                    paginate: {
                        first: 'First',
                        last: 'Last',
                        next: 'Next',
                        previous: 'Previous'
                    }
                }
            });
        }
    }
}

function toggleTimeline() {
    const timeline = $('#timeline_list');
    if (timeline.hasClass('table-expanded')) {
        timeline.removeClass('table-expanded');
        $('.table-overlay').remove();
    } else {
        $('body').append('<div class="table-overlay" onclick="closeExpandedTables()"></div>');
        $('.table-overlay').fadeIn();
        timeline.addClass('table-expanded');
    }
}

function closeExpandedTables() {
    $('.table-expanded').removeClass('table-expanded');
    $('.table-overlay').remove();
    
    // Destroy DataTables if they were initialized
    if ($.fn.DataTable) {
        $('.dataTable').each(function() {
            if ($.fn.DataTable.isDataTable(this)) {
                $(this).DataTable().destroy();
            }
        });
    }
}

function confirmExport(format) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '{{ __("stocktake.export_variance_report") }}',
            text: '{{ __("stocktake.export_variance_description") }}'.replace(':format', format.toUpperCase()),
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Export',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#198754',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                exportVarianceReport(format);
            }
        });
    } else {
        // Fallback to basic confirmation
        if (confirm('{{ __("stocktake.confirm_export_variance") }}'.replace(':format', format.toUpperCase()))) {
            exportVarianceReport(format);
        }
    }
}

function exportVarianceReport(format) {
    const locationId = $('select[name="location_id"]').val();
    const dateFrom = $('input[name="date_from"]').val();
    const dateTo = $('input[name="date_to"]').val();
    const priceBasis = $('select[name="price_basis"]').val();
    
    let url = "{{ route('stocktakes.quickExportVarianceReport') }}";
    let params = [];
    
    if (locationId) params.push('location_id=' + locationId);
    if (dateFrom) params.push('date_from=' + dateFrom);
    if (dateTo) params.push('date_to=' + dateTo);
    if (priceBasis) params.push('price_basis=' + priceBasis);
    if (format) params.push('format=' + format);
    
    if (params.length > 0) {
        url += '?' + params.join('&');
    }
    
    // Show loading indicator if SweetAlert is available
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '{{ __("stocktake.exporting_report") }}',
            text: '{{ __("stocktake.please_wait") }}',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }
    
    // Create a temporary form to trigger download
    const form = document.createElement('form');
    form.method = 'GET';
    form.action = url;
    form.style.display = 'none';
    
    document.body.appendChild(form);
    form.submit();
    
    // Remove the form after submission
    setTimeout(() => {
        document.body.removeChild(form);
        if (typeof Swal !== 'undefined') {
            Swal.close();
        }
    }, 1000);
}

function exportTableToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const html = table.outerHTML;
    
    // Create a Blob with the table data
    const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    
    // Create a download link
    const downloadLink = document.createElement('a');
    downloadLink.download = filename + '.xls';
    downloadLink.href = window.URL.createObjectURL(blob);
    downloadLink.style.display = 'none';
    
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// Keyboard shortcut for printing
$(document).on('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        window.print();
    }
    
    // Ctrl+R for reset filters
    if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
        e.preventDefault();
        $('#reset_filters').click();
    }
});

// Handle page visibility changes
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
        // Page became visible, you could trigger a soft refresh if needed
        console.log('Page is now visible');
    }
});
</script>
@endsection