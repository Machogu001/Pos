@extends('layouts.app')

@section('title', (session('business.name', config('app.name')) . ' - ' . __('stocktake.stocktake') . ' - ' . $stocktake->reference_no))

@section('content')
<style>
    .stocktake-page {
        max-width: 1500px;
        margin: 0 auto;
    }
    .stocktake-shell {
        background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }
    .stocktake-hero {
        background:
            radial-gradient(circle at top right, rgba(59, 130, 246, 0.10), transparent 24%),
            linear-gradient(180deg, #ffffff 0%, #f3f8ff 100%);
        color: #0f172a;
        padding: 28px 30px;
        border-bottom: 1px solid #e2e8f0;
    }
    .stocktake-hero-topline {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 18px;
        align-items: flex-start;
    }
    .stocktake-hero h1 {
        margin: 0;
        font-size: 1.55rem;
        font-weight: 700;
        letter-spacing: 0.2px;
        color: #0f172a;
    }
    .stocktake-hero-subtitle {
        color: #475569;
        margin-top: 8px;
        font-size: 0.95rem;
        font-weight: 500;
    }
    .stocktake-hero-detail {
        margin-top: 12px;
        color: #64748b;
        font-size: 0.95rem;
    }
    .stocktake-status-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 999px;
        background: #eff6ff;
        border: 1px solid #dbeafe;
        font-weight: 500;
        color: #0f172a;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
    }
    .stocktake-actionbar {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
        align-items: center;
    }
    .stocktake-actionbar .btn {
        border-radius: 12px;
        padding: 0.72rem 1rem;
        font-weight: 600;
        letter-spacing: 0.01em;
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.04);
    }
    .stocktake-actionbar .dropdown-menu {
        border-radius: 14px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 16px 30px rgba(15, 23, 42, 0.12);
        min-width: 220px;
    }
    .stocktake-actionbar .dropdown-item {
        padding-top: 0.7rem;
        padding-bottom: 0.7rem;
    }
    .stocktake-content {
        padding: 24px 26px 30px;
    }
    .stocktake-metrics-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }
    .stocktake-metric {
        background: linear-gradient(180deg, #ffffff 0%, #fcfcfd 100%);
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 18px 18px;
        height: 100%;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.035);
    }
    .stocktake-metric-label {
        color: #6b7280;
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin-bottom: 8px;
        font-weight: 600;
    }
    .stocktake-metric-value {
        color: #111827;
        font-size: 1rem;
        font-weight: 500;
        line-height: 1.5;
    }
    .stocktake-metric-value strong {
        font-weight: 700;
    }
    .stocktake-status-text {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #0f172a;
        font-weight: 700;
    }
    .stocktake-toolbar {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 16px;
    }
    .stocktake-search-wrap {
        width: 100%;
        max-width: 460px;
    }
    .stocktake-search-wrap .input-group-text,
    .stocktake-search-wrap .form-control,
    .stocktake-search-wrap .btn {
        border-radius: 12px;
    }
    .stocktake-search-wrap .input-group-text {
        background: #f8fafc;
        border-color: #d1d5db;
        color: #64748b;
    }
    .stocktake-search-wrap .form-control {
        height: 46px;
        border-color: #d1d5db;
        box-shadow: none;
        padding-left: 14px;
        padding-right: 14px;
    }
    .stocktake-search-wrap .btn {
        height: 46px;
        padding-left: 16px;
        padding-right: 16px;
    }
    .stocktake-search-wrap .form-control:focus {
        border-color: #93c5fd;
        box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.12);
    }
    .stocktake-table-card {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.05);
    }
    .stocktake-table-card .table {
        margin-bottom: 0;
    }
    .stocktake-table-card thead th {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        color: #fff;
        border-color: #334155;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        padding-top: 14px;
        padding-bottom: 14px;
    }
    .stocktake-table-card tbody td {
        vertical-align: middle;
        border-color: #edf2f7;
        padding-top: 14px;
        padding-bottom: 14px;
    }
    .stocktake-table-card tbody tr:hover {
        background: #f8fafc;
    }
    .stocktake-table-card input.form-control {
        border-radius: 10px;
        border-color: #d1d5db;
    }
    .stocktake-table-card input.form-control:focus {
        border-color: #93c5fd;
        box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.10);
    }
    .variance-badge {
        font-size: 0.9em;
        min-width: 64px;
        display: inline-block;
        padding: 0.45rem 0.7rem;
        border-radius: 999px;
        font-weight: 700;
        border: 1px solid transparent;
    }
    .variance-badge.variance-negative {
        color: #b91c1c !important;
        background: #fee2e2 !important;
        border-color: #fecaca !important;
    }
    .variance-badge.variance-positive {
        color: #166534 !important;
        background: #dcfce7 !important;
        border-color: #bbf7d0 !important;
    }
    .variance-badge.variance-neutral {
        color: #475569 !important;
        background: #e2e8f0 !important;
        border-color: #cbd5e1 !important;
    }
    .empty-state {
        padding: 3rem;
        text-align: center;
        background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
        border-radius: 16px;
        width: 100%;
        border: 1px dashed #cbd5e1;
    }
    .empty-state-icon {
        font-size: 3rem;
        color: #94a3b8;
        margin-bottom: 1rem;
    }
    .empty-state h4 {
        margin-bottom: 0.5rem;
        color: #111827;
        font-weight: 700;
    }
    .empty-state-subtext {
        color: #64748b;
        margin-bottom: 1.5rem;
    }
    .busy-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.58);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
        color: white;
        font-size: 1.5rem;
        backdrop-filter: blur(4px);
    }
    .stocktake-footer-actions {
        display: flex;
        justify-content: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .stocktake-footer-actions .btn {
        border-radius: 12px;
        padding: 0.8rem 1.5rem;
        font-weight: 600;
        letter-spacing: 0.01em;
        min-width: 160px;
    }
    .stocktake-footer-actions .btn-primary {
        box-shadow: 0 8px 20px rgba(37, 99, 235, 0.18);
    }
    .stocktake-footer-actions .btn-default {
        border-color: #d1d5db;
        color: #334155;
        background: #fff;
    }
    .stocktake-section-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
    }
    .stocktake-section-title h4 {
        margin: 0;
        font-size: 1.02rem;
        font-weight: 700;
        color: #0f172a;
    }
    .stocktake-section-title .count {
        color: #64748b;
        font-size: 0.9rem;
    }
    .stocktake-note-card {
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        background: #fff;
        padding: 18px 20px;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.035);
    }
    .stocktake-note-card .label {
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-size: 0.8rem;
        font-weight: 700;
        margin-bottom: 8px;
    }
    .stocktake-note-card .value {
        color: #111827;
        font-weight: 500;
        line-height: 1.55;
    }
    .stocktake-print-summary {
        display: none;
    }
    .stocktake-print-summary-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin: 0 0 18px;
    }
    .stocktake-print-summary-item {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 12px 14px;
        background: #fff;
    }
    .stocktake-print-summary-label {
        color: #64748b;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin-bottom: 4px;
        font-weight: 700;
    }
    .stocktake-print-summary-value {
        color: #111827;
        font-size: 0.95rem;
        font-weight: 600;
        line-height: 1.45;
    }
    @media print {
        body {
            background: #fff !important;
        }
        .stocktake-page {
            max-width: 100% !important;
            padding: 0 !important;
        }
        .stocktake-shell {
            box-shadow: none !important;
            border: 0 !important;
            border-radius: 0 !important;
        }
        .stocktake-actionbar,
        .stocktake-hero,
        .stocktake-metrics-grid,
        .stocktake-note-card,
        .stocktake-section-title,
        .stocktake-toolbar,
        .stocktake-footer-actions,
        #busy-overlay,
        .main-header,
        .main-sidebar,
        .content-header,
        .navbar,
        footer,
        .breadcrumb,
        .btn,
        #complete_stocktake {
            display: none !important;
        }
        .stocktake-table-card,
        .stocktake-metric,
        .stocktake-note-card {
            box-shadow: none !important;
        }
        .stocktake-table-card {
            border: 1px solid #d1d5db !important;
        }
        .stocktake-table-card thead th {
            background: #e5e7eb !important;
            color: #111827 !important;
            font-size: 0.72rem !important;
            padding-top: 8px !important;
            padding-bottom: 8px !important;
        }
        .stocktake-table-card tbody td {
            font-size: 0.78rem !important;
            padding-top: 8px !important;
            padding-bottom: 8px !important;
        }
        .stocktake-table-card .form-control {
            height: auto !important;
            min-height: 0 !important;
            padding: 0.25rem 0.45rem !important;
            border: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            font-size: 0.78rem !important;
        }
        .stocktake-table-card .badge {
            padding: 0.3rem 0.45rem !important;
            min-width: 52px !important;
            font-size: 0.76rem !important;
        }
        .stocktake-print-summary {
            display: block !important;
        }
        .stocktake-print-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
    }
    @media (max-width: 1199px) {
        .stocktake-metrics-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 767px) {
        .stocktake-page {
            padding: 0 8px;
        }
        .stocktake-shell {
            border-radius: 14px;
        }
        .stocktake-hero,
        .stocktake-content {
            padding-left: 16px;
            padding-right: 16px;
        }
        .stocktake-metrics-grid {
            grid-template-columns: 1fr;
        }
        .stocktake-footer-actions .btn {
            width: 100%;
            min-width: 0;
        }
        .stocktake-search-wrap {
            max-width: 100%;
        }
    }
