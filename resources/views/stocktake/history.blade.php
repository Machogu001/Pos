@extends('layouts.app')

@section('title', __('stocktake.stocktake_history'))

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- ── PAGE HEADER ──────────────────────────────────────── --}}
    <div style="background:linear-gradient(135deg,#1d4ed8 0%,#4338ca 100%); border-radius:1rem; padding:1.25rem 1.5rem; margin-bottom:1.25rem; box-shadow:0 4px 18px rgba(29,78,216,.25);">
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem;">
            <div style="display:flex; align-items:center; gap:.875rem;">
                <div style="background:rgba(255,255,255,.18); border-radius:.75rem; width:2.75rem; height:2.75rem; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i class="fas fa-history" style="font-size:1.2rem; color:#fff;"></i>
                </div>
                <div>
                    <div style="font-size:.72rem; letter-spacing:.1em; text-transform:uppercase; color:rgba(255,255,255,.65); font-weight:700; margin-bottom:.2rem;">@lang('stocktake.stocktake_history')</div>
                    <h3 style="color:#fff; font-weight:700; margin:0; font-size:1.2rem;">@lang('stocktake.stocktake_history')</h3>
                    <p style="color:rgba(255,255,255,.75); margin:0; font-size:.82rem;">@lang('stocktake.stocktake_history_description')</p>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:.5rem; flex-wrap:wrap;">
                <a href="{{ route('stocktakes.index') }}" style="display:inline-flex; align-items:center; gap:.4rem; background:rgba(255,255,255,.15); color:#fff; font-size:.82rem; font-weight:500; padding:.45rem .9rem; border-radius:.5rem; text-decoration:none; border:1px solid rgba(255,255,255,.25);" onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'">
                    <i class="fas fa-list"></i> @lang('stocktake.stocktakes')
                </a>
                <a href="{{ route('stocktakes.variance_report') }}" style="display:inline-flex; align-items:center; gap:.4rem; background:rgba(255,255,255,.15); color:#fff; font-size:.82rem; font-weight:500; padding:.45rem .9rem; border-radius:.5rem; text-decoration:none; border:1px solid rgba(255,255,255,.25);" onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'">
                    <i class="fas fa-chart-bar"></i> @lang('stocktake.variance_report')
                </a>
                <div class="dropdown">
                    <button style="display:inline-flex; align-items:center; gap:.4rem; background:#f59e0b; color:#1a1a1a; font-size:.82rem; font-weight:600; padding:.45rem .9rem; border-radius:.5rem; border:none; cursor:pointer;" id="exportDropdown" data-toggle="dropdown" onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                        <i class="fas fa-file-export"></i> @lang('stocktake.export') <i class="fas fa-chevron-down" style="font-size:.65rem;"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown">
                        <li><a class="dropdown-item" href="#" onclick="exportHistory('excel')"><i class="fas fa-file-excel text-success me-2"></i> Excel</a></li>
                        <li><a class="dropdown-item" href="#" onclick="exportHistory('csv')"><i class="fas fa-file-csv text-info me-2"></i> CSV</a></li>
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
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(!$summary || $summary->stocktake_count == 0)
        <div class="alert alert-info">
            <div class="text-center py-4">
                <i class="fas fa-clipboard-list fa-3x text-info mb-3"></i>
                <h4 class="fw-semibold">@lang('stocktake.no_stocktake_data')</h4>
                <p class="text-muted mb-3">@lang('stocktake.no_stocktake_data_description')</p>
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <a href="{{ route('stocktakes.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i> @lang('stocktake.create_first_stocktake')
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
        <form id="history_filter_form" method="GET" action="{{ route('stocktakes.history') }}">
            @csrf
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">@lang('stocktake.location')</label>
                                        <select name="location_id" class="form-control select2" id="location_filter">
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
                                        <input type="date" name="date_from" class="form-control" id="date_from_filter"
                                            value="{{ request('date_from', now()->subDays(30)->format('Y-m-d')) }}"
                                            max="{{ now()->format('Y-m-d') }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">@lang('stocktake.date_to')</label>
                                        <input type="date" name="date_to" class="form-control" id="date_to_filter"
                                            value="{{ request('date_to', now()->format('Y-m-d')) }}"
                                            max="{{ now()->format('Y-m-d') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <div class="d-flex gap-2">
                                            <button type="submit" class="btn btn-primary flex-fill">
                                                <i class="fas fa-filter me-2"></i> @lang('stocktake.apply_filters')
                                            </button>
                                            <a href="{{ route('stocktakes.history') }}" id="reset_filters" class="btn btn-outline-secondary">
                                                <i class="fas fa-redo me-2"></i> @lang('stocktake.reset')
                                            </a>
                                        </div>
                                    </div>
                                </div>
        </form>
    </div>

    {{-- ── SUMMARY CARDS ─────────────────────────────────────────── --}}
            @if($summary && $summary->stocktake_count > 0)
            <div class="row g-3 mb-4">
                @php
                $accuracy_rate = 0;
                if(($summary->total_items ?? 0) > 0) {
                    $exact_count = $summary->exact_count ?? ($summary->total_items - $summary->overage_count - $summary->shortage_count);
                    $accuracy_rate = ($exact_count / $summary->total_items) * 100;
                }
                $summaryCards = [
                    ['mod'=>'primary', 'icon'=>'fas fa-clipboard-list', 'label'=>__('stocktake.stocktake_count'),         'value'=> $summary->stocktake_count ?? 0,                   'color'=>null],
                    ['mod'=>'info',    'icon'=>'fas fa-cubes',           'label'=>__('stocktake.total_items_counted'),      'value'=> number_format($summary->total_items ?? 0),         'color'=>null],
                    ['mod'=>'success', 'icon'=>'fas fa-plus',            'label'=>__('stocktake.overage_items'),            'value'=> $summary->overage_count ?? 0,                     'color'=>'#16a34a'],
                    ['mod'=>'danger',  'icon'=>'fas fa-minus',           'label'=>__('stocktake.shortage_items'),           'value'=> $summary->shortage_count ?? 0,                    'color'=>'#dc2626'],
                    ['mod'=>'warning', 'icon'=>'fas fa-balance-scale',   'label'=>__('stocktake.total_variance_amount'),    'value'=> (($summary->total_variance_amount ?? 0) != 0 ? (($summary->total_variance_amount > 0 ? '+' : '').number_format(abs($summary->total_variance_amount), 2)) : '0.00'), 'color'=> ($summary->total_variance_amount ?? 0) > 0 ? '#16a34a' : (($summary->total_variance_amount ?? 0) < 0 ? '#dc2626' : null)],
                    ['mod'=>'purple',  'icon'=>'fas fa-percentage',      'label'=>__('stocktake.accuracy_rate'),            'value'=> number_format($accuracy_rate, 1).'%',             'color'=>null],
                ];
                @endphp
                @foreach($summaryCards as $sc)
                <div class="col-xl-2 col-md-4 col-6">
                    <div class="stocktake-summary-card stocktake-summary-card--{{ $sc['mod'] }}" style="border-radius:1rem; padding:1rem 1.1rem; display:flex; align-items:center; gap:.75rem;">
                        <div class="stocktake-summary-card__icon" style="width:2.75rem; height:2.75rem; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:1rem;">
                            <i class="{{ $sc['icon'] }}"></i>
                        </div>
                        <div style="min-width:0;">
                            <p style="font-size:.7rem; margin:0 0 .1rem; font-weight:600; opacity:.7; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $sc['label'] }}</p>
                            <div class="stocktake-summary-card__value" style="font-size:1.35rem; font-weight:800; line-height:1; color:{{ $sc['color'] ?? '#1e293b' }};">{{ $sc['value'] }}</div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($stocktakeAccountingAudit['cutoff_date']) && $stocktakeAccountingAudit['rows']->isNotEmpty())
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                        <div>
                            <h5 class="mb-0">
                                <i class="fas fa-balance-scale me-2 text-primary"></i>
                                @lang('business.stocktake_accounting_rules')
                            </h5>
                            <p class="text-muted mb-0 small">
                                Cutoff date: <strong>{{ \Carbon\Carbon::parse($stocktakeAccountingAudit['cutoff_date'])->format('Y-m-d') }}</strong>
                                | Opening Stock Equity total: <strong>{{ number_format($stocktakeAccountingAudit['total_amount'], 2) }}</strong>
                            </p>
                        </div>
                        <span class="badge bg-light text-dark border px-3 py-2">
                            {{ $stocktakeAccountingAudit['rows']->count() }} stocktake batch{{ $stocktakeAccountingAudit['rows']->count() === 1 ? '' : 'es' }}
                        </span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="stocktake_audit_table">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Stocktake Ref</th>
                                    <th>Adjustment Ref</th>
                                    <th>Location</th>
                                    <th class="text-end">Amount</th>
                                    <th>Posting</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stocktakeAccountingAudit['rows'] as $auditRow)
                                <tr>
                                    <td class="ps-4">{{ \Carbon\Carbon::parse($auditRow->transaction_date)->format('Y-m-d') }}</td>
                                    <td><span class="fw-semibold text-primary">{{ $auditRow->stocktake_reference_no }}</span></td>
                                    <td>{{ $auditRow->adjustment_reference_no }}</td>
                                    <td>{{ $auditRow->location_name ?? '-' }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($auditRow->amount, 2) }}</td>
                                    <td>
                                        <span class="label {{ $auditRow->posting_type === 'credit' ? 'label-success' : 'label-warning' }}">
                                            {{ strtoupper($auditRow->posting_type) }} Opening Stock Equity
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- DataTable -->
            @if($summary && $summary->stocktake_count > 0)
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="mb-0">
                                <i class="fas fa-table me-2 text-primary"></i>
                                @lang('stocktake.adjustment_history')
                            </h5>
                            <p class="text-muted mb-0 small">@lang('stocktake.adjustment_history_description')</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="toggleTable('stocktake_history_table')" data-bs-toggle="tooltip" title="Expand view">
                                <i class="fas fa-expand-alt"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="exportHistory('excel')" data-bs-toggle="tooltip" title="Export to Excel">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0" style="max-height: 600px; overflow-y: auto; overflow-x: auto;">
                    <table class="table table-hover align-middle mb-0" id="stocktake_history_table">
                        <thead class="table-light" style="position: sticky; top: 0; z-index: 10; background-color: #f8f9fa;">
                                <tr>
                                    <th class="ps-4">@lang('stocktake.date')</th>
                                    <th>@lang('stocktake.reference_no')</th>
                                    <th>@lang('stocktake.product_name')</th>
                                    <th>@lang('stocktake.sku')</th>
                                    <th>@lang('stocktake.location')</th>
                                    <th class="text-end">@lang('stocktake.old_quantity')</th>
                                    <th class="text-end">@lang('stocktake.new_quantity')</th>
                                    <th class="text-end">@lang('stocktake.variance')</th>
                                    <th class="text-end">@lang('stocktake.variance_percentage')</th>
                                    <th class="text-end">@lang('stocktake.variance_amount')</th>
                                    <th>@lang('stocktake.adjusted_by')</th>
                                    <th class="text-center">@lang('stocktake.actions')</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
            </div>
        </div>
        </div>
            @endif
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

    .stocktake-summary-card--primary {
        background: linear-gradient(135deg, rgba(191, 219, 254, 1), rgba(219, 234, 254, 0.94)) !important;
        border: 1px solid rgba(37, 99, 235, 0.38) !important;
    }

    .stocktake-summary-card--info {
        background: linear-gradient(135deg, rgba(153, 246, 228, 1), rgba(207, 250, 254, 0.94)) !important;
        border: 1px solid rgba(6, 182, 212, 0.38) !important;
    }

    .stocktake-summary-card--success {
        background: linear-gradient(135deg, rgba(187, 247, 208, 1), rgba(220, 252, 231, 0.94)) !important;
        border: 1px solid rgba(34, 197, 94, 0.38) !important;
    }

    .stocktake-summary-card--danger {
        background: linear-gradient(135deg, rgba(252, 165, 165, 1), rgba(254, 226, 226, 0.94)) !important;
        border: 1px solid rgba(239, 68, 68, 0.38) !important;
    }

    .stocktake-summary-card--primary::before { background: linear-gradient(180deg, rgb(37, 99, 235), rgba(37, 99, 235, 0.5)); }
    .stocktake-summary-card--info::before { background: linear-gradient(180deg, rgb(6, 182, 212), rgba(6, 182, 212, 0.5)); }
    .stocktake-summary-card--success::before { background: linear-gradient(180deg, rgb(22, 163, 74), rgba(22, 163, 74, 0.5)); }
    .stocktake-summary-card--danger::before { background: linear-gradient(180deg, rgb(220, 38, 38), rgba(220, 38, 38, 0.5)); }
    .stocktake-summary-card--warning::before { background: linear-gradient(180deg, rgb(217, 119, 6), rgba(217, 119, 6, 0.5)); }
    .stocktake-summary-card--purple::before { background: linear-gradient(180deg, rgb(147, 51, 234), rgba(147, 51, 234, 0.5)); }

    .stocktake-summary-card--warning {
        background: linear-gradient(135deg, rgba(253, 224, 71, 1), rgba(255, 251, 235, 0.94)) !important;
        border: 1px solid rgba(245, 158, 11, 0.38) !important;
    }

    .stocktake-summary-card--purple {
        background: linear-gradient(135deg, rgba(216, 180, 254, 1), rgba(250, 245, 255, 0.94)) !important;
        border: 1px solid rgba(168, 85, 247, 0.38) !important;
    }

    .stocktake-summary-card__icon i {
        text-shadow: 0 1px 0 rgba(255, 255, 255, 0.55);
    }

    .stocktake-summary-card--primary .stocktake-summary-card__icon {
        background: rgba(37, 99, 235, 0.18) !important;
        color: rgb(37, 99, 235) !important;
    }

    .stocktake-summary-card--info .stocktake-summary-card__icon {
        background: rgba(6, 182, 212, 0.18) !important;
        color: rgb(8, 145, 178) !important;
    }

    .stocktake-summary-card--success .stocktake-summary-card__icon {
        background: rgba(34, 197, 94, 0.18) !important;
        color: rgb(22, 163, 74) !important;
    }

    .stocktake-summary-card--danger .stocktake-summary-card__icon {
        background: rgba(239, 68, 68, 0.18) !important;
        color: rgb(220, 38, 38) !important;
    }

    .stocktake-summary-card--warning .stocktake-summary-card__icon {
        background: rgba(245, 158, 11, 0.18) !important;
        color: rgb(217, 119, 6) !important;
    }

    .stocktake-summary-card--purple .stocktake-summary-card__icon {
        background: rgba(168, 85, 247, 0.18) !important;
        color: rgb(147, 51, 234) !important;
    }

    .stocktake-summary-card__value {
        letter-spacing: -0.03em;
    }

    .bg-light-primary { background-color: rgba(13, 110, 253, 0.1) !important; }
    .bg-light-info { background-color: rgba(23, 162, 184, 0.1) !important; }
    .bg-light-success { background-color: rgba(25, 135, 84, 0.1) !important; }
    .bg-light-danger { background-color: rgba(220, 53, 69, 0.1) !important; }
    .bg-light-warning { background-color: rgba(255, 193, 7, 0.1) !important; }
    .bg-light-secondary { background-color: rgba(108, 117, 125, 0.1) !important; }
    
    .table-success-light { background-color: rgba(25, 135, 84, 0.05) !important; }
    .table-danger-light { background-color: rgba(220, 53, 69, 0.05) !important; }
    
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

    .table th {
        border-top: none;
        font-weight: 600;
        color: #495057;
    }

    .select2-container--bootstrap-5 .select2-selection {
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
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

    .dataTables_wrapper .dataTables_processing {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
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

        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            text-align: left;
            margin-bottom: 0.5rem;
        }
    }

    @media print {
        .card-header, .btn, .form-group, .dropdown, .bg-light, .alert, .table-overlay,
        .dataTables_length, .dataTables_filter, .dataTables_info, .dataTables_paginate {
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

    // Paginate baseline accounting audit table
    if ($('#stocktake_audit_table').length) {
        $('#stocktake_audit_table').DataTable({
            pageLength: 10,
            lengthMenu: [[10, 20, 50, -1], [10, 20, 50, 'All']],
            order: [[0, 'asc']],
            dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
            language: {
                search: '_INPUT_',
                searchPlaceholder: 'Search…',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ batches',
                paginate: { first: 'First', last: 'Last', next: 'Next', previous: 'Previous' }
            },
            responsive: false
        });
    }

    // Initialize DataTable
    var table = $('#stocktake_history_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('stocktakes.data.history') }}",
            data: function (d) {
                d.location_id = $('#location_filter').val();
                d.date_from = $('#date_from_filter').val();
                d.date_to = $('#date_to_filter').val();
            }
        },
        columns: [
            { 
                data: 'created_at',
                name: 'created_at',
                render: function(data) {
                    return '<small class="text-muted d-block">' + 
                           new Date(data).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) +
                           '</small><small class="text-muted">' +
                           new Date(data).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }) +
                           '</small>';
                }
            },
            { 
                data: 'reference_no',
                name: 'reference_no',
                render: function(data, type, row) {
                    return '<span class="fw-semibold text-primary">' + data + '</span>';
                }
            },
            { 
                data: 'product_name_full',
                name: 'product_name_full',
                render: function(data, type, row) {
                    return '<div class="d-flex align-items-center">' +
                           '<div class="bg-light-primary rounded-circle p-2 me-3">' +
                           '<i class="fas fa-cube text-primary"></i></div>' +
                           '<div><div class="fw-medium text-dark">' + data + '</div>' +
                           (row.variation_name && row.variation_name != 'DUMMY' ? 
                           '<small class="text-muted">' + row.variation_name + '</small>' : '') +
                           '</div></div>';
                }
            },
            { 
                data: 'sku',
                name: 'sku',
                render: function(data) {
                    return '<code class="text-muted bg-light px-2 py-1 rounded">' + (data || 'N/A') + '</code>';
                }
            },
            { 
                data: 'location_name',
                name: 'location_name',
                render: function(data) {
                    return '<span class="badge bg-light text-dark"><i class="fas fa-store me-1"></i>' + (data || 'N/A') + '</span>';
                }
            },
            { 
                data: 'old_quantity',
                name: 'old_quantity',
                className: 'text-end fw-medium',
                render: function(data) {
                    return data ? parseFloat(data).toLocaleString() : '0';
                }
            },
            { 
                data: 'new_quantity',
                name: 'new_quantity',
                className: 'text-end fw-bold text-primary',
                render: function(data) {
                    return data ? parseFloat(data).toLocaleString() : '0';
                }
            },
            { 
                data: 'actual_adjustment',
                name: 'actual_adjustment',
                className: 'text-end fw-bold',
                render: function(data) {
                    if (data > 0) {
                        return '<span class="text-success"><i class="fas fa-arrow-up me-1"></i>+' + parseFloat(data).toLocaleString() + '</span>';
                    } else if (data < 0) {
                        return '<span class="text-danger"><i class="fas fa-arrow-down me-1"></i>' + parseFloat(data).toLocaleString() + '</span>';
                    } else {
                        return '<span class="text-muted">0</span>';
                    }
                }
            },
            { 
                data: 'variance_percentage',
                name: 'variance_percentage',
                className: 'text-end',
                render: function(data, type, row) {
                    // Accept numeric value or formatted string; null/undefined means N/A
                    if (data === null || data === undefined || data === 'N/A') {
                        return '<span class="badge bg-secondary rounded-pill">N/A</span>';
                    }

                    var percentage = Number(String(data).replace('%', '').trim());
                    if (!isFinite(percentage)) {
                        return '<span class="badge bg-secondary rounded-pill">N/A</span>';
                    }

                    var badgeClass = Math.abs(percentage) > 20 ? 'bg-danger' : 
                                   (Math.abs(percentage) > 10 ? 'bg-warning' : 'bg-success');
                    return '<span class="badge ' + badgeClass + ' rounded-pill">' + percentage.toFixed(1) + '%</span>';
                }
            },
            {
                data: 'variance_amount',
                name: 'variance_amount',
                className: 'text-end',
                render: function(data, type, row) {
                    if (data === null || data === undefined) return '-';
                    var num = parseFloat(data) || 0;
                    return num.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
            },
            { 
                data: 'adjusted_by',
                name: 'adjusted_by',
                render: function(data) {
                    return '<span class="badge bg-light text-dark">' + (data || 'System') + '</span>';
                }
            },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    return '<a href="' + (row.product_history_url || '#') + '" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="View Product History">' +
                           '<i class="fas fa-history"></i></a>';
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search...",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            },
            processing: '<div class="spinner-border text-primary" role="status"></div> Loading...'
        },
        responsive: true,
        stateSave: true,
        drawCallback: function(settings) {
            // Re-initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
            
            // Add row highlighting classes
            var api = this.api();
            $(api.table().body()).find('tr').each(function() {
                var data = api.row(this).data();
                if (data) {
                    var adjustment = parseFloat(data.actual_adjustment);
                    if (adjustment > 0) {
                        $(this).addClass('table-success-light');
                    } else if (adjustment < 0) {
                        $(this).addClass('table-danger-light');
                    }
                }
            });
        }
    });

    // Filter form submission
    $('#history_filter_form').on('submit', function(e) {
        e.preventDefault();
        table.draw();
        updateSummaryCards();
    });

    // Reset filters
    $('#reset_filters').on('click', function(e) {
        e.preventDefault();
        $('#location_filter').val('').trigger('change');
        $('#date_from_filter').val('{{ now()->subDays(30)->format("Y-m-d") }}');
        $('#date_to_filter').val('{{ now()->format("Y-m-d") }}');
        table.draw();
        updateSummaryCards();
    });

    // Auto-refresh data every 60 seconds
    setInterval(function() {
        if (document.visibilityState === 'visible') {
            table.ajax.reload(null, false);
            updateSummaryCards();
        }
    }, 60000);

    // Update summary cards via AJAX
    function updateSummaryCards() {
        $.ajax({
            url: "{{ route('stocktakes.stats.performance') }}",
            data: {
                location_id: $('#location_filter').val(),
                date_from: $('#date_from_filter').val(),
                date_to: $('#date_to_filter').val()
            },
            success: function(response) {
                if (response.success && response.metrics) {
                    var metrics = response.metrics;
                    
                    // Update summary cards
                    $('.card-body h4:contains("stocktake_count")').closest('.card-body').find('h4').text(metrics.stocktake_count || 0);
                    $('.card-body h4:contains("total_items_counted")').closest('.card-body').find('h4').text((metrics.total_items || 0).toLocaleString());
                    $('.card-body h4:contains("overage_items")').closest('.card-body').find('h4').text(metrics.overage_count || 0);
                    $('.card-body h4:contains("shortage_items")').closest('.card-body').find('h4').text(metrics.shortage_count || 0);
                    
                    // Update variance quantity with proper formatting
                    var varianceQty = metrics.total_variance_quantity || 0;
                    var varianceElement = $('.card-body h4:contains("total_variance_quantity")').closest('.card-body').find('h4');
                    varianceElement.text((varianceQty > 0 ? '+' : '') + varianceQty);
                    varianceElement.removeClass('text-success text-danger text-warning');
                    if (varianceQty > 0) {
                        varianceElement.addClass('text-success');
                    } else if (varianceQty < 0) {
                        varianceElement.addClass('text-danger');
                    } else {
                        varianceElement.addClass('text-warning');
                    }
                    
                    // Update accuracy rate
                    var accuracyElement = $('.card-body h4:contains("accuracy_rate")').closest('.card-body').find('h4');
                    accuracyElement.text((metrics.accuracy_rate || 0).toFixed(1) + '%');
                }
            }
        });
    }

    // Initialize tooltips
    if ($('[data-bs-toggle="tooltip"]').length > 0) {
        $('[data-bs-toggle="tooltip"]').tooltip();
    }
});

