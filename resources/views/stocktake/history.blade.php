@extends('layouts.app')

@section('title', __('stocktake.stocktake_history'))

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <!-- Card Header -->
        <div class="card-header bg-white py-3 border-bottom">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <i class="fas fa-history fs-4 text-primary me-3"></i>
                    <div>
                        <h3 class="mb-0 fw-semibold">@lang('stocktake.stocktake_history')</h3>
                        <p class="text-muted mb-0 small">@lang('stocktake.stocktake_history_description')</p>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <a href="{{ route('stocktakes.variance_report') }}" class="btn btn-info me-2">
                        <i class="fas fa-chart-bar me-2"></i> @lang('stocktake.variance_report')
                    </a>
                    <!-- Export Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-success dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-file-export me-2"></i> @lang('stocktake.export')
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown">
                            <li>
                                <a class="dropdown-item" href="#" onclick="exportHistory('excel')">
                                    <i class="fas fa-file-excel text-success me-2"></i> Excel
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#" onclick="exportHistory('csv')">
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
                    @if(config('app.debug'))
                    <a href="{{ route('stocktakes.debug.routes_permissions') }}" class="btn btn-outline-warning ms-2" target="_blank">
                        <i class="fas fa-bug me-2"></i> Debug
                    </a>
                    @endif
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

            <!-- Filters Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 bg-light">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-filter text-primary me-2"></i>@lang('stocktake.filters')
                            </h5>
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
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            @if($summary && $summary->stocktake_count > 0)
            <div class="row g-3 mb-4">
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="card border-0 h-100 shadow-sm border-start border-3 border-primary">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-light-primary p-3 rounded-circle me-3">
                                    <i class="fas fa-clipboard-list fs-4 text-primary"></i>
                                </div>
                                <div>
                                    <p class="mb-1 text-muted small">@lang('stocktake.stocktake_count')</p>
                                    <h4 class="mb-0 fw-bold text-primary">{{ $summary->stocktake_count ?? 0 }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="card border-0 h-100 shadow-sm border-start border-3 border-info">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-light-info p-3 rounded-circle me-3">
                                    <i class="fas fa-cubes fs-4 text-info"></i>
                                </div>
                                <div>
                                    <p class="mb-1 text-muted small">@lang('stocktake.total_items_counted')</p>
                                    <h4 class="mb-0 fw-bold text-info">{{ number_format($summary->total_items ?? 0) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="card border-0 h-100 shadow-sm border-start border-3 border-success">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-light-success p-3 rounded-circle me-3">
                                    <i class="fas fa-plus fs-4 text-success"></i>
                                </div>
                                <div>
                                    <p class="mb-1 text-muted small">@lang('stocktake.overage_items')</p>
                                    <h4 class="mb-0 fw-bold text-success">{{ $summary->overage_count ?? 0 }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="card border-0 h-100 shadow-sm border-start border-3 border-danger">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-light-danger p-3 rounded-circle me-3">
                                    <i class="fas fa-minus fs-4 text-danger"></i>
                                </div>
                                <div>
                                    <p class="mb-1 text-muted small">@lang('stocktake.shortage_items')</p>
                                    <h4 class="mb-0 fw-bold text-danger">{{ $summary->shortage_count ?? 0 }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="card border-0 h-100 shadow-sm border-start border-3 border-warning">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-light-warning p-3 rounded-circle me-3">
                                    <i class="fas fa-balance-scale fs-4 text-warning"></i>
                                </div>
                                <div>
                                    <p class="mb-1 text-muted small">@lang('stocktake.total_variance_quantity')</p>
                                    <h4 class="mb-0 fw-bold text-warning {{ $summary->total_variance_quantity > 0 ? 'text-success' : ($summary->total_variance_quantity < 0 ? 'text-danger' : '') }}">
                                        {{ $summary->total_variance_quantity > 0 ? '+' : '' }}{{ $summary->total_variance_quantity ?? 0 }}
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-sm-6">
                    <div class="card border-0 h-100 shadow-sm border-start border-3 border-secondary">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="bg-light-secondary p-3 rounded-circle me-3">
                                    <i class="fas fa-percentage fs-4 text-secondary"></i>
                                </div>
                                <div>
                                    <p class="mb-1 text-muted small">@lang('stocktake.accuracy_rate')</p>
                                    <h4 class="mb-0 fw-bold text-secondary">
                                        @if($summary->total_items > 0)
                                            @php
                                                $exact_count = $summary->exact_count ?? ($summary->total_items - $summary->overage_count - $summary->shortage_count);
                                                $accuracy_rate = ($exact_count / $summary->total_items) * 100;
                                            @endphp
                                            {{ number_format($accuracy_rate, 1) }}%
                                        @else
                                            0%
                                        @endif
                                    </h4>
                                </div>
                            </div>
                        </div>
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
                            <button class="btn btn-sm btn-outline-primary" onclick="toggleTable('stocktake_history_table_wrapper')" data-bs-toggle="tooltip" title="Expand view">
                                <i class="fas fa-expand-alt"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-success" onclick="exportHistory('excel')" data-bs-toggle="tooltip" title="Export to Excel">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" id="stocktake_history_table_wrapper">
                        <table class="table table-hover align-middle mb-0" id="stocktake_history_table">
                            <thead class="table-light">
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