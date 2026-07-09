@extends('layouts.app')

@section('title', __('payment.users_management'))

@section('content')
@php
    $isSuperadminAdminPanel = request()->routeIs('superadmin.admin.*');
    $dashboardRoute = $isSuperadminAdminPanel ? 'superadmin.admin.dashboard' : 'admin.dashboard';
    $usersIndexRoute = $isSuperadminAdminPanel ? 'superadmin.admin.users' : 'admin.users';
    $userStatusRoute = $isSuperadminAdminPanel ? 'superadmin.admin.users.update-status' : 'admin.users.update-status';
    $userShowRoute = $isSuperadminAdminPanel ? 'superadmin.admin.users.show' : 'admin.users.show';
    $userDestroyRoute = $isSuperadminAdminPanel ? 'superadmin.admin.users.destroy' : 'admin.users.destroy';
@endphp

@if($isSuperadminAdminPanel)
    @include('superadmin::layouts.nav')

    <section class="content-header">
        <div class="tw-flex tw-items-center tw-gap-3 tw-text-sm tw-text-gray-600">
            <span class="tw-inline-flex tw-items-center tw-gap-2 tw-rounded-full tw-bg-white tw-px-3 tw-py-1 tw-shadow-sm tw-ring-1 tw-ring-gray-200">
                <i class="fas fa-user-shield tw-text-primary-600"></i>
                <span>{{ __('superadmin::lang.superadmin') }}</span>
            </span>
            <span class="tw-text-gray-400">/</span>
            <span class="tw-font-medium tw-text-gray-700">{{ __('payment.users_management') }}</span>
        </div>
    </section>

    <section class="content">
@endif

