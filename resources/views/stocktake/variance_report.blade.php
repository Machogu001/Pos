@extends('layouts.app')
@section('title', __('stocktake.variance_report'))

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- ── PAGE HEADER ──────────────────────────────────────── --}}
    <div style="background:linear-gradient(135deg,#1d4ed8 0%,#4338ca 100%); border-radius:1rem; padding:1.25rem 1.5rem; margin-bottom:1.25rem; box-shadow:0 4px 18px rgba(29,78,216,.25);">
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem;">
            <div style="display:flex; align-items:center; gap:.875rem;">
                <div style="background:rgba(255,255,255,.18); border-radius:.75rem; width:2.75rem; height:2.75rem; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i class="fas fa-chart-bar" style="font-size:1.2rem; color:#fff;"></i>
                </div>
                <div>
                    <div style="font-size:.72rem; letter-spacing:.1em; text-transform:uppercase; color:rgba(255,255,255,.65); font-weight:700; margin-bottom:.2rem;">@lang('stocktake.variance_report')</div>
                    <h3 style="color:#fff; font-weight:700; margin:0; font-size:1.2rem;">@lang('stocktake.variance_report')</h3>
                    <p style="color:rgba(255,255,255,.75); margin:0; font-size:.82rem;">@lang('stocktake.variance_report_description')</p>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:.5rem; flex-wrap:wrap;">
                <a href="{{ route('stocktakes.index') }}" style="display:inline-flex; align-items:center; gap:.4rem; background:rgba(255,255,255,.15); color:#fff; font-size:.82rem; font-weight:500; padding:.45rem .9rem; border-radius:.5rem; text-decoration:none; border:1px solid rgba(255,255,255,.25);" onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'">
                    <i class="fas fa-list"></i> @lang('stocktake.stocktakes')
                </a>
                <a href="{{ route('stocktakes.history') }}" style="display:inline-flex; align-items:center; gap:.4rem; background:rgba(255,255,255,.15); color:#fff; font-size:.82rem; font-weight:500; padding:.45rem .9rem; border-radius:.5rem; text-decoration:none; border:1px solid rgba(255,255,255,.25);" onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'">
                    <i class="fas fa-history"></i> @lang('stocktake.stocktake_history')
                </a>
                <div class="dropdown">
                    <button style="display:inline-flex; align-items:center; gap:.4rem; background:#f59e0b; color:#1a1a1a; font-size:.82rem; font-weight:600; padding:.45rem .9rem; border-radius:.5rem; border:none; cursor:pointer;" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false" onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                        <i class="fas fa-file-export"></i> @lang('stocktake.export') <i class="fas fa-chevron-down" style="font-size:.65rem;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown">
                        <li><a class="dropdown-item" href="#" onclick="confirmExport('excel')"><i class="fas fa-file-excel text-success me-2"></i> Excel</a></li>
                        <li><a class="dropdown-item" href="#" onclick="confirmExport('csv')"><i class="fas fa-file-csv text-info me-2"></i> CSV</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" onclick="window.print()"><i class="fas fa-print text-secondary me-2"></i> @lang('stocktake.print')</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- ── STATUS MESSAGES ─────────────────────────────────── --}}
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

    {{-- ── FILTERS ──────────────────────────────────────────────────────── --}}
    <div style="background:#fff; border-radius:.875rem; border:1px solid #e5e7eb; box-shadow:0 1px 4px rgba(0,0,0,.06); padding:1.25rem 1.5rem; margin-bottom:1.25rem;">
        <h6 style="font-weight:700; color:#374151; margin:0 0 1rem; display:flex; align-items:center; gap:.4rem;">
            <i class="fas fa-filter" style="color:#4f46e5;"></i> @lang('stocktake.filters')
        </h6>
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

            <!-- Summary Cards -->
            @if(($worstPerformers && !$worstPerformers->isEmpty()) || ($locationPerformance && !$locationPerformance->isEmpty()))
            <div class="row g-3 mb-4">
                @php
                $summaryCards = [
                    ['mod'=>'danger',  'icon'=>'fas fa-exclamation-triangle', 'label'=>__('stocktake.worst_performers'),   'value'=> $worstPerformers ? $worstPerformers->count() : 0,        'sub'=>__('stocktake.highest_variance_items')],
                    ['mod'=>'warning', 'icon'=>'fas fa-store',                'label'=>__('stocktake.locations_analyzed'), 'value'=> $locationPerformance ? $locationPerformance->count() : 0, 'sub'=>__('stocktake.performance_tracked')],
                    ['mod'=>'info',    'icon'=>'fas fa-chart-line',           'label'=>__('stocktake.recent_stocktakes'),  'value'=> $timeline ? $timeline->count() : 0,                       'sub'=>__('stocktake.last_30_days')],
                    ['mod'=>'success', 'icon'=>'fas fa-percentage',           'label'=>__('stocktake.avg_variance_rate'),  'value'=> number_format($averageVarianceRate ?? 0, 1).'%',           'sub'=>__('stocktake.across_all_locations')],
                ];
                @endphp
                @foreach($summaryCards as $sc)
                <div class="col-lg-3 col-md-6">
                    <div class="stocktake-summary-card stocktake-summary-card--{{ $sc['mod'] }}" style="border-radius:1rem; padding:1.25rem; display:flex; align-items:center; gap:1rem;">
                        <div class="stocktake-summary-card__icon" style="width:3rem; height:3rem; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:1.1rem;">
                            <i class="{{ $sc['icon'] }}"></i>
                        </div>
                        <div>
                            <p style="font-size:.78rem; margin:0 0 .15rem; font-weight:600; opacity:.75;">{{ $sc['label'] }}</p>
                            <div class="stocktake-summary-card__value" style="font-size:1.6rem; font-weight:800; line-height:1; color:#1e293b;">{{ $sc['value'] }}</div>
                            <small style="font-size:.72rem; opacity:.6;">{{ $sc['sub'] }}</small>
                        </div>
                    </div>
                </div>
                @endforeach
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
@endsection

