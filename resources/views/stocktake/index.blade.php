@extends('layouts.app')

@section('title', __('stocktake.stocktakes'))

@section('content')
<div class="container-fluid px-4 py-3">
    <div class="card border-0 shadow-lg rounded-3 overflow-hidden">
        <!-- Modern Gradient Header (matching Home Dashboard) -->
        <div class="card-header tw-bg-gradient-to-r tw-from-@if(!empty(session('business.theme_color'))){{session('business.theme_color')}}@else{{'primary'}}@endif-800 tw-to-@if(!empty(session('business.theme_color'))){{session('business.theme_color')}}@else{{'primary'}}@endif-900 py-4 border-0">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center">
                    <div class="tw-bg-white/20 tw-p-3 tw-rounded-lg me-3">
                        <i class="fas fa-clipboard-list fs-3 tw-text-white"></i>
                    </div>
                    <div>
                        <h3 class="mb-1 fw-bold tw-text-white">@lang('stocktake.stocktakes')</h3>
                        <p class="tw-text-white/90 mb-0 small">@lang('stocktake.manage_stocktakes_description')</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="{{ route('stocktakes.history') }}" class="btn btn-light shadow-sm">
                        <i class="fas fa-history me-2"></i> @lang('stocktake.history')
                    </a>
                    <a href="{{ route('stocktakes.variance_report') }}" class="btn btn-warning shadow-sm">
                        <i class="fas fa-chart-bar me-2"></i> @lang('stocktake.variance_report')
                    </a>
                    @can('stocktake.create')
                    <a href="{{ route('stocktakes.create') }}" class="btn btn-success shadow-sm">
                        <i class="fas fa-plus-circle me-2"></i> @lang('stocktake.add_stocktake')
                    </a>
                    @endcan
                </div>
            </div>
        </div>

        <!-- Card Body -->
        <div class="card-body bg-light">
            <!-- Status Messages -->
            @if(session('status'))
                <div class="alert alert-{{ session('status.success') ? 'success' : 'danger' }} alert-dismissible fade show shadow-sm border-0" role="alert">
                    <i class="fas fa-{{ session('status.success') ? 'check-circle' : 'exclamation-triangle' }} me-2"></i>
                    {!! session('status.msg') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Modern Stats Cards (Matching Home Dashboard) -->
            <div class="row mb-4 g-3">
                <div class="col-lg-3 col-md-6">
                    <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm tw-rounded-xl hover:tw-shadow-md hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200 h-100">
                        <div class="tw-p-4 sm:tw-p-5">
                            <div class="tw-flex tw-items-center tw-gap-4">
                                <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-12 tw-h-12 tw-rounded-full tw-shrink-0 tw-bg-blue-100 tw-text-blue-600">
                                    <i class="fas fa-clipboard-list fa-lg"></i>
                                </div>
                                <div class="tw-flex-1 tw-min-w-0">
                                    <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                                        @lang('stocktake.total_stocktakes')
                                    </p>
                                    <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-font-bold tw-tracking-tight" id="total-stocktakes">
                                        -
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm tw-rounded-xl hover:tw-shadow-md hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200 h-100">
                        <div class="tw-p-4 sm:tw-p-5">
                            <div class="tw-flex tw-items-center tw-gap-4">
                                <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-12 tw-h-12 tw-rounded-full tw-shrink-0 tw-bg-amber-100 tw-text-amber-600">
                                    <i class="fas fa-sync-alt fa-lg fa-spin"></i>
                                </div>
                                <div class="tw-flex-1 tw-min-w-0">
                                    <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                                        @lang('stocktake.in_progress')
                                    </p>
                                    <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-font-bold tw-tracking-tight" id="in-progress-stocktakes">
                                        -
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm tw-rounded-xl hover:tw-shadow-md hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200 h-100">
                        <div class="tw-p-4 sm:tw-p-5">
                            <div class="tw-flex tw-items-center tw-gap-4">
                                <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-12 tw-h-12 tw-rounded-full tw-shrink-0 tw-bg-green-100 tw-text-green-600">
                                    <i class="fas fa-check-circle fa-lg"></i>
                                </div>
                                <div class="tw-flex-1 tw-min-w-0">
                                    <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                                        @lang('stocktake.completed')
                                    </p>
                                    <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-font-bold tw-tracking-tight" id="completed-stocktakes">
                                        -
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm tw-rounded-xl hover:tw-shadow-md hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200 h-100">
                        <div class="tw-p-4 sm:tw-p-5">
                            <div class="tw-flex tw-items-center tw-gap-4">
                                <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-12 tw-h-12 tw-rounded-full tw-shrink-0 tw-bg-red-100 tw-text-red-600">
                                    <i class="fas fa-times-circle fa-lg"></i>
                                </div>
                                <div class="tw-flex-1 tw-min-w-0">
                                    <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">
                                        @lang('stocktake.cancelled')
                                    </p>
                                    <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-font-bold tw-tracking-tight" id="cancelled-stocktakes">
                                        -
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modern Filters Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="card-title mb-0 fw-bold">
                                    <i class="fas fa-filter text-primary me-2"></i>@lang('stocktake.filters')
                                </h5>
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
                                                <option value="{{ $key }}">{{ $value }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <label class="form-label fw-semibold text-secondary mb-2">
                                            <i class="fas fa-info-circle me-1 text-primary"></i>@lang('stocktake.status')
                                        </label>
                                        <select class="form-control select2" id="status_filter" name="status">
                                            <option value="">@lang('stocktake.all')</option>
                                            <option value="in_progress">@lang('stocktake.in_progress')</option>
                                            <option value="completed">@lang('stocktake.completed')</option>
                                            <option value="cancelled">@lang('stocktake.cancelled')</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <label class="form-label fw-semibold text-secondary mb-2">
                                            <i class="fas fa-calendar-alt me-1 text-primary"></i>@lang('stocktake.date_range')
                                        </label>
                                        <select class="form-control select2" id="date_range_filter" name="date_range">
                                            <option value="">@lang('stocktake.all')</option>
                                            <option value="today">@lang('stocktake.today')</option>
                                            <option value="yesterday">@lang('stocktake.yesterday')</option>
                                            <option value="this_week">@lang('stocktake.this_week')</option>
                                            <option value="last_week">@lang('stocktake.last_week')</option>
                                            <option value="this_month">@lang('stocktake.this_month')</option>
                                            <option value="last_month">@lang('stocktake.last_month')</option>
                                            <option value="custom">@lang('stocktake.custom_range')</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <label class="form-label fw-semibold text-secondary mb-2">
                                            <i class="fas fa-dollar-sign me-1 text-primary"></i>@lang('stocktake.price_basis')
                                        </label>
                                        <select class="form-control" id="price_basis_filter" name="price_basis">
                                            <option value="selling">@lang('stocktake.price_basis_selling')</option>
                                            <option value="purchase">@lang('stocktake.price_basis_purchase')</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="d-flex gap-2 justify-content-end">
                                            <button type="button" class="btn btn-primary px-4 shadow-sm" id="apply_filters">
                                                <i class="fas fa-check me-2"></i> @lang('stocktake.apply')
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary px-4" id="reset_filters">
                                                <i class="fas fa-redo me-2"></i> @lang('stocktake.reset')
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Advanced Filters -->
                                <div class="row g-3 mt-2 p-3 tw-bg-gray-50 tw-rounded-lg" id="advanced-filters" style="display: none;">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold text-secondary mb-2">
                                            <i class="fas fa-calendar-day me-1 text-primary"></i>@lang('stocktake.custom_from_date')
                                        </label>
                                        <input type="date" class="form-control" id="from_date_filter" name="from_date">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold text-secondary mb-2">
                                            <i class="fas fa-calendar-check me-1 text-primary"></i>@lang('stocktake.custom_to_date')
                                        </label>
                                        <input type="date" class="form-control" id="to_date_filter" name="to_date">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold text-secondary mb-2">
                                            <i class="fas fa-search me-1 text-primary"></i>@lang('stocktake.search_reference')
                                        </label>
                                        <input type="text" class="form-control" id="search_filter" name="search" placeholder="@lang('stocktake.enter_reference')">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modern DataTable -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-bottom">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <h5 class="mb-0 fw-bold text-dark">
                <i class="fas fa-table me-2 text-primary"></i>
                @lang('stocktake.stocktake_list')
            </h5>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="auto-refresh-toggle" checked>
                    <label class="form-check-label small text-dark" for="auto-refresh-toggle">
                        <i class="fas fa-sync-alt me-1"></i> @lang('stocktake.auto_refresh')
                    </label>
                </div>
                <span class="badge bg-light text-dark shadow-sm px-3 py-2 border" id="last-updated">
                    <i class="fas fa-clock me-1 text-primary"></i>
                    @lang('stocktake.last_updated'): <span id="last-updated-time" class="fw-bold">-</span>
                </span>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="stocktakes-table" style="width:100%">
                <thead class="tw-bg-gray-50">
                    <tr>
                        <th class="ps-4 fw-semibold">@lang('stocktake.reference_no')</th>
                        <th class="fw-semibold">@lang('stocktake.location')</th>
                        <th class="fw-semibold">@lang('stocktake.status')</th>
                        <th class="text-center fw-semibold">@lang('stocktake.product_count')</th>
                        <th class="text-end fw-semibold">@lang('stocktake.value_amount')</th>
                        <th class="fw-semibold">@lang('stocktake.started_at')</th>
                        <th class="fw-semibold">@lang('stocktake.completed_at')</th>
                        <th class="fw-semibold">@lang('stocktake.adjustment_ref')</th>
                        <th class="text-center fw-semibold">@lang('stocktake.action')</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Data will be loaded via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bulk Actions Modal -->
<div class="modal fade" id="bulkActionsModal" tabindex="-1" aria-labelledby="bulkActionsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkActionsModalLabel">@lang('stocktake.bulk_actions')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>@lang('stocktake.select_bulk_action')</p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-danger" id="bulk-delete-btn">
                        <i class="fas fa-trash me-2"></i> @lang('stocktake.bulk_delete')
                    </button>
                </div>
            </div>
        </div>
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
        placeholder: '{{ __("Filter By Status") }}',
        allowClear: true
    });

    // Initialize DataTable
    var table = $('#stocktakes-table').DataTable({
        processing: true,
        serverSide: true,
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
                    showAlert('Error loading data: ' + xhr.responseJSON.error, 'danger');
                } else {
                    showAlert('Error loading stocktakes. Please try again.', 'danger');
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
            $('#total-stocktakes').text(json.stats.total || 0);
            $('#in-progress-stocktakes').text(json.stats.in_progress || 0);
            $('#completed-stocktakes').text(json.stats.completed || 0);
            $('#cancelled-stocktakes').text(json.stats.cancelled || 0);
        }
    }

    // Update last updated time
    function updateLastUpdatedTime() {
        const timeString = lastUpdated.toLocaleTimeString();
        $('#last-updated-time').text(timeString);
    }

    // Format date to locale string
    function formatDateTime(dateString) {
        if (!dateString) return '';
        try {
            var date = new Date(dateString);
            if (isNaN(date.getTime())) {
                return dateString;
            }
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        } catch (e) {
            return dateString;
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
        transform: scale(1.005);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
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
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
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