<div class="dashboard-wrapper" style="max-width: 100%; overflow-x: hidden; padding: 1.5rem; margin: 0 auto;">

    <div class="tw-mb-5 tw-overflow-hidden tw-rounded-2xl tw-border tw-border-slate-200 tw-bg-white" style="box-shadow: 0 20px 46px rgba(15, 23, 42, 0.10);">
        <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-[minmax(0,1fr)_220px]">
            <div class="tw-p-5 md:tw-p-6" style="background: linear-gradient(135deg, #0f172a 0%, #0f766e 55%, #14b8a6 100%);">
                <div class="sm:tw-flex sm:tw-items-start sm:tw-justify-between sm:tw-gap-6">
                    <div class="tw-max-w-2xl">
                        <span class="tw-inline-flex tw-items-center tw-gap-2 tw-rounded-full tw-px-3 tw-py-1 tw-text-xs tw-font-semibold tw-uppercase tw-tracking-[0.18em] tw-text-white" style="background: rgba(255,255,255,0.16); border: 1px solid rgba(255,255,255,0.20);">
                            <i class="fas fa-users"></i>
                            User oversight
                        </span>
                        <h1 class="tw-mt-3 tw-text-2xl tw-font-semibold tw-tracking-tight tw-text-white md:tw-text-3xl">
                            {{ __('payment.users_management') }}
                        </h1>
                        <p class="tw-mt-2 tw-max-w-2xl tw-text-sm tw-leading-6 tw-text-white" style="opacity: 0.94;">
                            {{ __('payment.total_users', ['count' => $users->total()]) }}. Review account status, business assignments, and latest subscription activity.
                        </p>
                    </div>
                    <div class="tw-mt-4 sm:tw-mt-0 tw-flex tw-flex-wrap tw-gap-2 sm:tw-justify-end">
                        <button type="button" id="openUsersFiltersModal" class="tw-inline-flex tw-items-center tw-justify-center tw-gap-2 tw-rounded-xl tw-bg-white tw-px-4 tw-py-2.5 tw-text-sm tw-font-semibold tw-text-slate-900 tw-shadow-sm hover:tw-bg-slate-100">
                            <i class="fas fa-filter"></i> {{ __('payment.filters') }}
                        </button>
                        <a href="{{ route($dashboardRoute) }}"
                           class="tw-inline-flex tw-items-center tw-justify-center tw-gap-2 tw-rounded-xl tw-bg-white/12 tw-px-4 tw-py-2.5 tw-text-sm tw-font-semibold tw-text-white tw-ring-1 tw-ring-white/20 hover:tw-bg-white/18">
                            <i class="fas fa-arrow-left"></i> {{ __('payment.dashboard') }}
                        </a>
                    </div>
                </div>
            </div>
            <div class="tw-flex tw-bg-slate-50 tw-p-4 tw-gap-3">
                <div class="tw-flex tw-flex-1 tw-items-center tw-justify-center tw-rounded-xl tw-bg-white tw-px-4 tw-py-3 tw-ring-1 tw-ring-slate-200">
                    <span class="tw-inline-flex tw-items-center tw-gap-2">
                        <span class="tw-text-xs tw-font-semibold tw-uppercase tw-tracking-[0.16em] tw-text-slate-500">{{ __('payment.total') }}</span>
                        <span class="tw-text-lg tw-font-semibold tw-leading-none tw-text-slate-900">{{ $stats['total'] ?? 0 }}</span>
                    </span>
                </div>
                <div class="tw-flex tw-flex-1 tw-items-center tw-justify-center tw-rounded-xl tw-bg-white tw-px-4 tw-py-3 tw-ring-1 tw-ring-slate-200">
                    <span class="tw-inline-flex tw-items-center tw-gap-2">
                        <span class="tw-text-xs tw-font-semibold tw-uppercase tw-tracking-[0.16em] tw-text-slate-500">{{ __('payment.active') }}</span>
                        <span class="tw-text-lg tw-font-semibold tw-leading-none tw-text-slate-900">{{ $stats['active'] ?? 0 }}</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="tw-mb-5 tw-overflow-hidden tw-rounded-2xl tw-border tw-border-emerald-200 tw-bg-gradient-to-r tw-from-emerald-50 tw-via-white tw-to-teal-50 tw-shadow-sm" role="alert">
            <div class="tw-flex tw-flex-col tw-gap-4 tw-p-4 sm:tw-flex-row sm:tw-items-center sm:tw-justify-between sm:tw-p-5">
                <div class="tw-flex tw-items-start tw-gap-3">
                    <span class="tw-inline-flex tw-h-11 tw-w-11 tw-shrink-0 tw-items-center tw-justify-center tw-rounded-2xl tw-bg-emerald-500 tw-text-white tw-shadow-sm">
                        <i class="fas fa-check"></i>
                    </span>
                    <div>
                        <p class="tw-mb-1 tw-text-sm tw-font-semibold tw-uppercase tw-tracking-[0.18em] tw-text-emerald-700">Update applied</p>
                        <p class="tw-mb-0 tw-text-sm tw-leading-6 tw-text-slate-700">{{ session('success') }}</p>
                    </div>
                </div>
                <button type="button" class="btn-close tw-self-start sm:tw-self-center" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="tw-mb-5 tw-overflow-hidden tw-rounded-2xl tw-border tw-border-rose-200 tw-bg-gradient-to-r tw-from-rose-50 tw-via-white tw-to-orange-50 tw-shadow-sm" role="alert">
            <div class="tw-flex tw-flex-col tw-gap-4 tw-p-4 sm:tw-flex-row sm:tw-items-center sm:tw-justify-between sm:tw-p-5">
                <div class="tw-flex tw-items-start tw-gap-3">
                    <span class="tw-inline-flex tw-h-11 tw-w-11 tw-shrink-0 tw-items-center tw-justify-center tw-rounded-2xl tw-bg-rose-500 tw-text-white tw-shadow-sm">
                        <i class="fas fa-exclamation"></i>
                    </span>
                    <div>
                        <p class="tw-mb-1 tw-text-sm tw-font-semibold tw-uppercase tw-tracking-[0.18em] tw-text-rose-700">Action needs attention</p>
                        <p class="tw-mb-0 tw-text-sm tw-leading-6 tw-text-slate-700">{{ session('error') }}</p>
                    </div>
                </div>
                <button type="button" class="btn-close tw-self-start sm:tw-self-center" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
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
                                <form action="{{ route($userStatusRoute, ['user' => $user->id]) }}" method="POST" class="user-status-form">
                                    @csrf
                                    @method('PATCH')
                                    <select name="status" 
                                        class="form-select form-select-sm user-status" 
                                        data-user-id="{{ $user->id }}"
                                        onchange="this.form.submit()"
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
                                    <a href="{{ route($userShowRoute, ['user' => $user->id]) }}" 
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
                                            <form action="{{ route($userDestroyRoute, ['user' => $user->id]) }}" 
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
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="GET" action="{{ route($usersIndexRoute) }}">
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
                        <a href="{{ route($usersIndexRoute) }}" class="btn btn-secondary">{{ __('payment.clear_filters') }}</a>
                        <button type="submit" class="btn btn-primary">{{ __('payment.apply_filters') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@if($isSuperadminAdminPanel)
    </section>
@endif
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

    $('#openUsersFiltersModal').on('click', function(e) {
        e.preventDefault();
        $('#filtersModal').modal('show');
    });
    
    // Initialize the button state on page load
    toggleBulkActionButton();
});
</script>
@endpush