function toggleTable(wrapperId) {
    const wrapper = $('#' + wrapperId);
    
    if (wrapper.hasClass('table-expanded')) {
        wrapper.removeClass('table-expanded');
        $('.table-overlay').remove();
    } else {
        // Create overlay
        $('body').append('<div class="table-overlay" onclick="closeExpandedTables()"></div>');
        $('.table-overlay').fadeIn();
        
        // Expand table
        wrapper.addClass('table-expanded');
    }
}

function closeExpandedTables() {
    $('.table-expanded').removeClass('table-expanded');
    $('.table-overlay').remove();
}

function exportHistory(format) {
    // Get current filter values
    const locationId = $('#location_filter').val();
    const dateFrom = $('#date_from_filter').val();
    const dateTo = $('#date_to_filter').val();
    
    // Build export URL
    let url = "{{ route('stocktakes.export.history') }}?format=" + format;
    if (locationId) url += '&location_id=' + locationId;
    if (dateFrom) url += '&date_from=' + dateFrom;
    if (dateTo) url += '&date_to=' + dateTo;
    
    // Trigger download
    window.location.href = url;
}

// Keyboard shortcuts
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
        // Refresh data when page becomes visible
        var table = $('#stocktake_history_table').DataTable();
        if (table) {
            table.ajax.reload(null, false);
            updateSummaryCards();
        }
    }
});
</script>
@endsection