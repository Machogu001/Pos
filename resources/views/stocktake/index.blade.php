@extends('layouts.app')

@section('title', __('stocktake.stocktakes'))

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- ── PAGE HEADER ──────────────────────────────────────── --}}
    <div style="background:linear-gradient(135deg,#1d4ed8 0%,#4338ca 100%); border-radius:1rem; padding:1.25rem 1.5rem; margin-bottom:1.25rem; box-shadow:0 4px 18px rgba(29,78,216,.25);">
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem;">
            <div style="display:flex; align-items:center; gap:.875rem;">
                <div style="background:rgba(255,255,255,.18); border-radius:.75rem; width:2.75rem; height:2.75rem; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i class="fas fa-clipboard-list" style="font-size:1.2rem; color:#fff;"></i>
                </div>
                <div>
                    <h3 style="color:#fff; font-weight:700; margin:0; font-size:1.2rem;">@lang('stocktake.stocktakes')</h3>
                    <p style="color:rgba(255,255,255,.75); margin:0; font-size:.82rem;">@lang('stocktake.manage_stocktakes_description')</p>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:.5rem; flex-wrap:wrap;">
                <a href="{{ route('stocktakes.history') }}" style="display:inline-flex; align-items:center; gap:.4rem; background:rgba(255,255,255,.15); color:#fff; font-size:.82rem; font-weight:500; padding:.45rem .9rem; border-radius:.5rem; text-decoration:none; border:1px solid rgba(255,255,255,.25); transition:background .2s;" onmouseover="this.style.background='rgba(255,255,255,.25)'" onmouseout="this.style.background='rgba(255,255,255,.15)'">
                    <i class="fas fa-history"></i> @lang('stocktake.history')
                </a>
                <a href="{{ route('stocktakes.variance_report') }}" style="display:inline-flex; align-items:center; gap:.4rem; background:#f59e0b; color:#1a1a1a; font-size:.82rem; font-weight:600; padding:.45rem .9rem; border-radius:.5rem; text-decoration:none; transition:opacity .2s;" onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                    <i class="fas fa-chart-bar"></i> @lang('stocktake.variance_report')
                </a>
                @can('stocktake.create')
                <a href="{{ route('stocktakes.create') }}" style="display:inline-flex; align-items:center; gap:.4rem; background:#10b981; color:#fff; font-size:.82rem; font-weight:600; padding:.45rem .9rem; border-radius:.5rem; text-decoration:none; transition:opacity .2s;" onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                    <i class="fas fa-plus-circle"></i> @lang('stocktake.add_stocktake')
                </a>
                @endcan
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

    {{-- ── STATS CARDS ──────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        @php
        $statCards = [
            ['id'=>'total-stocktakes',       'label'=>__('stocktake.total_stocktakes'), 'value'=> $stats['total'] ?? $stats['total_stocktakes'] ?? '-', 'icon'=>'fas fa-clipboard-list', 'bg'=>'#eff6ff', 'color'=>'#1d4ed8'],
            ['id'=>'in-progress-stocktakes', 'label'=>__('stocktake.in_progress'),      'value'=> $stats['in_progress'] ?? '-',                          'icon'=>'fas fa-sync-alt',       'bg'=>'#fffbeb', 'color'=>'#d97706'],
            ['id'=>'completed-stocktakes',   'label'=>__('stocktake.completed'),         'value'=> $stats['completed'] ?? '-',                            'icon'=>'fas fa-check-circle',   'bg'=>'#f0fdf4', 'color'=>'#16a34a'],
            ['id'=>'cancelled-stocktakes',   'label'=>__('stocktake.cancelled'),         'value'=> $stats['cancelled'] ?? '-',                            'icon'=>'fas fa-times-circle',   'bg'=>'#fff1f2', 'color'=>'#dc2626'],
        ];
        @endphp
        @foreach($statCards as $sc)
        <div class="col-lg-3 col-md-6">
            <div style="background:#fff; border-radius:.875rem; border:1px solid #e5e7eb; box-shadow:0 1px 4px rgba(0,0,0,.06); padding:1.1rem 1.25rem; display:flex; align-items:center; gap:1rem;">
                <div style="background:{{ $sc['bg'] }}; color:{{ $sc['color'] }}; width:2.75rem; height:2.75rem; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <i class="{{ $sc['icon'] }}"></i>
                </div>
                <div>
                    <p style="font-size:.78rem; color:#6b7280; margin:0 0 .15rem; font-weight:500;">{{ $sc['label'] }}</p>
                    <p id="{{ $sc['id'] }}" style="font-size:1.6rem; font-weight:700; color:#111827; margin:0; line-height:1;">{{ $sc['value'] }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- ── FILTERS ──────────────────────────────────────────────────────────── --}}
    <div style="background:#fff; border-radius:.875rem; border:1px solid #e5e7eb; box-shadow:0 1px 4px rgba(0,0,0,.06); padding:1.25rem 1.5rem; margin-bottom:1.25rem;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <h6 style="font-weight:700; color:#374151; margin:0; display:flex; align-items:center; gap:.4rem;">
                <i class="fas fa-filter" style="color:#4f46e5;"></i> @lang('stocktake.filters')
            </h6>
            <button type="button" class="btn btn-sm btn-outline-primary" id="toggle-advanced-filters">
                <i class="fas fa-sliders-h me-1"></i> @lang('stocktake.advanced_filters')
            </button>
        </div>
                            <form id="stocktake_filter_form">
                                <div class="row g-3">
                                    <div class="col-lg-3 col-md-6">
                                        <label class="form-label fw-semibold text-secondary mb-2">
                                            <i class="fas fa-map-marker-alt me-1 text-primary"></i>@lang('business.location')
                                        </label>
                                            <select class="form-control select2" id="location_filter" name="location_id">
                                            <option value="">@lang('stocktake.all')</option>
                                            @foreach($businessLocations as $key => $value)
                                                <option value="{{ $key }}" @selected(($filters['location_id'] ?? '') == $key)>{{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <label class="form-label fw-semibold text-secondary mb-2">
                                            <i class="fas fa-info-circle me-1 text-primary"></i>@lang('stocktake.status')
                                        </label>
                                            <select class="form-control select2" id="status_filter" name="status">
                                            <option value="">@lang('stocktake.all')</option>
                                                <option value="in_progress" @selected(($filters['status'] ?? '') === 'in_progress')>@lang('stocktake.in_progress')</option>
                                                <option value="completed" @selected(($filters['status'] ?? '') === 'completed')>@lang('stocktake.completed')</option>
                                                <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>@lang('stocktake.cancelled')</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <label class="form-label fw-semibold text-secondary mb-2">
                                            <i class="fas fa-calendar-alt me-1 text-primary"></i>@lang('stocktake.date_range')
                                        </label>
                                            <select class="form-control select2" id="date_range_filter" name="date_range">
                                            <option value="">@lang('stocktake.all')</option>
                                            <option value="today" @selected(($filters['date_range'] ?? '') === 'today')>@lang('stocktake.today')</option>
                                            <option value="yesterday" @selected(($filters['date_range'] ?? '') === 'yesterday')>@lang('stocktake.yesterday')</option>
                                            <option value="this_week" @selected(($filters['date_range'] ?? '') === 'this_week')>@lang('stocktake.this_week')</option>
                                            <option value="last_week" @selected(($filters['date_range'] ?? '') === 'last_week')>@lang('stocktake.last_week')</option>
                                            <option value="this_month" @selected(($filters['date_range'] ?? '') === 'this_month')>@lang('stocktake.this_month')</option>
                                            <option value="last_month" @selected(($filters['date_range'] ?? '') === 'last_month')>@lang('stocktake.last_month')</option>
                                            <option value="custom" @selected(($filters['date_range'] ?? '') === 'custom')>@lang('stocktake.custom_range')</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <label class="form-label fw-semibold text-secondary mb-2">
                                            <i class="fas fa-dollar-sign me-1 text-primary"></i>@lang('stocktake.price_basis')
                                        </label>
                                            <select class="form-control" id="price_basis_filter" name="price_basis">
                                            <option value="selling" @selected(($filters['price_basis'] ?? 'selling') === 'selling')>@lang('stocktake.price_basis_selling')</option>
                                            <option value="purchase" @selected(($filters['price_basis'] ?? '') === 'purchase')>@lang('stocktake.price_basis_purchase')</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons -->
                <div class="row mt-3">
                    <div class="col-12 d-flex gap-2 justify-content-end">
                        <button type="button" class="btn btn-primary px-4" id="apply_filters">
                            <i class="fas fa-check me-2"></i> @lang('stocktake.apply')
                        </button>
                        <button type="button" class="btn btn-outline-secondary px-4" id="reset_filters">
                            <i class="fas fa-redo me-2"></i> @lang('stocktake.reset')
                        </button>
                    </div>
                </div>
                
                <!-- Advanced Filters -->
                <div class="row g-3 mt-2 p-3" id="advanced-filters" style="display:none; background:#f9fafb; border-radius:.5rem;">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold text-secondary mb-2">
                                            <i class="fas fa-calendar-day me-1 text-primary"></i>@lang('stocktake.custom_from_date')
                                        </label>
                                        <input type="date" class="form-control" id="from_date_filter" name="from_date" value="{{ $filters['from_date'] ?? '' }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold text-secondary mb-2">
                                            <i class="fas fa-calendar-check me-1 text-primary"></i>@lang('stocktake.custom_to_date')
                                        </label>
                                        <input type="date" class="form-control" id="to_date_filter" name="to_date" value="{{ $filters['to_date'] ?? '' }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold text-secondary mb-2">
                                            <i class="fas fa-search me-1 text-primary"></i>@lang('stocktake.search_reference')
                                        </label>
                                        <input type="text" class="form-control" id="search_filter" name="search" placeholder="@lang('stocktake.enter_reference')" value="{{ $filters['search'] ?? '' }}">
                                    </div>
                                </div>
            </form>
    </div>

    {{-- ── TABLE CARD ───────────────────────────────────────── --}}
    <div style="background:#fff; border-radius:.875rem; border:1px solid #e5e7eb; box-shadow:0 1px 4px rgba(0,0,0,.06); overflow:hidden;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.75rem; padding:1rem 1.5rem; border-bottom:1px solid #f3f4f6;">
            <h6 style="font-weight:700; color:#374151; margin:0; display:flex; align-items:center; gap:.4rem;">
                <i class="fas fa-table" style="color:#4f46e5;"></i> @lang('stocktake.stocktake_list')
            </h6>
            <div style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" id="auto-refresh-toggle" checked>
                    <label class="form-check-label small" for="auto-refresh-toggle">
                        <i class="fas fa-sync-alt me-1"></i> @lang('stocktake.auto_refresh')
                    </label>
                </div>
                <span style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:.5rem; padding:.35rem .75rem; font-size:.78rem; color:#374151;" id="last-updated">
                    <i class="fas fa-clock me-1" style="color:#4f46e5;"></i>
                    @lang('stocktake.last_updated'): <strong id="last-updated-time">-</strong>
                </span>
            </div>
        </div>
        <div class="stocktake-table-scroll" style="padding:1rem 1.5rem 1.5rem;">
            <table class="table table-hover align-middle mb-0" id="stocktakes-table" style="width:100%">
                <thead style="background:#f9fafb;">
                    <tr>
                        <th class="ps-2 fw-semibold" style="color:#374151; font-size:.78rem; text-transform:uppercase; letter-spacing:.05em;">@lang('stocktake.reference_no')</th>
                        <th class="fw-semibold" style="color:#374151; font-size:.78rem; text-transform:uppercase; letter-spacing:.05em;">@lang('stocktake.location')</th>
                        <th class="fw-semibold" style="color:#374151; font-size:.78rem; text-transform:uppercase; letter-spacing:.05em;">@lang('stocktake.status')</th>
                        <th class="text-center fw-semibold" style="color:#374151; font-size:.78rem; text-transform:uppercase; letter-spacing:.05em;">@lang('stocktake.product_count')</th>
                        <th class="text-end fw-semibold" style="color:#374151; font-size:.78rem; text-transform:uppercase; letter-spacing:.05em;">@lang('stocktake.value_amount')</th>
                        <th class="fw-semibold" style="color:#374151; font-size:.78rem; text-transform:uppercase; letter-spacing:.05em;">@lang('stocktake.started_at')</th>
                        <th class="fw-semibold" style="color:#374151; font-size:.78rem; text-transform:uppercase; letter-spacing:.05em;">@lang('stocktake.completed_at')</th>
                        <th class="fw-semibold" style="color:#374151; font-size:.78rem; text-transform:uppercase; letter-spacing:.05em;">@lang('stocktake.adjustment_ref')</th>
                        <th class="text-center fw-semibold" style="color:#374151; font-size:.78rem; text-transform:uppercase; letter-spacing:.05em;">@lang('stocktake.action')</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be loaded via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('styles')
<style>
    .dataTables_processing {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(255, 255, 255, 0.9);
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        z-index: 100;
        border: 1px solid #ddd;
        display: none;
    }
    
    .badge-completed {
        background-color: #28a745;
        color: white;
        padding: 6px 12px;
        border-radius: 12px;
        font-weight: 500;
        font-size: 0.8em;
    }
    
    .badge-in_progress {
        background-color: #ffc107;
        color: #212529;
        padding: 6px 12px;
        border-radius: 12px;
        font-weight: 500;
        font-size: 0.8em;
    }
    
    .badge-cancelled {
        background-color: #dc3545;
        color: white;
        padding: 6px 12px;
        border-radius: 12px;
        font-weight: 500;
        font-size: 0.8em;
    }
    
    .action-btns {
        min-width: 140px;
    }
    
    .btn-sm-icon {
        padding: 5px 8px;
        font-size: 0.8rem;
        line-height: 1.2;
    }
    
    .filter-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.04);
        transform: translateY(-1px);
        transition: all 0.2s ease;
    }

    .stocktake-table-scroll .table-hover tbody tr:hover {
        transform: none;
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

    .stats-card {
        transition: transform 0.2s ease-in-out;
    }

    .stats-card:hover {
        transform: translateY(-2px);
    }

    .progress {
        height: 6px;
        margin-top: 5px;
    }

    .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice {
        background: #e9ecef;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
    }

    .bulk-checkbox {
        width: 18px;
        height: 18px;
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
        
        .table-responsive {
            font-size: 0.875rem;
        }
        
        .action-btns {
            min-width: 100px;
        }
        
        .btn-group .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .stats-card .card-body {
            padding: 1rem;
        }

        .stats-card h4 {
            font-size: 1.25rem;
        }
    }
    
    @media print {
        .card-header, .btn, .form-group, .bg-light, .alert, .dataTables_filter, .dataTables_length, .dt-buttons {
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
    }

    .stocktake-table-scroll {
        display: block;
        overflow: hidden;
        scrollbar-gutter: stable;
    }

    .stocktake-table-scroll .dataTables_wrapper,
    .stocktake-table-scroll .dataTables_scroll,
    .stocktake-table-scroll .dataTables_scrollHead,
    .stocktake-table-scroll .dataTables_scrollBody {
        overflow-x: hidden !important;
    }

    .stocktake-table-scroll .dataTables_scrollBody {
        overflow-y: scroll !important;
    }

    .stocktake-table-scroll table,
    .dataTables_scrollBody table {
        width: 100% !important;
        table-layout: fixed;
    }

    .stocktake-table-scroll th,
    .stocktake-table-scroll td,
    .dataTables_scrollBody th,
    .dataTables_scrollBody td {
        word-break: break-word;
        white-space: normal;
    }

    .stocktake-table-scroll thead th {
        position: sticky;
        top: 0;
        z-index: 5;
        background: #f8fafc;
    }
</style>
@endsection

@section('javascript')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    let autoRefreshInterval;
    let lastUpdated = new Date();
    
    // Initialize select2 for filters
    $('.select2').select2({
        width: '100%',
        theme: 'bootstrap-5',
        placeholder: '{{ __("stocktake.filter_by_status") }}',
        allowClear: true
    });

    // Initialize DataTable
    var table = $('#stocktakes-table').DataTable({
        processing: true,
        serverSide: true,
        scrollY: '360px',
        scrollCollapse: false,
        scrollX: false,
        autoWidth: false,
        ajax: {
            url: "{{ route('stocktakes.index') }}",
            data: function(d) {
                d.location_id = $('#location_filter').val();
                d.status = $('#status_filter').val();
                d.date_range = $('#date_range_filter').val();
                d.from_date = $('#from_date_filter').val();
                d.to_date = $('#to_date_filter').val();
                    d.price_basis = $('#price_basis_filter').val();
                d.search = $('#search_filter').val();
                
                // Update last updated time
                lastUpdated = new Date();
                updateLastUpdatedTime();
            },
            error: function(xhr, error, thrown) {
                console.error('DataTables error:', error, thrown);
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    showAlert('{{ __("stocktake.error_loading_data", ["error" => ""]) }}' + xhr.responseJSON.error, 'danger');
                } else {
                    showAlert('{{ __("stocktake.error_loading_stocktakes_retry") }}', 'danger');
                }
            }
        },
        language: {
            processing: '<div class="d-flex flex-column align-items-center">' +
                            '<div class="spinner-border text-primary mb-2" role="status"></div>' +
                            '<span>{{ __("stocktake.loading_stocktakes") }}</span>' +
                         '</div>',
            emptyTable: '<div class="text-center py-5">' +
                            '<i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>' +
                            '<h5 class="fw-semibold">{{ __("stocktake.no_stocktakes_found") }}</h5>' +
                            '<p class="text-muted">{{ __("stocktake.get_started_by_creating") }}</p>' +
                            '@can("stocktake.create")' +
                            '<a href="{{ route('stocktakes.create') }}" class="btn btn-primary mt-2">' +
                                '<i class="fas fa-plus me-1"></i> {{ __("stocktake.add_stocktake") }}' +
                            '</a>' +
                            '@endcan' +
                        '</div>',
            zeroRecords: '<div class="text-center py-5">' +
                            '<i class="fas fa-search fa-3x text-muted mb-3"></i>' +
                            '<h5 class="fw-semibold">{{ __("stocktake.no_matching_stocktakes") }}</h5>' +
                            '<p class="text-muted">{{ __("stocktake.try_adjusting_search") }}</p>' +
                         '</div>',
            info: '{{ __("stocktake.showing_entries", ["start" => "_START_", "end" => "_END_", "total" => "_TOTAL_"]) }}',
            infoEmpty: '{{ __("stocktake.showing_entries", ["start" => 0, "end" => 0, "total" => 0]) }}',
            infoFiltered: '{{ __("stocktake.filtered_from", ["total" => "_MAX_"]) }}',
            lengthMenu: '{{ __("stocktake.show_menu_entries") }}',
            loadingRecords: '{{ __("stocktake.loading") }}',
            search: '{{ __("stocktake.search") }}:',
            searchPlaceholder: '{{ __("stocktake.search_placeholder") }}',
            paginate: {
                first: '{{ __("stocktake.first") }}',
                last: '{{ __("stocktake.last") }}',
                next: '{{ __("stocktake.next") }}',
                previous: '{{ __("stocktake.previous") }}'
            }
        },
        dom: "<'row'<'col-sm-12 col-md-6'B><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        buttons: [
            {
                extend: 'excel',
                className: 'btn btn-light border',
                text: '<i class="fas fa-file-excel text-success me-2"></i> {{ __("stocktake.excel") }}',
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
                extend: 'pdf',
                className: 'btn btn-light border',
                text: '<i class="fas fa-file-pdf text-danger me-2"></i> {{ __("stocktake.pdf") }}',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'print',
                className: 'btn btn-light border',
                text: '<i class="fas fa-print me-2"></i> {{ __("stocktake.print") }}',
                exportOptions: {
                    columns: ':visible'
                }
            },
            {
                extend: 'colvis',
                className: 'btn btn-light border',
                text: '<i class="fas fa-columns me-2"></i> {{ __("stocktake.columns") }}',
                columns: ':not(.no-colvis)'
            },
            {
                text: '<i class="fas fa-sync-alt me-2"></i> {{ __("stocktake.refresh") }}',
                className: 'btn btn-light border',
                action: function (e, dt, node, config) {
                    dt.ajax.reload();
                    showAlert('{{ __("stocktake.refresh_success") }}', 'success');
                }
            }
        ],
        columns: [
            { 
                data: 'reference_no', 
                name: 'reference_no',
                className: 'ps-4 fw-semibold no-colvis',
                render: function(data, type, row) {
                    if (type === 'display' && data) {
                        return '<a href="' + row.show_url + '" class="text-decoration-none text-dark fw-semibold">' + data + '</a>';
                    }
                    return data;
                }
            },
            { 
                data: 'location.name', 
                name: 'location.name',
                render: function(data) {
                    return data ? '<span class="badge bg-light text-dark border">' + data + '</span>' : '<span class="text-muted">-</span>';
                }
            },
            { 
                data: 'status', 
                name: 'status',
                render: function(data, type, row) {
                    if (!data) return '<span class="text-muted">-</span>';
                    
                    // Improved status display with better styling
                    var statusConfig = {
                        'in_progress': {
                            class: 'warning',
                            icon: 'fas fa-sync-alt fa-spin',
                            text: '{{ __("stocktake.in_progress") }}'
                        },
                        'completed': {
                            class: 'success',
                            icon: 'fas fa-check-circle',
                            text: '{{ __("stocktake.completed") }}'
                        },
                        'cancelled': {
                            class: 'danger',
                            icon: 'fas fa-times-circle',
                            text: '{{ __("stocktake.cancelled") }}'
                        }
                    };
                    
                    var config = statusConfig[data] || { class: 'secondary', icon: 'fas fa-question-circle', text: data };
                    
                    // Add progress bar for in_progress status
                    var progressHtml = '';
                    if (data === 'in_progress' && row.progress_percentage !== undefined) {
                        progressHtml = '<div class="progress mt-1" style="height: 4px; width: 80px;">' +
                            '<div class="progress-bar bg-warning progress-bar-striped progress-bar-animated" role="progressbar" ' +
                            'style="width: ' + row.progress_percentage + '%;" ' +
                            'aria-valuenow="' + row.progress_percentage + '" aria-valuemin="0" aria-valuemax="100">' +
                            '</div>' +
                            '</div>';
                    }
                    
                    return '<div class="d-flex align-items-center">' +
                           '<span class="badge bg-' + config.class + ' d-flex align-items-center">' +
                           '<i class="' + config.icon + ' me-1" style="font-size: 0.7em;"></i>' +
                           config.text +
                           '</span>' +
                           '</div>' + progressHtml;
                }
            },
            { 
                data: 'product_count', 
                name: 'product_count',
                className: 'text-center',
                render: function(data) {
                    return '<span class="badge bg-secondary rounded-pill">' + (data || 0) + '</span>';
                }
            },
            {
                data: 'variance_amount',
                name: 'variance_amount',
                className: 'text-end',
                render: function(data, type, row) {
                    if (!data && data !== 0 && !row.variance_amount_raw) return '<span class="text-muted">-</span>';
                    // data is formatted string from server (productUtil->num_f). Show currency symbol if available
                    var symbol = '{{ session("currency.symbol") }}';
                    var value = data || (row.variance_amount_raw ? Number(row.variance_amount_raw).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0.00');
                    return '<span class="fw-semibold">' + (value ? (symbol + value) : '-') + '</span>';
                }
            },
            { 
                data: 'transaction_date', 
                name: 'transaction_date',
                render: function(data) {
                    return data ? formatDateTime(data) : '<span class="text-muted">-</span>';
                }
            },
            { 
                data: 'completed_at', 
                name: 'completed_at',
                render: function(data) {
                    return data ? formatDateTime(data) : '<span class="text-muted">-</span>';
                }
            },
            { 
                data: 'adjustment_ref', 
                name: 'adjustment_ref',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    if (!data) return '<span class="text-muted">-</span>';
                    return '<a href="' + (row.adjustment_url || '#') + '" class="text-decoration-none">' + data + '</a>';
                }
            },
            { 
                data: 'action', 
                name: 'action', 
                orderable: false, 
                searchable: false,
                className: 'text-center action-btns no-colvis',
                render: function(data, type, row) {
                    if (type === 'display') {
                        return data || '<div class="btn-group btn-group-sm">' +
                            '<a href="' + row.show_url + '" class="btn btn-outline-primary" data-bs-toggle="tooltip" title="{{ __("stocktake.view") }}">' +
                                '<i class="fas fa-eye"></i>' +
                            '</a>' +
                            (row.can_edit ? 
                            '<a href="' + row.edit_url + '" class="btn btn-outline-warning" data-bs-toggle="tooltip" title="{{ __("stocktake.edit") }}">' +
                                '<i class="fas fa-edit"></i>' +
                            '</a>' : '') +
                            (row.can_delete ? 
                            '<button class="btn btn-outline-danger delete-stocktake" data-href="' + row.delete_url + '" data-status="' + row.status + '" data-bs-toggle="tooltip" title="{{ __("stocktake.delete") }}">' +
                                '<i class="fas fa-trash"></i>' +
                            '</button>' : '') +
                            (row.can_complete ? 
                            '<button class="btn btn-outline-success complete-stocktake" data-href="' + row.complete_url + '" data-bs-toggle="tooltip" title="{{ __("stocktake.complete") }}">' +
                                '<i class="fas fa-check"></i>' +
                            '</button>' : '') +
                        '</div>';
                    }
                    return data;
                }
            }
        ],
        order: [[0, 'desc']],
        responsive: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        stateSave: true,
        stateDuration: 60 * 60 * 24, // 1 day
        initComplete: function(settings, json) {
            $('.dataTables_processing').hide();
            updateStats(json);
        },
        drawCallback: function(settings) {
            $('.dataTables_processing').hide();
            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
            
            // Add row highlighting
            $('#stocktakes-table tbody tr').hover(
                function() {
                    $(this).addClass('table-active');
                },
                function() {
                    $(this).removeClass('table-active');
                }
            );

            // Update stats if available
            if (settings.json) {
                updateStats(settings.json);
            }
        },
        error: function(xhr, error, thrown) {
            console.error('DataTable error:', error, thrown);
            showAlert('{{ __("stocktake.load_error") }}', 'danger');
        }
    });

    // Update statistics cards
    function updateStats(json) {
        if (json && json.stats) {
            $('#total-stocktakes').text(json.stats.total ?? json.stats.total_stocktakes ?? 0);
            $('#in-progress-stocktakes').text(json.stats.in_progress || 0);
            $('#completed-stocktakes').text(json.stats.completed || 0);
            $('#cancelled-stocktakes').text(json.stats.cancelled || 0);
        }
    }

    // Update last updated time
    function updateLastUpdatedTime() {
        $('#last-updated-time').text(formatDateTime(lastUpdated));
    }

    function pad2(value) {
        return value.toString().padStart(2, '0');
    }

    // Format date to DD/MM/YYYY HH:MM:SS
    function formatDateTime(input) {
        if (!input) return '';

        try {
            if (input instanceof Date) {
                return pad2(input.getDate()) + '/' + pad2(input.getMonth() + 1) + '/' + input.getFullYear() +
                    ' ' + pad2(input.getHours()) + ':' + pad2(input.getMinutes()) + ':' + pad2(input.getSeconds());
            }

            var dateString = String(input).trim();
            if (/^\d{2}\/\d{2}\/\d{4}\s\d{2}:\d{2}:\d{2}$/.test(dateString)) {
                return dateString;
            }

            // Support mysql style datetime: YYYY-MM-DD HH:mm or YYYY-MM-DD HH:mm:ss
            var mysqlMatch = dateString.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?$/);
            if (mysqlMatch) {
                return mysqlMatch[3] + '/' + mysqlMatch[2] + '/' + mysqlMatch[1] + ' ' +
                    mysqlMatch[4] + ':' + mysqlMatch[5] + ':' + (mysqlMatch[6] || '00');
            }

            var date = new Date(dateString);
            if (isNaN(date.getTime())) {
                return dateString;
            }

            return pad2(date.getDate()) + '/' + pad2(date.getMonth() + 1) + '/' + date.getFullYear() +
                ' ' + pad2(date.getHours()) + ':' + pad2(date.getMinutes()) + ':' + pad2(date.getSeconds());
        } catch (e) {
            return input;
        }
    }

    // Show alert message
    function showAlert(message, type) {
        var alertClass = 'alert-' + type;
        var icon = type === 'success' ? 'fa-check-circle' : 
                  type === 'warning' ? 'fa-exclamation-triangle' : 
                  type === 'info' ? 'fa-info-circle' : 'fa-exclamation-circle';
        
        var alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                        '<i class="fas ' + icon + ' me-2"></i>' +
                        message +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                        '</div>';
        
        $('.alert').remove();
        $('.card-body').prepend(alertHtml);
        
        // Auto-dismiss success alerts after 5 seconds
        if (type === 'success') {
            setTimeout(function() {
                $('.alert').alert('close');
            }, 5000);
        }
    }

    // Toggle advanced filters
    $('#toggle-advanced-filters').on('click', function() {
        $('#advanced-filters').slideToggle(300);
        $(this).toggleClass('active');
        if ($(this).hasClass('active')) {
            $(this).html('<i class="fas fa-times me-1"></i> {{ __("stocktake.hide_filters") }}');
        } else {
            $(this).html('<i class="fas fa-sliders-h me-1"></i> {{ __("stocktake.advanced_filters") }}');
        }
    });

    // Handle date range filter change
    $('#date_range_filter').on('change', function() {
        if ($(this).val() === 'custom') {
            $('#advanced-filters').show();
            $('#toggle-advanced-filters').addClass('active')
                .html('<i class="fas fa-times me-1"></i> {{ __("stocktake.hide_filters") }}');
        } else {
            $('#from_date_filter').val('');
            $('#to_date_filter').val('');
        }
    });

    // Set default dates for custom range
    function setDefaultCustomDates() {
        var today = new Date();
        var oneWeekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
        
        $('#from_date_filter').val(oneWeekAgo.toISOString().split('T')[0]);
        $('#to_date_filter').val(today.toISOString().split('T')[0]);
    }

    // Filter handlers
    $('#apply_filters').on('click', function() {
        // Validate custom date range
        if ($('#date_range_filter').val() === 'custom') {
            var fromDate = $('#from_date_filter').val();
            var toDate = $('#to_date_filter').val();
            
            if (fromDate && toDate && new Date(fromDate) > new Date(toDate)) {
                showAlert('{{ __("stocktake.invalid_date_range") }}', 'warning');
                return;
            }
        }
        
        table.ajax.reload();
        
        // Show loading state
        var applyBtn = $(this);
        var originalHtml = applyBtn.html();
        applyBtn.html('<i class="fas fa-spinner fa-spin me-2"></i> {{ __("stocktake.applying") }}...').prop('disabled', true);
        
        setTimeout(function() {
            applyBtn.html(originalHtml).prop('disabled', false);
        }, 1000);
    });

    $('#reset_filters').on('click', function() {
        $('#location_filter').val('').trigger('change');
        $('#status_filter').val('').trigger('change');
        $('#date_range_filter').val('').trigger('change');
        $('#from_date_filter').val('');
        $('#to_date_filter').val('');
        $('#search_filter').val('');
        table.ajax.reload();
        
        // Show reset feedback
        var resetBtn = $(this);
        var originalHtml = resetBtn.html();
        resetBtn.html('<i class="fas fa-check me-2"></i> {{ __("stocktake.reset") }}').prop('disabled', true);
        
        setTimeout(function() {
            resetBtn.html(originalHtml).prop('disabled', false);
        }, 1000);
    });

    // Auto-refresh toggle
    $('#auto-refresh-toggle').on('change', function() {
        if ($(this).is(':checked')) {
            startAutoRefresh();
            showAlert('{{ __("stocktake.auto_refresh_enabled") }}', 'info');
        } else {
            stopAutoRefresh();
            showAlert('{{ __("stocktake.auto_refresh_disabled") }}', 'warning');
        }
    });

    function startAutoRefresh() {
        autoRefreshInterval = setInterval(function() {
            if ($('#stocktakes-table').is(':visible') && document.visibilityState === 'visible') {
                table.ajax.reload(null, false);
                console.log('Auto-refreshed stocktakes data');
            }
        }, 60000); // 60 seconds
    }

    function stopAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
    }

    // Start auto-refresh initially
    startAutoRefresh();

    // Complete stocktake handler
    $(document).on('click', '.complete-stocktake', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var href = button.data('href');
        var reference = button.closest('tr').find('td:first-child').text().trim();

        Swal.fire({
            title: '{{ __("stocktake.complete_stocktake") }}?',
            html: `<p class="mb-3">{{ __("stocktake.confirm_complete_message") }}</p>
                   <div class="alert alert-info text-start">
                      <i class="fas fa-info-circle me-2"></i>
                      <strong>${reference}</strong>
                   </div>
                   <p class="mb-0 text-muted small">{{ __("stocktake.complete_warning") }}</p>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-check-circle me-2"></i> {{ __("stocktake.complete") }}',
            cancelButtonText: '<i class="fas fa-times me-2"></i> {{ __("stocktake.cancel") }}',
            reverseButtons: true,
            backdrop: true,
            showClass: {
                popup: 'animate__animated animate__fadeInDown'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    method: 'POST',
                    url: href,
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        button.html('<span class="spinner-border spinner-border-sm me-1" role="status"></span>');
                        button.prop('disabled', true);
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '{{ __("stocktake.completed") }}!',
                                text: response.msg,
                                timer: 2000,
                                showConfirmButton: false,
                                showClass: {
                                    popup: 'animate__animated animate__fadeInDown'
                                },
                                hideClass: {
                                    popup: 'animate__animated animate__fadeOutUp'
                                }
                            }).then(() => {
                                if (response.redirect) {
                                    window.location.href = response.redirect;
                                } else {
                                    table.ajax.reload(null, false);
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __("stocktake.error") }}',
                                text: response.msg,
                                showClass: {
                                    popup: 'animate__animated animate__fadeInDown'
                                },
                                hideClass: {
                                    popup: 'animate__animated animate__fadeOutUp'
                                }
                            });
                            button.html('<i class="fas fa-check"></i>');
                            button.prop('disabled', false);
                        }
                    },
                    error: function(xhr) {
                        var errorMsg = xhr.responseJSON && xhr.responseJSON.msg 
                            ? xhr.responseJSON.msg 
                            : '{{ __("stocktake.something_went_wrong") }}';
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __("stocktake.error") }}',
                            text: errorMsg,
                            showClass: {
                                popup: 'animate__animated animate__fadeInDown'
                            },
                            hideClass: {
                                popup: 'animate__animated animate__fadeOutUp'
                            }
                        });
                        button.html('<i class="fas fa-check"></i>');
                        button.prop('disabled', false);
                    }
                });
            }
        });
    });

    // Delete button handler
    $(document).on('click', '.delete-stocktake', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var href = button.data('href');
        var status = button.data('status');
        var reference = button.closest('tr').find('td:first-child').text().trim();
        
        if (status === 'completed') {
            Swal.fire({
                icon: 'error',
                title: '{{ __("stocktake.cannot_delete") }}',
                html: `<p class="mb-3">{{ __("stocktake.cannot_delete_completed") }}</p>
                       <div class="alert alert-warning text-start mb-0">
                          <i class="fas fa-exclamation-circle me-2"></i>
                          {{ __("stocktake.only_in_progress_can_delete") }}
                       </div>`,
                confirmButtonColor: '#3085d6',
                showClass: {
                    popup: 'animate__animated animate__fadeInDown'
                },
                hideClass: {
                    popup: 'animate__animated animate__fadeOutUp'
                }
            });
            return false;
        }

        Swal.fire({
            title: '{{ __("stocktake.delete_stocktake") }}?',
            html: `<p class="mb-3">{{ __("stocktake.about_to_delete") }}</p>
                   <div class="alert alert-danger text-start">
                      <i class="fas fa-tag me-2"></i>
                      <strong>${reference}</strong>
                   </div>
                   <p class="mb-0">{{ __("stocktake.action_cannot_undone") }}</p>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash me-2"></i> {{ __("stocktake.delete") }}',
            cancelButtonText: '<i class="fas fa-times me-2"></i> {{ __("stocktake.cancel") }}',
            reverseButtons: true,
            backdrop: true,
            showClass: {
                popup: 'animate__animated animate__fadeInDown'
            },
            hideClass: {
                popup: 'animate__animated animate__fadeOutUp'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    method: 'DELETE',
                    url: href,
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    dataType: 'json',
                    beforeSend: function() {
                        button.html('<span class="spinner-border spinner-border-sm me-1" role="status"></span>');
                        button.prop('disabled', true);
                    },
                    complete: function() {
                        button.html('<i class="fas fa-trash"></i>');
                        button.prop('disabled', false);
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '{{ __("stocktake.deleted") }}!',
                                text: response.msg,
                                timer: 2000,
                                showConfirmButton: false,
                                showClass: {
                                    popup: 'animate__animated animate__fadeInDown'
                                },
                                hideClass: {
                                    popup: 'animate__animated animate__fadeOutUp'
                                }
                            });
                            table.ajax.reload(null, false);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ __("stocktake.error") }}',
                                text: response.msg,
                                showClass: {
                                    popup: 'animate__animated animate__fadeInDown'
                                },
                                hideClass: {
                                    popup: 'animate__animated animate__fadeOutUp'
                                }
                            });
                        }
                    },
                    error: function(xhr) {
                        var errorMsg = xhr.responseJSON && xhr.responseJSON.msg 
                            ? xhr.responseJSON.msg 
                            : '{{ __("stocktake.something_went_wrong_delete") }}';
                        Swal.fire({
                            icon: 'error',
                            title: '{{ __("stocktake.error") }}',
                            text: errorMsg,
                            showClass: {
                                popup: 'animate__animated animate__fadeInDown'
                            },
                            hideClass: {
                                popup: 'animate__animated animate__fadeOutUp'
                            }
                        });
                    }
                });
            }
        });
    });

    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl+F for filter focus
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            $('input[type="search"]').focus();
        }
        
        // Ctrl+R for reset filters
        if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
            e.preventDefault();
            $('#reset_filters').click();
        }
        
        // Ctrl+N for new stocktake
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            @can('stocktake.create')
            window.location.href = "{{ route('stocktakes.create') }}";
            @endcan
        }

        // Escape to clear search
        if (e.key === 'Escape') {
            $('input[type="search"]').val('').trigger('input');
        }
    });

    // Handle page visibility changes
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'visible') {
            console.log('Stocktakes page is now visible');
            // Refresh data when page becomes visible if auto-refresh is enabled
            if ($('#auto-refresh-toggle').is(':checked')) {
                table.ajax.reload(null, false);
            }
        }
    });

    // Initialize last updated time
    updateLastUpdatedTime();

    // Set default custom dates when custom range is selected
    $('#date_range_filter').on('change', function() {
        if ($(this).val() === 'custom') {
            setDefaultCustomDates();
        }
    });
});
</script>