@section('styles')
<style>
    .stocktake-detail-page {
        overflow-x: hidden;
    }

    .stocktake-detail-shell {
        border-radius: 1.25rem;
    }

    .stocktake-detail-hero {
        position: relative;
        color: #fff;
        background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 55%, #2563eb 100%);
        overflow: hidden;
    }

    .stocktake-detail-hero__glow {
        position: absolute;
        border-radius: 999px;
        filter: blur(18px);
        opacity: 0.28;
        pointer-events: none;
    }

    .stocktake-detail-hero__glow--left {
        width: 220px;
        height: 220px;
        left: -80px;
        top: -70px;
        background: rgba(255, 255, 255, 0.18);
    }

    .stocktake-detail-hero__glow--right {
        width: 280px;
        height: 280px;
        right: -110px;
        bottom: -130px;
        background: rgba(56, 189, 248, 0.24);
    }

    .stocktake-detail-hero__content,
    .stocktake-detail-hero__actions {
        position: relative;
        z-index: 1;
    }

    .stocktake-detail-hero__icon {
        width: 64px;
        height: 64px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.16);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        flex: 0 0 auto;
        backdrop-filter: blur(6px);
    }

    .stocktake-detail-hero__eyebrow {
        font-size: 0.75rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.7);
        font-weight: 700;
    }

    .stocktake-detail-hero__title {
        color: #fff;
        font-size: clamp(1.75rem, 2vw, 2.6rem);
        line-height: 1.05;
        font-weight: 800;
        letter-spacing: -0.03em;
        margin-bottom: 0.45rem;
    }

    .stocktake-detail-hero__description {
        color: rgba(255, 255, 255, 0.88);
        font-size: 1rem;
        line-height: 1.6;
    }

    .stocktake-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.45rem 0.8rem;
        border-radius: 999px;
        font-size: 0.86rem;
        font-weight: 600;
        backdrop-filter: blur(6px);
    }

    .stocktake-chip--primary {
        background: rgba(255, 255, 255, 0.18);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.18);
    }

    .stocktake-chip--soft {
        background: rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.92);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .stocktake-detail-hero__actions {
        background: linear-gradient(180deg, rgba(255,255,255,0.07), rgba(255,255,255,0.03));
        border-left: 1px solid rgba(255, 255, 255, 0.08);
    }

    .stocktake-detail-hero__actions .btn {
        border-radius: 0.95rem;
        padding: 0.85rem 1rem;
        font-weight: 700;
    }

    .stocktake-summary-card {
        border-radius: 1rem;
        overflow: hidden;
        position: relative;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.98)) !important;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
    }

    .stocktake-summary-card::before {
        content: '';
        position: absolute;
        inset: 0 auto 0 0;
        width: 0.55rem;
    }

    .stocktake-summary-card--danger {
        background: linear-gradient(135deg, rgba(252, 165, 165, 1), rgba(254, 226, 226, 0.94)) !important;
        border: 1px solid rgba(239, 68, 68, 0.38) !important;
    }

    .stocktake-summary-card--warning {
        background: linear-gradient(135deg, rgba(253, 224, 71, 1), rgba(255, 251, 235, 0.94)) !important;
        border: 1px solid rgba(245, 158, 11, 0.38) !important;
    }

    .stocktake-summary-card--info {
        background: linear-gradient(135deg, rgba(153, 246, 228, 1), rgba(207, 250, 254, 0.94)) !important;
        border: 1px solid rgba(34, 211, 238, 0.38) !important;
    }

    .stocktake-summary-card--success {
        background: linear-gradient(135deg, rgba(187, 247, 208, 1), rgba(220, 252, 231, 0.94)) !important;
        border: 1px solid rgba(34, 197, 94, 0.38) !important;
    }

    .stocktake-summary-card--danger::before { background: linear-gradient(180deg, rgb(220, 38, 38), rgba(220, 38, 38, 0.5)); }
    .stocktake-summary-card--warning::before { background: linear-gradient(180deg, rgb(217, 119, 6), rgba(217, 119, 6, 0.5)); }
    .stocktake-summary-card--info::before { background: linear-gradient(180deg, rgb(6, 182, 212), rgba(6, 182, 212, 0.5)); }
    .stocktake-summary-card--success::before { background: linear-gradient(180deg, rgb(22, 163, 74), rgba(22, 163, 74, 0.5)); }

    .stocktake-summary-card--danger .stocktake-summary-card__icon {
        background: rgba(239, 68, 68, 0.18) !important;
        color: rgb(220, 38, 38) !important;
    }

    .stocktake-summary-card--warning .stocktake-summary-card__icon {
        background: rgba(245, 158, 11, 0.18) !important;
        color: rgb(217, 119, 6) !important;
    }

    .stocktake-summary-card--info .stocktake-summary-card__icon {
        background: rgba(34, 211, 238, 0.18) !important;
        color: rgb(6, 182, 212) !important;
    }

    .stocktake-summary-card--success .stocktake-summary-card__icon {
        background: rgba(34, 197, 94, 0.18) !important;
        color: rgb(22, 163, 74) !important;
    }

    .stocktake-summary-card__value {
        letter-spacing: -0.03em;
    }

    .stocktake-summary-card__icon i {
        text-shadow: 0 1px 0 rgba(255, 255, 255, 0.55);
    }

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

        .stocktake-detail-hero__actions {
            border-left: 0;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
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