</style>

<div class="stocktake-page">
    <div class="stocktake-shell">
        <div class="stocktake-hero">
            @php
                $statusColors = [
                    'completed' => 'success',
                    'in_progress' => 'warning',
                    'pending' => 'info',
                    'cancelled' => 'danger'
                ];
                $color = $statusColors[$stocktake->status] ?? 'secondary';
                $statusLabel = $stocktake->status == 'in_progress' ? __('stocktake.in_progress') : ($stocktake->status == 'completed' ? __('stocktake.completed') : ($stocktake->status == 'pending' ? __('stocktake.pending') : ($stocktake->status == 'cancelled' ? __('stocktake.cancelled') : ucfirst(str_replace('_', ' ', $stocktake->status)))));
            @endphp
            <div class="stocktake-hero-topline">
                <div>
                    <h1><i class="fas fa-clipboard-list mr-2 text-primary"></i>@lang('stocktake.stocktake') - {{ $stocktake->reference_no }}</h1>
                    <div class="stocktake-hero-subtitle">{{ $stocktake->location->name }}</div>
                    <div class="stocktake-hero-detail">
                        {{ __('business.business_location') }}: <strong>{{ $stocktake->location->name }}</strong>
                    </div>
                </div>
                <div class="text-right">
                    <div class="stocktake-status-pill mb-2">
                        <i class="fas fa-circle text-{{ $color }}" style="font-size: .55rem;"></i>
                        {{ $statusLabel }}
                    </div>
                    <div class="stocktake-actionbar">
                        <button type="button" class="btn btn-outline-primary" id="stocktake_export_btn">
                            <i class="fas fa-file-export mr-1"></i> @lang('stocktake.export_stocktake')
                        </button>
                        <a href="javascript:window.print();" class="btn btn-outline-secondary">
                            <i class="fas fa-print mr-1"></i> @lang('stocktake.print_stocktake')
                        </a>
                        <a href="{{ route('stocktakes.index') }}" class="btn btn-light border">
                            <i class="fas fa-list mr-1 text-primary"></i> @lang('stocktake.view_stocktakes')
                        </a>
                    </div>
                    @if($stocktake->status == 'in_progress')
                    <div class="mt-2">
                        <button type="button" class="btn btn-success btn-lg" id="complete_stocktake">
                            <i class="fas fa-check-circle"></i> @lang('stocktake.complete_stocktake')
                        </button>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="stocktake-content">
            <div class="stocktake-print-summary">
                <h2 style="margin: 0 0 8px; font-size: 1.25rem; font-weight: 700; color: #0f172a;">
                    {{ session('business.name', config('app.name')) }} - @lang('stocktake.stocktake') - {{ $stocktake->reference_no }}
                </h2>
                <div style="color: #64748b; margin-bottom: 14px;">
                    {{ $stocktake->location->name }}
                </div>
                <div class="stocktake-print-summary-grid">
                    <div class="stocktake-print-summary-item">
                        <div class="stocktake-print-summary-label">@lang('business.business_location')</div>
                        <div class="stocktake-print-summary-value">{{ $stocktake->location->name }}</div>
                    </div>
                    <div class="stocktake-print-summary-item">
                        <div class="stocktake-print-summary-label">@lang('stocktake.started_at')</div>
                        <div class="stocktake-print-summary-value">{{ \Carbon\Carbon::parse($stocktake->transaction_date)->format(session('business.date_format', 'm/d/Y') . ' H:i') }}</div>
                    </div>
                    <div class="stocktake-print-summary-item">
                        <div class="stocktake-print-summary-label">@lang('stocktake.completed_at')</div>
                        <div class="stocktake-print-summary-value">
                            {{ $stocktake->completed_at ? \Carbon\Carbon::parse($stocktake->completed_at)->format(session('business.date_format', 'm/d/Y') . ' H:i') : __('stocktake.not_completed') }}
                        </div>
                    </div>
                    <div class="stocktake-print-summary-item">
                        <div class="stocktake-print-summary-label">@lang('stocktake.status')</div>
                        <div class="stocktake-print-summary-value">{{ $statusLabel }}</div>
                    </div>
                    <div class="stocktake-print-summary-item">
                        <div class="stocktake-print-summary-label">@lang('stocktake.created_by')</div>
                        <div class="stocktake-print-summary-value">{{ $stocktake->createdBy?->user_full_name ?? $stocktake->createdBy?->username ?? '—' }}</div>
                    </div>
                    <div class="stocktake-print-summary-item">
                        <div class="stocktake-print-summary-label">@lang('stocktake.additional_notes')</div>
                        <div class="stocktake-print-summary-value">{{ $stocktake->additional_notes ?: '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="stocktake-metrics-grid mb-4">
                <div class="stocktake-metric">
                    <div class="stocktake-metric-label"><i class="fas fa-store mr-1"></i>@lang('business.business_location')</div>
                    <div class="stocktake-metric-value">{{ $stocktake->location->name }}</div>
                </div>
                <div class="stocktake-metric">
                    <div class="stocktake-metric-label"><i class="far fa-clock mr-1"></i>@lang('stocktake.started_at')</div>
                    <div class="stocktake-metric-value">{{ \Carbon\Carbon::parse($stocktake->transaction_date)->format(session('business.date_format', 'm/d/Y') . ' H:i') }}</div>
                </div>
                <div class="stocktake-metric">
                    <div class="stocktake-metric-label"><i class="fas fa-boxes mr-1"></i>@lang('stocktake.stocktake')</div>
                    <div class="stocktake-metric-value"><strong>{{ $stocktake->items->count() }}</strong> items</div>
                </div>
                <div class="stocktake-metric">
                    <div class="stocktake-metric-label"><i class="fas fa-user mr-1"></i>@lang('stocktake.created_by')</div>
                    <div class="stocktake-metric-value">{{ $stocktake->createdBy?->user_full_name ?? $stocktake->createdBy?->username ?? '—' }}</div>
                </div>
            </div>

            @if($stocktake->additional_notes)
            <div class="stocktake-note-card mb-4">
                <div class="label"><i class="fas fa-sticky-note mr-1"></i>@lang('stocktake.additional_notes')</div>
                <div class="value">{{ $stocktake->additional_notes }}</div>
            </div>
            @endif

                <!-- Stocktake Form -->
                <form id="stocktake_form" action="{{ route('stocktakes.update', $stocktake->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="stocktake-toolbar">
                        <div class="stocktake-search-wrap">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <i class="fas fa-search"></i>
                                    </span>
                                </div>
                                <input type="text" id="stocktake_item_search" class="form-control" placeholder="Search by product name or SKU">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" id="clear_stocktake_item_search">Clear</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stocktake-section-title">
                        <h4>@lang('stocktake.stocktake_items') <span class="count">({{ $stocktake->items->count() }})</span></h4>
                        <span class="count">{{ __('stocktake.export_stocktake') }} / {{ __('stocktake.print_stocktake') }}</span>
                    </div>

                    <div class="stocktake-table-card">
                    <div class="table-responsive">
                        <table class="table table-hover" id="stocktake_items_table">
                            <thead class="thead-dark">
                                <tr>
                                    <th width="30%">@lang('stocktake.product_name')</th>
                                    <th width="10%">@lang('product.sku')</th>
                                    <th width="15%">@lang('stocktake.system_quantity')</th>
                                    <th width="15%">@lang('stocktake.counted_quantity')</th>
                                    <th width="15%">@lang('stocktake.variance')</th>
                                    <th width="15%">@lang('stocktake.notes')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stocktake->items as $item)
                                @php
                                    $variance = ($item->counted_quantity ?? $item->system_quantity) - $item->system_quantity;
                                    $varianceClass = $variance < 0 ? 'variance-negative' : ($variance > 0 ? 'variance-positive' : 'variance-neutral');
                                @endphp
                                <tr class="stocktake-item-row"
                                    data-search="{{ strtolower($item->product->name . ' ' . ($item->variation->name ?? '') . ' ' . ($item->variation->sub_sku ?? '') . ' ' . ($item->product->sku ?? '')) }}">
                                    <td>
                                        <strong>{{ $item->product->name }}</strong>
                                        @if($item->variation->name != 'DUMMY')
                                            <br><small class="text-muted">{{ $item->variation->name }}</small>
                                        @endif
                                    </td>
                                    <td>{{ $item->variation->sub_sku }}</td>
                                    <td class="text-center">{{ $item->system_quantity }}</td>
                                    <td>
                                        <input type="number" class="form-control counted_quantity" 
                                            name="items[{{ $item->id }}][counted_quantity]" 
                                            value="{{ $item->counted_quantity ?? $item->system_quantity }}" 
                                            min="0" step="any" required>
                                        <input type="hidden" name="items[{{ $item->id }}][id]" value="{{ $item->id }}">
                                    </td>
                                    <td class="variance text-center">
                                        <span class="badge variance-badge variance-value {{ $varianceClass }}">
                                            {{ number_format($variance, 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" 
                                            name="items[{{ $item->id }}][notes]" 
                                            value="{{ $item->notes }}" 
                                            placeholder="@lang('stocktake.add_notes')">
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <div class="empty-state-icon">
                                                <i class="fas fa-box-open"></i>
                                            </div>
                                            <h4>@lang('stocktake.no_products_in_stocktake')</h4>
                                            <p class="empty-state-subtext">
                                                @lang('stocktake.add_products_in_create_page')
                                            </p>
                                            <!-- Add a back button with the correct route -->
                                            <a href="{{ route('stocktakes.index') }}" class="btn btn-primary mt-3">
                                                <i class="fas fa-arrow-left"></i> @lang('stocktake.back')
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                                @if(count($stocktake->items) > 0)
                                <tr class="stocktake-no-results" style="display: none;">
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No items match your search.
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    </div>

                    @if(count($stocktake->items) > 0)
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="additional_notes">@lang('stocktake.additional_notes')</label>
                                <textarea class="form-control" id="additional_notes" 
                                    name="additional_notes" rows="3">{{ $stocktake->additional_notes }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12 text-center">
                            <div class="stocktake-footer-actions">
                                <button type="submit" class="btn btn-primary" id="save_stocktake">
                                    <i class="fas fa-save"></i> @lang('stocktake.save')
                                </button>
                                <a href="{{ route('stocktakes.index') }}" class="btn btn-default">
                                    <i class="fas fa-arrow-left"></i> @lang('stocktake.back')
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif
                </form>
            </div>
        </div>
    </div>
</div>
</div>

<div id="busy-overlay" class="busy-overlay" style="display: none;">
    <div class="text-center">
        <i class="fas fa-spinner fa-spin fa-3x"></i>
        <p class="mt-3">@lang('stocktake.server_busy')</p>
    </div>
</div>
@endsection

@section('javascript')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    function openStocktakeExportPopup() {
        if (typeof Swal === 'undefined') {
            if (window.confirm('Export this stocktake as Excel? Click Cancel for PDF/Print.')) {
                exportStocktakeFile('excel');
            } else {
                window.print();
            }
            return;
        }

        Swal.fire({
            title: '@lang('stocktake.export_stocktake')',
            text: 'Choose how you want to export this stocktake.',
            icon: 'question',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: 'PDF',
            denyButtonText: 'Excel',
            cancelButtonText: '@lang('stocktake.cancel')',
            customClass: {
                confirmButton: 'btn btn-primary',
                denyButton: 'btn btn-success',
                cancelButton: 'btn btn-light'
            },
            buttonsStyling: false
        }).then((result) => {
            if (result.isConfirmed) {
                exportStocktakeFile('pdf');
            } else if (result.isDenied) {
                exportStocktakeFile('excel');
            }
        });
    }

    function exportStocktakeFile(format) {
        let url = "{{ route('stocktakes.export', $stocktake->id) }}";
        const params = new URLSearchParams();
        if (format) {
            params.set('format', format);
        }
        if ([...params].length) {
            url += '?' + params.toString();
        }
        window.location.href = url;
    }

    $(document).on('click', '#stocktake_export_btn', function(e) {
        e.preventDefault();
        openStocktakeExportPopup();
    });

    function filterStocktakeItems() {
        var searchValue = ($('#stocktake_item_search').val() || '').toLowerCase().trim();
        var visibleRows = 0;

        $('.stocktake-item-row').each(function() {
            var row = $(this);
            var searchableText = (row.data('search') || '').toString();
            var matches = searchValue === '' || searchableText.indexOf(searchValue) !== -1;

            row.toggle(matches);

            if (matches) {
                visibleRows++;
            }
        });

        $('.stocktake-no-results').toggle(visibleRows === 0);
    }

    $(document).on('input', '#stocktake_item_search', function() {
        filterStocktakeItems();
    });

    $(document).on('click', '#clear_stocktake_item_search', function() {
        $('#stocktake_item_search').val('');
        filterStocktakeItems();
        $('#stocktake_item_search').focus();
    });

    // Initialize variance calculation on page load
    $('.counted_quantity').each(function() {
        calculateVariance($(this));
    });

    filterStocktakeItems();

    // Calculate variance on quantity change
    $(document).on('input', '.counted_quantity', function() {
        calculateVariance($(this));
    });

    function calculateVariance(inputElement) {
        var row = inputElement.closest('tr');
        var system_qty = parseFloat(row.find('td:eq(2)').text()) || 0;
        var counted_qty = parseFloat(inputElement.val()) || 0;
        var variance = counted_qty - system_qty;
        
        var varianceElement = row.find('.variance-value');
        varianceElement.text(variance.toFixed(2));
        
        // Update styling based on variance
        varianceElement.removeClass('variance-negative variance-positive variance-neutral');
        if (variance < 0) {
            varianceElement.addClass('variance-negative');
        } else if (variance > 0) {
            varianceElement.addClass('variance-positive');
        } else {
            varianceElement.addClass('variance-neutral');
        }
    }

    // Form submission with loading state
    $('#stocktake_form').on('submit', function(e) {
        e.preventDefault();
        var btn = $(this).find('#save_stocktake');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> @lang('stocktake.saving')');
        
        $.ajax({
            method: 'POST',
            url: $(this).attr('action'),
            data: $(this).serialize(),
            success: function(response) {
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> @lang('stocktake.save')');
                if (response.success) {
                    toastr.success(response.msg);
                } else {
                    toastr.error(response.msg);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-save"></i> @lang('stocktake.save')');
                toastr.error(xhr.responseJSON.message || '@lang('stocktake.something_went_wrong')');
            }
        });
    });

    // Complete stocktake button - guaranteed working version
    $(document).on('click', '#complete_stocktake', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: '@lang('stocktake.confirm_complete_title')',
            text: '@lang('stocktake.confirm_complete_message')',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '@lang('stocktake.yes')',
            cancelButtonText: '@lang('stocktake.no')'
        }).then((result) => {
            if (result.isConfirmed) {
                var btn = $('#complete_stocktake');
                $('#busy-overlay').show();
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> @lang('stocktake.processing')');
                
                $.ajax({
                    type: 'POST',
                    url: "{{ route('stocktakes.complete', $stocktake->id) }}",
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        $('#busy-overlay').hide();
                        if (response.success) {
                            toastr.success(response.msg);
                            setTimeout(function() {
                                window.location.href = response.redirect || "{{ route('stocktakes.index') }}";
                            }, 1500);
                        } else {
                            btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> @lang('stocktake.complete_stocktake')');
                            toastr.error(response.msg);
                        }
                    },
                    error: function(xhr) {
                        $('#busy-overlay').hide();
                        btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i> @lang('stocktake.complete_stocktake')');
                        var errorMsg = xhr.responseJSON && xhr.responseJSON.message 
                            ? xhr.responseJSON.message 
                            : '@lang('stocktake.something_went_wrong')';
                        toastr.error(errorMsg);
                    }
                });
            }
        });
    });
});
</script>
@endsection