<style>
    html, body {
        overflow-x: hidden !important;
        max-width: 100%;
    }

    .container-fluid,
    .content,
    .content-wrapper {
        overflow-x: hidden !important;
        max-width: 100%;
    }

    /* Modern Card Hover Effects */
    .hover-lift {
        transition: all 0.3s ease;
    }
    
    .hover-lift:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
    }

    /* Smooth transitions for stats */
    #total-stocktakes, #in-progress-stocktakes, 
    #completed-stocktakes, #cancelled-stocktakes {
        transition: all 0.5s ease;
    }

    /* Table row hover effect */
    #stocktakes-table tbody tr {
        transition: all 0.2s ease;
    }

    #stocktakes-table tbody tr:hover {
        background-color: #f8f9fa;
        transform: none !important;
        box-shadow: none !important;
    }

    #stocktakes-table tbody tr:hover > td {
        transform: none !important;
    }

    #stocktakes-table .btn:hover,
    .stocktake-table-scroll .btn:hover {
        transform: none !important;
        box-shadow: none !important;
    }

    /* Status badge styles */
    .badge {
        font-weight: 600;
        letter-spacing: 0.3px;
    }

    /* Filter card animation */
    #advanced-filters {
        animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            max-height: 0;
        }
        to {
            opacity: 1;
            max-height: 200px;
        }
    }

    /* Button hover effects */
    .btn {
        transition: all 0.2s ease;
    }

    .btn:hover {
        transform: none !important;
        box-shadow: none !important;
    }

    /* DataTables button styling */
    .dt-buttons .btn {
        margin: 0 2px;
        border-radius: 6px;
    }

    /* Action button group spacing */
    .btn-group .btn {
        margin: 0 2px;
    }

    /* Spinning icon animation */
    @keyframes gentleSpin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .fa-spin {
        animation: gentleSpin 3s linear infinite;
    }

    /* Alert animations */
    .alert {
        animation: slideInDown 0.3s ease-out;
    }

    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endsection