@extends('layouts.app')

@section('title', __('payment.users_management'))

@section('content')
<div class="dashboard-wrapper" style="max-width: 100%; overflow-x: hidden; padding: 1.5rem; margin: 0 auto;">

    <!-- Header Banner -->
    <div class="tw-mb-5 tw-rounded-xl tw-bg-gradient-to-r tw-from-primary-800 tw-to-primary-900 tw-text-white">
        <div class="tw-p-5">
            <div class="sm:tw-flex sm:tw-items-center sm:tw-justify-between sm:tw-gap-6">
                <div>
                    <h1 class="tw-text-2xl md:tw-text-3xl tw-font-semibold tw-tracking-tight tw-text-white tw-mb-1">
                        <i class="fas fa-users me-2"></i>{{ __('payment.users_management') }}
                    </h1>
                    <p class="tw-mb-0 tw-text-white/80">{{ __('payment.total_users', ['count' => $users->total()]) }}</p>
                </div>
                <div class="tw-mt-3 sm:tw-mt-0 tw-flex tw-flex-wrap tw-gap-2 sm:tw-justify-end">
                    <button class="tw-inline-flex tw-items-center tw-justify-center tw-gap-1 tw-px-4 tw-py-2 tw-text-sm tw-font-medium tw-text-gray-900 tw-transition-all tw-duration-200 tw-bg-white tw-rounded-lg hover:tw-bg-primary-50" data-bs-toggle="modal" data-bs-target="#filtersModal">
                        <i class="fas fa-filter me-1"></i> {{ __('payment.filters') }}
                    </button>
                    <a href="{{ route('admin.dashboard') }}"
                       class="tw-inline-flex tw-items-center tw-justify-center tw-gap-1 tw-px-4 tw-py-2 tw-text-sm tw-font-medium tw-text-gray-900 tw-transition-all tw-duration-200 tw-bg-white tw-rounded-lg hover:tw-bg-primary-50">
                        <i class="fas fa-arrow-left me-1"></i> {{ __('payment.dashboard') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Quick Stats -->
    <div class="tw-grid tw-grid-cols-1 tw-gap-4 sm:tw-grid-cols-2 xl:tw-grid-cols-4 sm:tw-gap-5 tw-mb-5">
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-12 tw-h-12 tw-rounded-full tw-shrink-0 tw-bg-blue-100 tw-text-blue-600">
                        <i class="fas fa-users fa-lg"></i>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">{{ __('payment.total') }}</p>
                        <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            {{ $stats['total'] ?? 0 }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-12 tw-h-12 tw-rounded-full tw-shrink-0 tw-bg-green-100 tw-text-green-600">
                        <i class="fas fa-check-circle fa-lg"></i>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">{{ __('payment.active') }}</p>
                        <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            {{ $stats['active'] ?? 0 }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-12 tw-h-12 tw-rounded-full tw-shrink-0 tw-bg-yellow-100 tw-text-yellow-600">
                        <i class="fas fa-pause-circle fa-lg"></i>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">{{ __('payment.inactive') }}</p>
                        <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            {{ $stats['inactive'] ?? 0 }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-12 tw-h-12 tw-rounded-full tw-shrink-0 tw-bg-red-100 tw-text-red-600">
                        <i class="fas fa-times-circle fa-lg"></i>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">{{ __('payment.terminated') }}</p>
                        <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            {{ $stats['terminated'] ?? 0 }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm rounded-3 border-0">
        <div class="card-body p-0">
            <!-- Users Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" style="width: 50px;">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                            </th>
                            <th style="min-width: 250px;">{{ __('payment.user') }}</th>
                            <th style="min-width: 150px;">{{ __('payment.business') }}</th>
                            <th style="min-width: 120px;">{{ __('payment.username') }}</th>
                            <th style="width: 150px;">{{ __('payment.subscriptions') }}</th>
                            <th style="width: 150px;">{{ __('payment.status') }}</th>
                            <th style="width: 150px;">{{ __('payment.last_login') }}</th>
                            <th style="width: 120px;">{{ __('payment.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users ?? [] as $user)
                        <tr class="align-middle">
                            <td class="ps-4">
                                <input type="checkbox" class="form-check-input user-checkbox" value="{{ $user->id }}">
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full tw-bg-blue-100 tw-text-blue-600 tw-font-semibold tw-text-sm tw-shrink-0">
                                        {{ substr($user->name ?? 'U', 0, 1) }}
                                    </div>
                                    <div class="tw-min-w-0 tw-flex-1">
                                        <div class="tw-font-medium tw-text-gray-900 tw-truncate">{{ $user->name }}</div>
                                        <div class="tw-text-sm tw-text-gray-500 tw-truncate">{{ $user->email }}</div>
                                        <div class="tw-text-xs tw-text-gray-400">{{ __('payment.id') }}: {{ $user->id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($user->business)
                                    <span class="badge bg-info text-white px-2 py-1">{{ $user->business->name }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-gray-700">{{ $user->username ?? '—' }}</span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary px-2 py-1">{{ $user->subscriptions_count ?? 0 }}</span>
                                    @if($user->subscriptions_count > 0)
                                        <small class="text-muted">{{ __('payment.last') }}: {{ optional($user->latest_subscription)->created_at->diffForHumans() ?? '—' }}</small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <form action="{{ route('admin.users.update-status', ['user' => $user->id]) }}" method="POST" class="user-status-form">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" 
                                        class="form-select form-select-sm user-status" 
                                        data-user-id="{{ $user->id }}"
                                        title="{{ __('payment.change_status') }}">
                                        <option value="active" {{ $user->status == 'active' ? 'selected' : '' }}>{{ __('payment.active') }}</option>
                                        <option value="inactive" {{ $user->status == 'inactive' ? 'selected' : '' }}>{{ __('payment.inactive') }}</option>
                                        <option value="terminated" {{ $user->status == 'terminated' ? 'selected' : '' }}>{{ __('payment.terminated') }}</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                @if($user->last_login_at)
                                    <span class="text-muted" title="{{ $user->last_login_at->format('M d, Y H:i') }}">
                                        {{ $user->last_login_at->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="text-muted">{{ __('payment.never') }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.users.show', ['user' => $user->id]) }}" 
                                       class="btn btn-info" 
                                       title="{{ __('payment.view_details') }}">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button class="btn btn-outline-secondary dropdown-toggle" 
                                            type="button" 
                                            data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="#">
                                                <i class="fas fa-envelope me-2"></i>{{ __('payment.send_email') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="#">
                                                <i class="fas fa-file-invoice me-2"></i>{{ __('payment.view_invoices') }}
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('admin.users.destroy', ['user' => $user->id]) }}" 
                                                  method="POST" 
                                                  class="d-inline delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="fas fa-trash me-2"></i>{{ __('payment.delete_user') }}
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <p class="text-muted">{{ __('payment.no_users_found') }}</p>
                                <a href="#" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus me-1"></i> {{ __('payment.add_new_user') }}
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Bulk Actions -->
            <div class="d-flex justify-content-between align-items-center p-3 bg-light">
                <div>
                    <select class="form-select form-select-sm me-2" style="width: auto;" id="bulkAction">
                        <option value="">{{ __('payment.bulk_actions') }}</option>
                        <option value="activate">{{ __('payment.activate_selected') }}</option>
                        <option value="deactivate">{{ __('payment.deactivate_selected') }}</option>
                        <option value="terminate">{{ __('payment.terminate_selected') }}</option>
                        <option value="delete">{{ __('payment.delete_selected') }}</option>
                    </select>
                    <button class="btn btn-sm btn-outline-primary" id="applyBulkAction">{{ __('payment.apply') }}</button>
                </div>
                
                @if($users?->count() > 0)
                <div class="d-flex align-items-center">
                    <span class="text-muted me-3">{{ __('payment.showing_entries', ['from' => $users->firstItem(), 'to' => $users->lastItem(), 'total' => $users->total()]) }}</span>
                    {{ $users->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Filters Modal -->
<div class="modal fade" id="filtersModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('payment.filter_users') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="GET" action="{{ route('admin.users') }}">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payment.status') }}</label>
                            <select class="form-select" name="status">
                                <option value="">{{ __('payment.all_statuses') }}</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>{{ __('payment.active') }}</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>{{ __('payment.inactive') }}</option>
                                <option value="terminated" {{ request('status') == 'terminated' ? 'selected' : '' }}>{{ __('payment.terminated') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payment.business') }}</label>
                            <select class="form-select" name="business_id">
                                <option value="">{{ __('payment.all_businesses') }}</option>
                                @foreach($businesses ?? [] as $business)
                                    <option value="{{ $business->id }}" {{ request('business_id') == $business->id ? 'selected' : '' }}>
                                        {{ $business->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payment.sort_by') }}</label>
                            <select class="form-select" name="sort">
                                <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>{{ __('payment.newest_first') }}</option>
                                <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>{{ __('payment.oldest_first') }}</option>
                                <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>{{ __('payment.name_az') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('payment.results_per_page') }}</label>
                            <select class="form-select" name="per_page">
                                <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                                <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('admin.users') }}" class="btn btn-secondary">{{ __('payment.clear_filters') }}</a>
                        <button type="submit" class="btn btn-primary">{{ __('payment.apply_filters') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.avatar-sm {
    width: 40px;
    height: 40px;
    font-weight: 600;
    font-size: 16px;
}

.user-status {
    transition: all 0.3s ease;
    cursor: pointer;
    min-width: 120px;
}

.user-status.active {
    background: linear-gradient(45deg, #198754, #20c997) !important;
    color: white !important;
    border-color: #198754;
}

.user-status.inactive {
    background: linear-gradient(45deg, #ffc107, #ffca2c) !important;
    color: #000 !important;
    border-color: #ffc107;
}

.user-status.terminated {
    background: linear-gradient(45deg, #dc3545, #fd7e14) !important;
    color: white !important;
    border-color: #dc3545;
}

.table-hover tbody tr:hover {
    background-color: rgba(13, 110, 253, 0.04) !important;
}

.badge {
    font-weight: 500;
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,0.08);
}

/* Bulk action button styling */
#applyBulkAction:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Checkbox styling */
.form-check-input:indeterminate {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

/* Loading spinner */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

/* Toast notifications */
.toast-container {
    z-index: 1055;
}
</style>
@endpush

@push('scripts')
<script>
// Define language strings for JavaScript
const Lang = {
    get: function(key, params = {}) {
        const translations = {
            // Status update
            'payment.updating': 'Updating...',
            'payment.success': 'Success',
            'payment.error': 'Error',
            'payment.warning': 'Warning',
            'payment.status_updated_successfully': 'User status updated successfully',
            'payment.update_failed': 'Failed to update status',
            'payment.something_wrong': 'Something went wrong. Please try again.',
            
            // Bulk actions
            'payment.select_action_users': 'Please select an action and at least one user',
            'payment.confirm_delete_users': 'Are you sure you want to delete {count} user(s)? This action cannot be undone.',
            'payment.confirm_terminate_users': 'Are you sure you want to terminate {count} user(s)?',
            'payment.processing': 'Processing...',
            'payment.bulk_action_failed': 'Failed to process bulk action',
            
            // Delete confirmation
            'payment.confirm_delete_user': 'Are you sure you want to delete this user? This action cannot be undone.'
        };
        
        let message = translations[key] || key;
        
        // Replace placeholders with actual values
        for (const [param, value] of Object.entries(params)) {
            message = message.replace(`{${param}}`, value);
            message = message.replace(`:${param}`, value);
        }
        
        return message;
    }
};

$(document).ready(function() {
    // Initialize status dropdown colors
    function updateSelectColor(selectEl, status) {
        selectEl.removeClass("active inactive terminated")
                .addClass(status);
    }

    $('.user-status').each(function() {
        updateSelectColor($(this), $(this).val());
        $(this).data('original-value', $(this).val());
    });

    // Status change handler
    $('.user-status').on('change', function() {
        let form = $(this).closest('form');
        let selectEl = $(this);
        let originalValue = selectEl.data('original-value');

        // Show loading state
        selectEl.prop('disabled', true);
        let currentHtml = selectEl.html();
        selectEl.html('<option>' + Lang.get('payment.updating') + '</option>');

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: form.serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    updateSelectColor(selectEl, response.status);
                    selectEl.data('original-value', response.status);
                    showToast(Lang.get('payment.success'), Lang.get('payment.status_updated_successfully'), 'success');
                } else {
                    selectEl.val(originalValue);
                    updateSelectColor(selectEl, originalValue);
                    showToast(Lang.get('payment.error'), response.message || Lang.get('payment.update_failed'), 'error');
                }
            },
            error: function(xhr) {
                selectEl.val(originalValue);
                updateSelectColor(selectEl, originalValue);
                showToast(Lang.get('payment.error'), xhr.responseJSON?.message || Lang.get('payment.something_wrong'), 'error');
            },
            complete: function() {
                // Restore options
                selectEl.html(currentHtml).val(selectEl.val()).prop('disabled', false);
                updateSelectColor(selectEl, selectEl.val());
            }
        });
    });

    // Select all checkbox
    $('#selectAll').on('change', function() {
        $('.user-checkbox').prop('checked', this.checked);
        toggleBulkActionButton();
    });

    // Individual checkbox change
    $('.user-checkbox').on('change', function() {
        toggleBulkActionButton();
        // Update select all checkbox state
        const allChecked = $('.user-checkbox:checked').length === $('.user-checkbox').length;
        const someChecked = $('.user-checkbox:checked').length > 0;
        $('#selectAll').prop('checked', allChecked);
        $('#selectAll').prop('indeterminate', someChecked && !allChecked);
    });

    // Toggle bulk action button based on selection
    function toggleBulkActionButton() {
        const hasSelection = $('.user-checkbox:checked').length > 0;
        const hasAction = $('#bulkAction').val() !== '';
        $('#applyBulkAction').prop('disabled', !(hasSelection && hasAction));
    }

    // Bulk action selection change
    $('#bulkAction').on('change', toggleBulkActionButton);

    // Bulk actions - FIXED: Now properly handles the click event
    $('#applyBulkAction').on('click', function() {
        const action = $('#bulkAction').val();
        const selectedUsers = $('.user-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (!action || selectedUsers.length === 0) {
            showToast(Lang.get('payment.warning'), Lang.get('payment.select_action_users'), 'warning');
            return;
        }

        // Confirm destructive actions
        if (['terminate', 'delete'].includes(action)) {
            const confirmMessage = action === 'delete' 
                ? Lang.get('payment.confirm_delete_users', {count: selectedUsers.length})
                : Lang.get('payment.confirm_terminate_users', {count: selectedUsers.length});
            
            if (!confirm(confirmMessage)) {
                return;
            }
        }

        // Show loading state
        const button = $(this);
        const originalText = button.html();
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span> ' + Lang.get('payment.processing'));

        $.ajax({
            url: '{{ route("admin.users.bulk-action") }}',
            method: 'POST',
            data: {
                action: action,
                users: selectedUsers,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    showToast(Lang.get('payment.success'), response.message, 'success');
                    // Reload page after a short delay to see changes
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast(Lang.get('payment.error'), response.message || Lang.get('payment.bulk_action_failed'), 'error');
                    button.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                showToast(Lang.get('payment.error'), xhr.responseJSON?.message || Lang.get('payment.something_wrong'), 'error');
                button.prop('disabled', false).html(originalText);
            }
        });
    });

    // Delete confirmation
    $('.delete-form').on('submit', function(e) {
        e.preventDefault();
        if (confirm(Lang.get('payment.confirm_delete_user'))) {
            this.submit();
        }
    });

    // Toast notification function
    function showToast(title, message, type = 'info') {
        // Create toast element if it doesn't exist
        if ($('#toastContainer').length === 0) {
            $('body').append('<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055"></div>');
        }

        const toastId = 'toast-' + Date.now();
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white bg-${type}" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${title}</strong>: ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

        $('#toastContainer').append(toastHtml);
        
        // Initialize Bootstrap toast
        const toastElement = new bootstrap.Toast(document.getElementById(toastId));
        toastElement.show();

        // Remove toast after it's hidden
        document.getElementById(toastId).addEventListener('hidden.bs.toast', function () {
            this.remove();
        });
    }

    // Initialize tooltips
    $('[title]').tooltip();
    
    // Initialize the button state on page load
    toggleBulkActionButton();
});
</script>
@endpush