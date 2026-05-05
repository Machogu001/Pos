@extends('layouts.app')

@section('title', __('payment.admin_dashboard'))

@section('content')
<div class="dashboard-wrapper" style="max-width: 100%; overflow-x: hidden; padding: 1.5rem; margin: 0 auto;">

    <!-- Header Banner (match Home dashboard styling) -->
    <div class="tw-mb-5 tw-rounded-xl tw-bg-gradient-to-r tw-from-primary-800 tw-to-primary-900 tw-text-white">
        <div class="tw-p-5">
            <div class="sm:tw-flex sm:tw-items-center sm:tw-justify-between sm:tw-gap-6">
                <div>
                    <h1 class="tw-text-2xl md:tw-text-3xl tw-font-semibold tw-tracking-tight tw-text-white tw-mb-1">
                        <i class="fas fa-tachometer-alt me-2"></i> {{ __('payment.admin_dashboard') }}
                    </h1>
                    <p class="tw-mb-0 tw-text-white/80">{{ __('payment.overview_text') }}</p>
                </div>
                <div class="tw-mt-3 sm:tw-mt-0 tw-flex tw-flex-wrap tw-gap-2 sm:tw-justify-end">
                    <a href="{{ route('admin.subscriptions') }}"
                       class="tw-inline-flex tw-items-center tw-justify-center tw-gap-1 tw-px-3 tw-py-2 tw-text-sm tw-font-medium tw-text-gray-900 tw-transition-all tw-duration-200 tw-bg-white tw-rounded-lg hover:tw-bg-primary-50">
                        <i class="fas fa-receipt me-1"></i> {{ __('payment.view_subscriptions') }}
                    </a>
                    <a href="{{ route('admin.users') }}"
                       class="tw-inline-flex tw-items-center tw-justify-center tw-gap-1 tw-px-3 tw-py-2 tw-text-sm tw-font-medium tw-text-gray-900 tw-transition-all tw-duration-200 tw-bg-white tw-rounded-lg hover:tw-bg-primary-50">
                        <i class="fas fa-users me-1"></i> {{ __('payment.view_users') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards (match Home dashboard styling) -->
    <div class="tw-grid tw-grid-cols-1 tw-gap-4 sm:tw-grid-cols-2 xl:tw-grid-cols-4 sm:tw-gap-5 tw-mb-5">
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-sky-100 tw-text-sky-500">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">{{ __('payment.total_businesses') }}</p>
                        <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            {{ $totalBusinesses ?? $totalUsers ?? 0 }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-green-100 tw-text-green-500">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">{{ __('payment.active') }}</p>
                        <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            {{ $activeUsers ?? 0 }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-yellow-100 tw-text-yellow-500">
                        <i class="fas fa-pause-circle"></i>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">{{ __('payment.inactive') }}</p>
                        <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            {{ $inactiveUsers ?? 0 }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-red-100 tw-text-red-500">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate tw-whitespace-nowrap">{{ __('payment.terminated') }}</p>
                        <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            {{ $terminatedUsers ?? 0 }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $hrm_enabled = false;
        $user = auth()->user();
        $business = session('business') ?? null;
        $enabled_modules = (array) ($business['enabled_modules'] ?? []);
        if ($user) {
            $has_hrm_perm = $user->can('hrm.access')
                || $user->can('hrm.companies')
                || $user->can('hrm.departments')
                || $user->can('hrm.designations')
                || $user->can('hrm.office_shifts')
                || $user->can('hrm.employees')
                || $user->can('hrm.payrolls');
            $hrm_enabled = (in_array('hrm', $enabled_modules) || in_array('Hrm', $enabled_modules)) && $has_hrm_perm;
        }
    @endphp

    <!-- Management & Configuration Cards -->
    <div class="tw-grid tw-grid-cols-1 tw-gap-4 sm:tw-grid-cols-2 xl:tw-grid-cols-4 sm:tw-gap-5 tw-mb-5">
        <!-- Subscription Enforcement -->
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200 tw-cursor-pointer" onclick="openModal('subscriptionEnforcementModal')">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 {{ ($settings->subscription_required ?? false) ? 'tw-bg-green-100 tw-text-green-600' : 'tw-bg-gray-100 tw-text-gray-400' }}">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">Subscription Enforcement</p>
                        <p class="tw-mt-0.5 tw-text-xs tw-text-gray-600 tw-line-clamp-2">
                            @if($settings->subscription_required ?? false)
                                Enabled: Users must have an active subscription
                            @else
                                Disabled: Users can use the system without subscription
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- M-Pesa Credentials -->
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200 tw-cursor-pointer" onclick="openModal('mpesaCredentialsModal')">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-blue-100 tw-text-blue-600">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">Subscription M-Pesa Credentials</p>
                        <p class="tw-mt-0.5 tw-text-xs tw-text-gray-600 tw-line-clamp-2">Configure separate M-Pesa credentials for subscription payments</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Management -->
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200 tw-cursor-pointer" onclick="openModal('userManagementModal')">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-purple-100 tw-text-purple-600">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">{{ __('payment.user_management') }}</p>
                        <p class="tw-mt-0.5 tw-text-xs tw-text-gray-600 tw-line-clamp-2">{{ __('payment.manage_users_businesses') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Subscriptions -->
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200 tw-cursor-pointer" onclick="openModal('subscriptionsModal')">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-green-100 tw-text-green-600">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">{{ __('payment.recent_subscriptions') }}</p>
                        <p class="tw-mt-0.5 tw-text-xs tw-text-gray-600 tw-line-clamp-2">{{ __('payment.view_manage_subscriptions') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manual Subscription Management -->
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200 tw-cursor-pointer" onclick="openModal('manualSubscriptionModal')">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-emerald-100 tw-text-emerald-600">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">{{ __('payment.manual_subscription_management') }}</p>
                        <p class="tw-mt-0.5 tw-text-xs tw-text-gray-600 tw-line-clamp-2">Manually create subscriptions for users without M-Pesa payment</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Registration -->
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200 tw-cursor-pointer" onclick="openModal('registrationModal')">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-blue-100 tw-text-blue-600">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">{{ __('payment.registration') }}</p>
                        <p class="tw-mt-0.5 tw-text-xs tw-text-gray-600 tw-line-clamp-2">{{ __('payment.configure_plans_pricing') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payroll -->
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200 tw-cursor-pointer" onclick="openModal('payrollModal')">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-green-100 tw-text-green-600">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">{{ __('payment.payroll') }}</p>
                        <p class="tw-mt-0.5 tw-text-xs tw-text-gray-600 tw-line-clamp-2">{{ __('payment.manage_payroll_tax_bands') }}</p>
                    </div>
                </div>
            </div>
        </div>

        @if($hrm_enabled)
        <!-- HRM Module Shortcut -->
        <a href="{{ url('/hrm/dashboard') }}" class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-indigo-200 tw-cursor-pointer tw-flex tw-items-stretch">
            <div class="tw-p-4 sm:tw-p-5 tw-flex tw-items-center tw-gap-4 tw-w-full">
                <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-indigo-100 tw-text-indigo-600">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="tw-flex-1 tw-min-w-0">
                    <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">Human Resource Management</p>
                    <p class="tw-mt-0.5 tw-text-xs tw-text-gray-600 tw-line-clamp-2">
                        Open HRM (employees, leave, HR payroll, departments)
                    </p>
                </div>
                <div class="tw-hidden sm:tw-flex tw-items-center tw-text-indigo-500">
                    <i class="fas fa-arrow-right"></i>
                </div>
            </div>
        </a>
        @endif

        <!-- Invoicing -->
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200 tw-cursor-pointer" onclick="openModal('invoicingModal')">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-yellow-100 tw-text-yellow-600">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">{{ __('payment.invoicing') }}</p>
                        <p class="tw-mt-0.5 tw-text-xs tw-text-gray-600 tw-line-clamp-2">{{ __('payment.configure_invoice_settings') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- System -->
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200 tw-cursor-pointer" onclick="openModal('systemModal')">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-cyan-100 tw-text-cyan-600">
                        <i class="fas fa-server"></i>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">{{ __('payment.system') }}</p>
                        <p class="tw-mt-0.5 tw-text-xs tw-text-gray-600 tw-line-clamp-2">{{ __('payment.configure_auto_renewal_system') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Company Info -->
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200 tw-cursor-pointer" onclick="openModal('companyModal')">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-red-100 tw-text-red-600">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">{{ __('payment.company_info') }}</p>
                        <p class="tw-mt-0.5 tw-text-xs tw-text-gray-600 tw-line-clamp-2">{{ __('payment.manage_company_branding') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- eTIMS Integration -->
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200 tw-cursor-pointer" onclick="openModal('etimsModal')">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 {{ ($settings->etims_api_url ?? false) ? 'tw-bg-green-100 tw-text-green-600' : 'tw-bg-gray-100 tw-text-gray-400' }}">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">eTIMS Integration</p>
                        <p class="tw-mt-0.5 tw-text-xs tw-text-gray-600 tw-line-clamp-2">Configure KRA eTIMS API settings</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Management Modal -->
    <div class="modal fade" id="userManagementModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-users me-2"></i>
                        {{ __('payment.user_management') }}
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" onclick="$('#userManagementModal').modal('hide')">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">{{ __('payment.business') }}</th>
                                    <th>{{ __('payment.business_status') }}</th>
                                    <th>{{ __('payment.user') }}</th>
                                    <th>{{ __('payment.phone') }}</th>
                                    <th>{{ __('payment.user_status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($recentUsers ?? collect())->take($settings->recent_limit ?? 5) as $user)
                                    <tr data-has-phone="{{ !empty($user->phone) ? 'true' : 'false' }}">
                                        <td class="ps-3">
                                            <span class="fw-medium">{{ optional($user->business)->name ?? optional($user->business)->tax_number ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            @if($user->business)
                                            <form action="{{ route('admin.business.update-status', $user->business) }}" 
                                                  method="POST" class="ajax-form d-flex align-items-center">
                                                @csrf
                                                @method('PATCH')
                                                <select name="is_active" class="form-select form-select-sm me-2">
                                                    <option value="1" {{ $user->business->is_active ? 'selected' : '' }}>{{ __('payment.active') }}</option>
                                                    <option value="0" {{ !$user->business->is_active ? 'selected' : '' }}>{{ __('payment.inactive') }}</option>
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <span class="btn-text">{{ __('payment.apply') }}</span>
                                                    <span class="btn-loading d-none">
                                                        <span class="spinner-border spinner-border-sm" role="status"></span>
                                                    </span>
                                                </button>
                                            </form>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-medium">{{ $user->username ?? $user->name ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-medium">{{ $user->phone ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <form action="{{ route('admin.users.update-status', $user) }}" 
                                                  method="POST" class="ajax-form user-status-form d-flex align-items-center">
                                                @csrf
                                                @method('PATCH')
                                                <select name="status" class="form-select form-select-sm me-2">
                                                    <option value="active" {{ $user->status === 'active' ? 'selected' : '' }}>{{ __('payment.active') }}</option>
                                                    <option value="inactive" {{ $user->status === 'inactive' ? 'selected' : '' }}>{{ __('payment.inactive') }}</option>
                                                    <option value="terminated" {{ $user->status === 'terminated' ? 'selected' : '' }}>{{ __('payment.terminated') }}</option>
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    <span class="btn-text">{{ __('payment.apply') }}</span>
                                                    <span class="btn-loading d-none">
                                                        <span class="spinner-border spinner-border-sm" role="status"></span>
                                                    </span>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="fas fa-user-slash fa-2x mb-2 d-block"></i>
                                            {{ __('payment.no_users_found') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('admin.users') }}" class="btn btn-primary">
                        {{ __('payment.view_all') }} <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="$('#userManagementModal').modal('hide')">{{ __('payment.close') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Subscriptions Modal -->
    <div class="modal fade" id="subscriptionsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-receipt me-2"></i>
                        {{ __('payment.recent_subscriptions') }}
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" onclick="$('#subscriptionsModal').modal('hide')">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">{{ __('payment.user') }}</th>
                                    <th>{{ __('payment.business') }}</th>
                                    <th>{{ __('payment.plan') }}</th>
                                    <th>{{ __('payment.amount') }}</th>
                                    <th>{{ __('payment.status') }}</th>
                                    <th class="pe-3">{{ __('payment.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($recentSubscriptions ?? collect())->take($settings->recent_limit ?? 5) as $subscription)
                                    <tr>
                                        <td class="ps-3">
                                            <span class="fw-medium">{{ $subscription->user->username ?? ($subscription->user->name ?? 'N/A') }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-medium">{{ optional($subscription->user->business)->name ?? optional($subscription->user->business)->tax_number ?? 'N/A' }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-medium">{{ $subscription->plan_name }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-medium">Ksh {{ number_format($subscription->amount, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge 
                                                {{ $subscription->status === 'active' ? 'bg-success' : 
                                                   ($subscription->status === 'pending' ? 'bg-warning text-dark' : 
                                                   ($subscription->status === 'expired' ? 'bg-secondary' : 'bg-danger')) }} text-capitalize">
                                                {{ $subscription->status }}
                                            </span>
                                        </td>
                                        <td class="pe-3">
                                            <form action="{{ route('admin.subscriptions.update-status', $subscription) }}" 
                                                  method="POST" class="ajax-form d-flex align-items-center">
                                                @csrf
                                                @method('PATCH')
                                                <select name="status" class="form-select form-select-sm me-2">
                                                    <option value="active" {{ $subscription->status === 'active' ? 'selected' : '' }}>{{ __('payment.active') }}</option>
                                                    <option value="pending" {{ $subscription->status === 'pending' ? 'selected' : '' }}>{{ __('payment.pending') }}</option>
                                                    <option value="expired" {{ $subscription->status === 'expired' ? 'selected' : '' }}>{{ __('payment.expired') }}</option>
                                                    <option value="canceled" {{ $subscription->status === 'canceled' ? 'selected' : '' }}>{{ __('payment.canceled') }}</option>
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    <span class="btn-text">{{ __('payment.apply') }}</span>
                                                    <span class="btn-loading d-none">
                                                        <span class="spinner-border spinner-border-sm" role="status"></span>
                                                    </span>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="fas fa-file-invoice-dollar fa-2x mb-2 d-block"></i>
                                            {{ __('payment.no_subscriptions_found') }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('admin.subscriptions') }}" class="btn btn-success">
                        {{ __('payment.view_all') }} <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="$('#subscriptionsModal').modal('hide')">{{ __('payment.close') }}</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Registration Modal -->
    <div class="modal fade" id="registrationModal" tabindex="-1" aria-labelledby="registrationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="registrationModalLabel">
                        <i class="fas fa-user-plus me-2"></i>Registration Settings
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
              <form id="registrationForm" action="{{ route('admin.settings.update') }}" method="POST" class="settings-form" 
                          onsubmit="return handleModalFormSubmit(event, this, 'registrationModal');">
                        @csrf
                        <!-- Hidden fields for required settings not in this modal -->
                        <input type="hidden" name="grace_period_days" value="{{ $settings?->grace_period_days ?? 7 }}">
                        <input type="hidden" name="recent_limit" value="{{ $settings?->recent_limit ?? 5 }}">
                <div class="modal-body">
                                                <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-medium">{{ __('payment.monthly_price') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Ksh</span>
                                    <input type="number" step="0.01" class="form-control" name="monthly_price"
                                        value="{{ $settings?->monthly_price ?? 0 }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">{{ __('payment.quarterly_price') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Ksh</span>
                                    <input type="number" step="0.01" class="form-control" name="quarterly_price"
                                        value="{{ $settings?->quarterly_price ?? 0 }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">{{ __('payment.yearly_price') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Ksh</span>
                                    <input type="number" step="0.01" class="form-control" name="yearly_price"
                                        value="{{ $settings?->yearly_price ?? 0 }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">{{ __('payment.registration_price') }}</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Ksh</span>
                                    <input type="number" step="0.01" min="0" max="10000" class="form-control" name="registration_price"
                                        value="{{ $settings?->registration_price ?? 5 }}" aria-describedby="registrationPriceHelp">
                                </div>
                                <small id="registrationPriceHelp" class="text-muted">{{ __('payment.registration_price_help') }}</small>
                                <div class="mt-2">
                                    <button id="previewRegistrationEmailBtn" type="button" class="btn btn-sm btn-outline-secondary">{{ __('payment.preview_registration_email') }}</button>
                                </div>
                            </div>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary px-4">
                        <span class="btn-text">{{ __('payment.update_settings') }}</span>
                        <span class="btn-loading d-none">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
              </form>
            </div>
        </div>
    </div>

    <!-- Payroll Modal -->
    <div class="modal fade" id="payrollModal" tabindex="-1" aria-labelledby="payrollModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="payrollModalLabel">
                        <i class="fas fa-money-bill-wave me-2"></i>Payroll Settings
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
              <form action="{{ route('admin.settings.update') }}" method="POST" class="settings-form" 
                          onsubmit="return handleModalFormSubmit(event, this, 'payrollModal');">
                        @csrf
                        <!-- Hidden fields for required settings not in this modal -->
                        <input type="hidden" name="monthly_price" value="{{ $settings?->monthly_price ?? 0 }}">
                        <input type="hidden" name="quarterly_price" value="{{ $settings?->quarterly_price ?? 0 }}">
                        <input type="hidden" name="yearly_price" value="{{ $settings?->yearly_price ?? 0 }}">
                        <input type="hidden" name="grace_period_days" value="{{ $settings?->grace_period_days ?? 7 }}">
                        <input type="hidden" name="recent_limit" value="{{ $settings?->recent_limit ?? 5 }}">
                <div class="modal-body">
                        <h6 class="fw-semibold mb-3">{{ __('payment.payroll_defaults') }}</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label fw-medium">{{ __('payment.nssf_percent') }}</label>
                                <input type="number" step="0.00001" class="form-control" name="payroll_nssf_percent" value="{{ $settings?->payroll_nssf_percent ?? 0.0048 }}">
                                <small class="text-muted">{{ __('payment.nssf_example', ['example' => '0.0048']) }}</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-medium">{{ __('payment.shif_percent') }}</label>
                                <input type="number" step="0.00001" class="form-control" name="payroll_shif_percent" value="{{ $settings?->payroll_shif_percent ?? 0.0275 }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-medium">{{ __('payment.housing_percent') }}</label>
                                <input type="number" step="0.00001" class="form-control" name="payroll_housing_percent" value="{{ $settings?->payroll_housing_percent ?? 0.015 }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-medium">{{ __('payment.tax_percent') }}</label>
                                <input type="number" step="0.00001" class="form-control" name="payroll_tax_percent" value="{{ $settings?->payroll_tax_percent ?? 0.0245 }}">
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Personal relief (amount)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Ksh</span>
                                    <input type="number" step="0.01" class="form-control" name="payroll_personal_relief" value="{{ $settings?->payroll_personal_relief ?? 2400 }}">
                                </div>
                            </div>
                        </div>

                        <h6 class="fw-semibold mb-3">{{ __('payment.payroll_tax_bands') }}</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <div id="bandEditor" class="mb-2">
                                    <div class="card border-1 shadow-sm">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <div>
                                                    <div class="small text-muted">{{ __('payment.bands_description') }}</div>
                                                </div>
                                                <div class="btn-group">
                                                    <button type="button" id="addBand" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-plus me-1"></i> {{ __('payment.add_band') }}
                                                    </button>
                                                    <button type="button" id="loadExampleBands" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-list me-1"></i> {{ __('payment.load_example') }}
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="table-responsive">
                                                <table class="table table-sm table-borderless align-middle" id="bandsTable">
                                                    <thead>
                                                        <tr class="text-muted small">
                                                            <th style="width:55%">{{ __('payment.upper_column') }}</th>
                                                            <th style="width:30%">{{ __('payment.rate_column') }}</th>
                                                            <th style="width:15%" class="text-end">{{ __('payment.bands_table_actions') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                </table>
                                            </div>

                                            <div class="mt-2 d-flex justify-content-between align-items-center">
                                                <small class="text-muted">{{ __('payment.bands_tip') }}</small>
                                                <div id="bandsError" class="text-danger small" style="display:none;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="payroll_tax_bands" id="payroll_tax_bands" value="{{ $settings?->payroll_tax_bands ?? '' }}">
                            </div>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success px-4">
                        <span class="btn-text">{{ __('payment.update_settings') }}</span>
                        <span class="btn-loading d-none">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
              </form>
            </div>
        </div>
    </div>

    <!-- Invoicing Modal -->
    <div class="modal fade" id="invoicingModal" tabindex="-1" aria-labelledby="invoicingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="invoicingModalLabel">
                        <i class="fas fa-file-invoice me-2"></i>Invoicing Settings
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
              <form action="{{ route('admin.settings.update') }}" method="POST" class="settings-form" 
                          onsubmit="return handleModalFormSubmit(event, this, 'invoicingModal');">
                        @csrf
                        <!-- Hidden fields for required settings not in this modal -->
                        <input type="hidden" name="monthly_price" value="{{ $settings?->monthly_price ?? 0 }}">
                        <input type="hidden" name="quarterly_price" value="{{ $settings?->quarterly_price ?? 0 }}">
                        <input type="hidden" name="yearly_price" value="{{ $settings?->yearly_price ?? 0 }}">
                        <input type="hidden" name="grace_period_days" value="{{ $settings?->grace_period_days ?? 7 }}">
                        <input type="hidden" name="recent_limit" value="{{ $settings?->recent_limit ?? 5 }}">
                <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Subscription invoice prefix</label>
                                <input type="text" class="form-control" name="subscription_invoice_prefix" value="{{ $settings?->subscription_invoice_prefix ?? '' }}" placeholder="e.g. SUB">
                                <small class="text-muted">Optional prefix for subscription invoices.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Subscription invoice next</label>
                                <input type="number" class="form-control" name="subscription_invoice_next" value="{{ $settings?->subscription_invoice_next ?? 1 }}" min="0">
                                <small class="text-muted">Next numeric value for the subscription invoice sequence.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Subscription VAT (%)</label>
                                <input type="number" step="0.01" class="form-control" name="subscription_vat_percent" value="{{ $settings?->subscription_vat_percent ?? 0 }}" min="0" max="100">
                                <small class="text-muted">Optional VAT percentage to apply to subscription invoices (e.g. 16).</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Subscription round precision</label>
                                <input type="number" class="form-control" name="subscription_round_precision" value="{{ $settings?->subscription_round_precision ?? 0 }}" min="0" max="6">
                                <small class="text-muted">Number of decimal places to round invoice totals to (0 = whole number)</small>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label class="form-label fw-medium">Invoice footer / note</label>
                                <textarea class="form-control" name="invoice_footer" rows="3">{{ $settings?->invoice_footer ?? '' }}</textarea>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label class="form-label fw-medium">Statement footer / note</label>
                                <textarea class="form-control" name="statement_footer" rows="3">{{ $settings?->statement_footer ?? '' }}</textarea>
                                <small class="text-muted">This will appear at the bottom of account statements only.</small>
                            </div>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-warning px-4">
                        <span class="btn-text">{{ __('payment.update_settings') }}</span>
                        <span class="btn-loading d-none">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
              </form>
            </div>
        </div>
    </div>

    <!-- System Modal -->
    <div class="modal fade" id="systemModal" tabindex="-1" aria-labelledby="systemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="systemModalLabel">
                        <i class="fas fa-server me-2"></i>System Settings
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
              <form action="{{ route('admin.settings.update') }}" method="POST" class="settings-form" 
                          onsubmit="return handleModalFormSubmit(event, this, 'systemModal');">
                        @csrf
                        <!-- Hidden fields for required settings not in this modal -->
                        <input type="hidden" name="monthly_price" value="{{ $settings?->monthly_price ?? 0 }}">
                        <input type="hidden" name="quarterly_price" value="{{ $settings?->quarterly_price ?? 0 }}">
                        <input type="hidden" name="yearly_price" value="{{ $settings?->yearly_price ?? 0 }}">
                <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-3">
                                    <input class="form-check-input" type="checkbox" name="auto_renewal"
                                        id="auto_renewal" value="1" {{ ($settings?->auto_renewal ?? false) ? 'checked' : '' }}>
                                    <label for="auto_renewal" class="form-check-label fw-medium">{{ __('payment.enable_auto_renewal') }}</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">{{ __('payment.grace_period_days') }}</label>
                                <input type="number" class="form-control" name="grace_period_days"
                                    value="{{ $settings?->grace_period_days ?? 7 }}" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">{{ __('payment.recent_records_limit') }}</label>
                                <input type="number" min="1" max="100" class="form-control" name="recent_limit"
                                    value="{{ $settings?->recent_limit ?? 5 }}" required>
                                <small class="text-muted">{{ __('payment.controls_how_many_recent') }}</small>
                            </div>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-info px-4">
                        <span class="btn-text">{{ __('payment.update_settings') }}</span>
                        <span class="btn-loading d-none">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
              </form>
            </div>
        </div>
    </div>

    <!-- Company Info Modal -->
    <div class="modal fade" id="companyModal" tabindex="-1" aria-labelledby="companyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="companyModalLabel">
                        <i class="fas fa-building me-2"></i>Company Information
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
              <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" class="settings-form" 
                          onsubmit="return handleModalFormSubmit(event, this, 'companyModal');">
                        @csrf
                        <!-- Hidden fields for required settings not in this modal -->
                        <input type="hidden" name="monthly_price" value="{{ $settings?->monthly_price ?? 0 }}">
                        <input type="hidden" name="quarterly_price" value="{{ $settings?->quarterly_price ?? 0 }}">
                        <input type="hidden" name="yearly_price" value="{{ $settings?->yearly_price ?? 0 }}">
                        <input type="hidden" name="grace_period_days" value="{{ $settings?->grace_period_days ?? 7 }}">
                        <input type="hidden" name="recent_limit" value="{{ $settings?->recent_limit ?? 5 }}">
                <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Company name</label>
                                <input type="text" class="form-control" name="company_name" value="{{ $settings?->company_name ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Company logo</label>
                                <input type="file" class="form-control" name="company_logo" accept="image/*">
                                @if(!empty($settings?->company_logo))
                                    <div class="mt-2 small text-muted">Current: <a href="{{ asset($settings->company_logo) }}" target="_blank">View</a></div>
                                @endif
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Contact phone</label>
                                <input type="text" class="form-control" name="company_contact_phone" value="{{ $settings?->company_contact_phone ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Contact email</label>
                                <input type="email" class="form-control" name="company_contact_email" value="{{ $settings?->company_contact_email ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Invoice PIN</label>
                                <input type="text" class="form-control" name="invoice_pin" value="{{ $settings?->invoice_pin ?? '' }}">
                            </div>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-danger px-4">
                        <span class="btn-text">{{ __('payment.update_settings') }}</span>
                        <span class="btn-loading d-none">
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </span>
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
              </form>
            </div>
        </div>
    </div>

    <!-- eTIMS Integration Modal -->
    <div class="modal fade" id="etimsModal" tabindex="-1" aria-labelledby="etimsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="etimsModalLabel">
                        <i class="fas fa-exchange-alt me-2"></i>eTIMS Integration Settings
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.settings.update') }}" method="POST" class="settings-form" 
                      onsubmit="return handleModalFormSubmit(event, this, 'etimsModal');">
                    @csrf
                    <!-- Hidden fields for required settings not in this modal -->
                    <input type="hidden" name="monthly_price" value="{{ $settings?->monthly_price ?? 0 }}">
                    <input type="hidden" name="quarterly_price" value="{{ $settings?->quarterly_price ?? 0 }}">
                    <input type="hidden" name="yearly_price" value="{{ $settings?->yearly_price ?? 0 }}">
                    <input type="hidden" name="grace_period_days" value="{{ $settings?->grace_period_days ?? 7 }}">
                    <input type="hidden" name="recent_limit" value="{{ $settings?->recent_limit ?? 5 }}">
                    
                    <div class="modal-body">
                        <div class="alert alert-info border-0">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Configure eTIMS API Integration</strong>
                            <br><small class="text-dark">Set up automatic transmission of sales invoices to KRA when transactions are posted. Each item in an invoice can have different tax rates (0% Exempt or 16% VAT), and the system automatically assigns the correct tax code per item.</small>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label class="form-label fw-medium">eTIMS API URL</label>
                                <input type="url" class="form-control" name="etims_api_url" 
                                       value="{{ $settings?->etims_api_url ?? '' }}" 
                                       placeholder="https://your-etims-api-url.com">
                                <small class="text-muted">The base URL for your eTIMS API endpoint</small>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label class="form-label fw-medium">API Token</label>
                                <textarea class="form-control" name="etims_api_token" rows="3" 
                                          placeholder="Enter your eTIMS API authentication token">{{ $settings?->etims_api_token ?? '' }}</textarea>
                                <small class="text-muted">Authentication token for API access</small>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Branch ID</label>
                                <input type="text" class="form-control" name="etims_branch_id" 
                                       value="{{ $settings?->etims_branch_id ?? '' }}" 
                                       placeholder="e.g., 02" maxlength="10">
                                <small class="text-muted">Your eTIMS branch identifier</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Auto Transmit (Products)</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="etims_auto_transmit" 
                                           id="etimsAutoTransmit" value="1"
                                           {{ ($settings?->etims_auto_transmit ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="etimsAutoTransmit">
                                        Automatically transmit product sales
                                    </label>
                                </div>
                                <small class="text-muted">Enable to send product invoices automatically when sales are posted</small>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Transmit Subscriptions</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="etims_transmit_subscriptions" 
                                           id="etimsTransmitSubscriptions" value="1"
                                           {{ ($settings?->etims_transmit_subscriptions ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="etimsTransmitSubscriptions">
                                        Include subscription invoices
                                    </label>
                                </div>
                                <small class="text-muted">Enable to also transmit subscription payments to eTIMS</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Transmit Registrations</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="etims_transmit_registrations" 
                                           id="etimsTransmitRegistrations" value="1"
                                           {{ ($settings?->etims_transmit_registrations ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="etimsTransmitRegistrations">
                                        Include registration payments
                                    </label>
                                </div>
                                <small class="text-muted">Enable to also transmit registration fees to eTIMS</small>
                            </div>
                        </div>

                        <div class="alert alert-light border">
                            <div class="text-dark">
                                <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
                                <strong>Tax Code Information:</strong>
                            </div>
                            <ul class="mb-0 mt-2 small">
                                <li><strong>Code A</strong> = Exempt (0%) - Applied to items with 0% tax or no tax set</li>
                                <li><strong>Code B</strong> = VAT Standard Rate (16%) - Applied to items with 16% tax</li>
                                <li><strong>Code E</strong> = Special Rate - Applied to items with other tax rates</li>
                            </ul>
                            <small class="d-block mt-2">Each item in the invoice gets its own tax code based on its tax rate. An invoice can contain items with different tax codes.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success px-4">
                            <span class="btn-text">
                                <i class="fas fa-save me-2"></i>Save eTIMS Settings
                            </span>
                            <span class="btn-loading d-none">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </span>
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Subscription Enforcement Modal -->
    <div class="modal fade" id="subscriptionEnforcementModal" tabindex="-1" aria-labelledby="subscriptionEnforcementModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-header {{ ($settings->subscription_required ?? false) ? 'bg-success' : 'bg-secondary' }} text-white">
                    <h5 class="modal-title" id="subscriptionEnforcementModalLabel">
                        <i class="fas fa-shield-alt me-2"></i>Subscription Enforcement
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="text-center py-4">
                        <div class="mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; border-radius: 50%; background-color: {{ ($settings->subscription_required ?? false) ? '#d1fae5' : '#e5e7eb' }};">
                                <i class="fas fa-shield-alt" style="font-size: 36px; color: {{ ($settings->subscription_required ?? false) ? '#059669' : '#6b7280' }};"></i>
                            </div>
                        </div>
                        
                        <h4 class="mb-3">Current Status: 
                            <span class="badge {{ ($settings->subscription_required ?? false) ? 'bg-success' : 'bg-secondary' }}">
                                {{ ($settings->subscription_required ?? false) ? 'ENABLED' : 'DISABLED' }}
                            </span>
                        </h4>
                        
                        <p class="text-muted mb-4">
                            @if($settings->subscription_required ?? false)
                                <i class="fas fa-check-circle text-success me-1"></i>
                                Users must have an active subscription to use the system.
                            @else
                                <i class="fas fa-info-circle text-secondary me-1"></i>
                                Users can use the system without a subscription.
                            @endif
                        </p>

                        <div class="alert {{ ($settings->subscription_required ?? false) ? 'alert-warning' : 'alert-info' }}">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            @if($settings->subscription_required ?? false)
                                Disabling this will allow all users to access the system regardless of subscription status.
                            @else
                                Enabling this will require all users to have an active subscription to use the system.
                            @endif
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <form action="{{ route('admin.settings.toggle-subscription-requirement') }}" method="POST" id="subscription-toggle-form" class="d-inline">
                        @csrf
                        <button type="submit" class="btn {{ ($settings->subscription_required ?? false) ? 'btn-danger' : 'btn-success' }} px-4">
                            <span class="btn-text">
                                <i class="fas fa-{{ ($settings->subscription_required ?? false) ? 'times' : 'check' }} me-1"></i>
                                {{ ($settings->subscription_required ?? false) ? 'Disable' : 'Enable' }} Enforcement
                            </span>
                            <span class="btn-loading d-none">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </span>
                        </button>
                    </form>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- M-Pesa Credentials Modal -->
    <div class="modal fade" id="mpesaCredentialsModal" tabindex="-1" aria-labelledby="mpesaCredentialsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="mpesaCredentialsModalLabel">
                        <i class="fas fa-mobile-alt me-2"></i>Subscription M-Pesa Credentials
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.settings.update-subscription-mpesa') }}" method="POST" id="mpesa-credentials-form">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Configure separate M-Pesa credentials for subscription payments. Leave all fields blank to use default (.env) credentials.
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="subscription_mpesa_consumer_key" class="form-label fw-medium">Consumer Key</label>
                                <input type="text" class="form-control" id="subscription_mpesa_consumer_key" 
                                       name="subscription_mpesa_consumer_key" 
                                       value="{{ $settings->subscription_mpesa_consumer_key ?? '' }}"
                                       placeholder="Enter M-Pesa Consumer Key">
                            </div>
                            <div class="col-md-6">
                                <label for="subscription_mpesa_consumer_secret" class="form-label fw-medium">Consumer Secret</label>
                                <input type="password" class="form-control" id="subscription_mpesa_consumer_secret" 
                                       name="subscription_mpesa_consumer_secret" 
                                       value="{{ $settings->subscription_mpesa_consumer_secret ?? '' }}"
                                       placeholder="Enter M-Pesa Consumer Secret">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label for="subscription_mpesa_shortcode" class="form-label fw-medium">Shortcode</label>
                                <input type="text" class="form-control" id="subscription_mpesa_shortcode" 
                                       name="subscription_mpesa_shortcode" 
                                       value="{{ $settings->subscription_mpesa_shortcode ?? '' }}"
                                       placeholder="e.g., 174379">
                            </div>
                            <div class="col-md-4">
                                <label for="subscription_mpesa_passkey" class="form-label fw-medium">Passkey</label>
                                <input type="password" class="form-control" id="subscription_mpesa_passkey" 
                                       name="subscription_mpesa_passkey" 
                                       value="{{ $settings->subscription_mpesa_passkey ?? '' }}"
                                       placeholder="Enter M-Pesa Passkey">
                            </div>
                            <div class="col-md-4">
                                <label for="subscription_mpesa_callback" class="form-label fw-medium">Callback URL</label>
                                <input type="url" class="form-control" id="subscription_mpesa_callback" 
                                       name="subscription_mpesa_callback" 
                                       value="{{ $settings->subscription_mpesa_callback ?? '' }}"
                                       placeholder="https://yourdomain.com/mpesa/callback">
                            </div>
                        </div>

                        <div class="d-flex justify-content-start">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="toggle-password-visibility">
                                <i class="fas fa-eye"></i> Show Credentials
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary px-4">
                            <span class="btn-text">
                                <i class="fas fa-save me-1"></i>Save M-Pesa Credentials
                            </span>
                            <span class="btn-loading d-none">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </span>
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Manual Subscription Modal -->
    <div class="modal fade" id="manualSubscriptionModal" tabindex="-1" aria-labelledby="manualSubscriptionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="manualSubscriptionModalLabel">
                        <i class="fas fa-hand-holding-usd me-2"></i>{{ __('payment.manual_subscription_management') }}
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.subscriptions.manual') }}" method="POST" class="ajax-form" id="manual-subscription-form">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <span class="fw-semibold">Quick Creation:</span> Manually create subscriptions for users without M-Pesa payment. The system will use default pricing unless you specify a custom amount.
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">
                                    <i class="fas fa-user me-1 text-primary"></i>
                                    {{ __('payment.select_user') }} <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="user_id" required>
                                    <option value="">{{ __('payment.select_user') }}</option>
                                    @foreach($users ?? [] as $user)
                                    <option value="{{ $user->id }}">
                                        {{ optional($user->business)->name ?? $user->name }} ({{ $user->email }})
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">
                                    <i class="fas fa-calendar-alt me-1 text-success"></i>
                                    {{ __('payment.billing_cycle') }} <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="billing_cycle" required>
                                    <option value="monthly">{{ __('payment.monthly') }} - Ksh {{ number_format($settings->monthly_price ?? 0, 2) }}</option>
                                    <option value="quarterly">{{ __('payment.quarterly') }} - Ksh {{ number_format($settings->quarterly_price ?? 0, 2) }}</option>
                                    <option value="yearly">{{ __('payment.yearly') }} - Ksh {{ number_format($settings->yearly_price ?? 0, 2) }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-medium">
                                    <i class="fas fa-money-bill-wave me-1 text-warning"></i>
                                    {{ __('payment.custom_amount') }} (Ksh)
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light fw-semibold">Ksh</span>
                                    <input type="number" step="0.01" class="form-control" name="custom_amount" placeholder="Optional">
                                </div>
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-lightbulb me-1"></i>
                                    {{ __('payment.leave_empty_default') }}
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success px-4">
                            <span class="btn-text">
                                <i class="fas fa-plus-circle me-1"></i>
                                {{ __('payment.create_manual_subscription') }}
                            </span>
                            <span class="btn-loading d-none">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </span>
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}">
    <style>
        /* Fix dashboard layout and prevent horizontal scrollbar */
        body {
            overflow-x: hidden !important;
        }
        
        .content-wrapper {
            overflow-x: hidden !important;
        }
        
        #scrollable-container {
            overflow-x: hidden !important;
        }
        
        .dashboard-wrapper {
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box;
        }
        
        /* Fix Tailwind grid to prevent overflow */
        .tw-grid {
            width: 100%;
            max-width: 100%;
        }
        
        /* Ensure all rows don't overflow */
        .dashboard-wrapper .row {
            margin-left: -0.75rem;
            margin-right: -0.75rem;
            max-width: 100%;
        }
        
        .dashboard-wrapper [class*="col-"] {
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }
        
        /* Fix cards to prevent overflow */
        .card {
            max-width: 100%;
            overflow: hidden;
        }
        
        /* Fix table responsive containers */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            max-width: 100%;
        }
        
        /* Prevent form elements from causing overflow */
        .form-select,
        .form-control,
        input,
        select {
            max-width: 100%;
        }
        
        .hover-lift {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }
        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
        }
        
        /* Custom column for 5 cards in a row on XL screens */
        @media (min-width: 1200px) {
            .col-xl-2-4 {
                flex: 0 0 auto;
                width: 20%; /* 100% / 5 = 20% */
            }
        }
        
        /* Ensure cards are centered when they wrap */
        .justify-content-center > [class*="col-"] {
            display: flex;
            justify-content: center;
        }
        
        /* Gradient headers */
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .bg-gradient-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
        }
        .bg-gradient-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        
        /* Section spacing */
        .row.mt-4:first-of-type {
            margin-top: 1.5rem !important;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .dashboard-wrapper {
                padding: 1rem !important;
            }
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Small set of translations used in runtime JS. Keep minimal to avoid large inlined objects.
        const DASHBOARD_I18N = {!! json_encode([
            'remove' => __('payment.remove'),
            'missing_phone_number' => __('payment.missing_phone_number'),
            'missing_phone_body' => __('payment.missing_phone_body'),
            'registration_email_preview' => __('payment.registration_email_preview'),
            'unable_load_preview' => __('payment.unable_load_preview'),
            'ok' => __('payment.ok'),
            'bands_invalid_json' => __('payment.bands_invalid_json'),
        ]) !!};
        
        /**
         * Open modal using jQuery (since Bootstrap may not be loaded)
         */
        function openModal(modalId) {
            $('#' + modalId).modal('show');
            
            // Initialize bands editor when payroll modal opens
            if (modalId === 'payrollModal') {
                setTimeout(function() {
                    const bandsTable = document.getElementById('bandsTable');
                    if (bandsTable && !window.bandsEditorInitialized) {
                        initializeBandsEditor();
                        window.bandsEditorInitialized = true;
                    }
                }, 100);
            }
        }
        
        /**
         * Handle modal form submissions
         */
        function handleModalFormSubmit(event, form, modalId) {
            event.preventDefault();
            console.log('🚀 Modal form submitted:', modalId);
            
            const formAction = '{{ route('admin.settings.update') }}';
            const formData = new FormData(form);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoading = submitBtn.querySelector('.btn-loading');
            
            if (btnText && btnLoading) {
                btnText.classList.add('d-none');
                btnLoading.classList.remove('d-none');
            }
            submitBtn.disabled = true;
            
            // Trigger bands collection before submit if in payroll modal
            if (modalId === 'payrollModal') {
                try {
                    if (window.collectBands && typeof window.collectBands === 'function') {
                        const bands = window.collectBands();
                        const hiddenField = form.querySelector('#payroll_tax_bands');
                        if (hiddenField) {
                            hiddenField.value = JSON.stringify(bands);
                        }
                    }
                } catch (err) {
                    console.error('Error collecting bands:', err);
                    if (typeof showToast === 'function') {
                        showToast('error', err.message || 'Invalid tax bands');
                    }
                    if (btnText && btnLoading) {
                        btnText.classList.remove('d-none');
                        btnLoading.classList.add('d-none');
                    }
                    submitBtn.disabled = false;
                    return false;
                }
            }
            
            fetch(formAction, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('✅ Settings response:', data);
                if (data.success) {
                    if (typeof showToast === 'function') {
                        showToast('success', data.message || 'Settings updated successfully!');
                    } else {
                        alert(data.message || 'Settings updated successfully!');
                    }
                    
                    // Close modal using jQuery
                    $('#' + modalId).modal('hide');
                    
                    // Play success audio
                    try {
                        const successAudio = document.getElementById('success-audio');
                        if (successAudio) {
                            successAudio.play().catch(error => {
                                console.log('🔇 Audio playback may be blocked by browser policy');
                            });
                        }
                    } catch (error) {
                        console.log('🔇 Audio not available');
                    }
                } else {
                    if (typeof showToast === 'function') {
                        showToast('error', data.message || 'Failed to update settings');
                    } else {
                        alert(data.message || 'Failed to update settings');
                    }
                }
            })
            .catch(error => {
                console.error('❌ Error:', error);
                if (typeof showToast === 'function') {
                    showToast('error', 'An error occurred while updating settings');
                } else {
                    alert('An error occurred while updating settings');
                }
            })
            .finally(() => {
                if (btnText && btnLoading) {
                    btnText.classList.remove('d-none');
                    btnLoading.classList.add('d-none');
                }
                submitBtn.disabled = false;
            });
            
            return false;
        }

        /**
         * Initialize the payroll tax bands editor
         */
        function initializeBandsEditor() {
            function q(sel){ return document.querySelector(sel); }
            var tableBody = q('#bandsTable tbody');
            var addBtn = q('#addBand');
            var loadExampleBtn = q('#loadExampleBands');
            var hidden = q('#payroll_tax_bands');
            var errorDiv = q('#bandsError');
            
            if (!tableBody || !addBtn || !hidden) {
                console.log('Bands editor elements not found, skipping initialization');
                return;
            }

            function makeRow(upper, rate){
                var tr = document.createElement('tr');
                var u = document.createElement('td');
                var ui = document.createElement('input'); ui.type='number'; ui.step='0.01'; ui.className='form-control form-control-sm';
                if (upper !== null && upper !== undefined && upper !== '') ui.value = upper;
                ui.placeholder = 'Leave empty for last band';
                u.appendChild(ui);

                var r = document.createElement('td');
                var ri = document.createElement('input'); ri.type='number'; ri.step='0.0001'; ri.className='form-control form-control-sm';
                if (rate !== null && rate !== undefined) ri.value = rate;
                ri.placeholder = 'e.g. 0.1';
                r.appendChild(ri);

                var a = document.createElement('td'); a.className='text-end';
                var rem = document.createElement('button'); rem.type='button'; rem.className='btn btn-sm btn-outline-danger'; 
                rem.innerHTML = '<i class="fas fa-trash"></i>';
                rem.addEventListener('click', function(){ tr.remove(); });
                a.appendChild(rem);

                tr.appendChild(u); tr.appendChild(r); tr.appendChild(a);
                tableBody.appendChild(tr);
                return tr;
            }

            function loadInitial(){
                tableBody.innerHTML = '';
                var raw = hidden.value || '';
                if (!raw.trim()){ 
                    // add default empty row
                    makeRow('', ''); 
                    return;
                }
                try {
                    var arr = JSON.parse(raw);
                    if (!Array.isArray(arr)) throw new Error('Not array');
                    if (arr.length === 0) {
                        makeRow('', '');
                    } else {
                        arr.forEach(function(b){ 
                            makeRow(b.upper === null ? '' : b.upper, b.rate); 
                        });
                    }
                } catch(e){
                    // fallback: show one empty row and display error
                    makeRow('', '');
                    if (errorDiv) {
                        errorDiv.style.display = 'block';
                        errorDiv.innerText = DASHBOARD_I18N.bands_invalid_json || 'Saved bands JSON is invalid';
                    }
                }
            }

            addBtn.addEventListener('click', function(){ makeRow('', ''); });
            
            if (loadExampleBtn) {
                loadExampleBtn.addEventListener('click', function(){
                    tableBody.innerHTML = '';
                    // Kenya 2024 tax bands example
                    makeRow('24000', '0.1');
                    makeRow('32333', '0.25');
                    makeRow('500000', '0.3');
                    makeRow('800000', '0.325');
                    makeRow('', '0.35'); // Last band has no upper limit
                });
            }

            window.collectBands = function(){
                var bands = [];
                var rows = tableBody.querySelectorAll('tr');
                for(var i=0;i<rows.length;i++){
                    var up = rows[i].querySelector('td:nth-child(1) input').value;
                    var rt = rows[i].querySelector('td:nth-child(2) input').value;
                    
                    // Skip empty rows
                    if ((up === undefined || up === null || up === '') && 
                        (rt === undefined || rt === null || rt === '')) {
                        continue;
                    }
                    
                    var upper = (up === undefined || up === null || up === '') ? null : parseFloat(up);
                    var rate = (rt === undefined || rt === null || rt === '') ? NaN : parseFloat(rt);
                    
                    if (isNaN(rate)) { 
                        throw new Error('Rate must be a number on row '+(i+1)); 
                    }
                    bands.push({ upper: upper, rate: rate });
                }
                return bands;
            };

            loadInitial();
        }

        document.addEventListener("DOMContentLoaded", function () {

            // Add phone number validation before activation
            document.querySelectorAll('.user-status-form select[name="status"]').forEach(select => {
                // Store initial value
                select.setAttribute('data-previous', select.value);
                
                select.addEventListener('change', function() {
                    if (this.value === 'active') {
                        const row = this.closest('tr');
                        const hasPhone = row.dataset.hasPhone === 'true';
                        const phoneCell = row.querySelector('td:nth-child(4)'); // 4th column is phone
                        
                        if (!hasPhone) {
                            Swal.fire({
                                icon: 'warning',
                                title: DASHBOARD_I18N.missing_phone_number,
                                html: DASHBOARD_I18N.missing_phone_body.replace(':phone', phoneCell.textContent.trim()),
                                confirmButtonText: DASHBOARD_I18N.ok || 'OK'
                            });
                            
                            // Reset to previous value
                            const previousStatus = this.getAttribute('data-previous') || 'inactive';
                            this.value = previousStatus;
                        } else {
                            // Store current value as previous for next time
                            this.setAttribute('data-previous', this.value);
                        }
                    } else {
                        // Store current value as previous for next time
                        this.setAttribute('data-previous', this.value);
                    }
                });
            });
            
            // Initialize tax bands table if it exists
            const bandsTable = document.getElementById('bandsTable');
            if (bandsTable) {
                initializeBandsEditor();
            }

            // AJAX forms are handled centrally in /public/js/ajax-forms.js
            console.log('Using centralized AJAX form handler for .ajax-form');
            
            // Manual payment check handler
            if (document.getElementById('manualCheckStatus')) {
                document.getElementById('manualCheckStatus').addEventListener('click', async function() {
                    const responseDiv = document.getElementById('stkResponse') || document.createElement('div');
                    responseDiv.innerHTML = '<div class="alert alert-info">{{ __("payment.checking_payment") }}</div>';
                    
                    try {
                        const checkoutRequestId = '{{ session('checkout_request_id') }}';
                        const res = await fetch("{{ route('subscription.manualStatusCheck') }}", {
                            method: 'POST',
                            headers: { 
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ 
                                checkout_request_id: checkoutRequestId
                            })
                        });
                        
                        if (!res.ok) {
                            throw new Error(`HTTP error! Status: ${res.status}`);
                        }
                        
                        const data = await res.json();
                        
                        if (data.transaction_status === 'success') {
                            responseDiv.innerHTML = '<div class="alert alert-success">{{ __("payment.payment_confirmed_reload") }}</div>';
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            responseDiv.innerHTML = `<div class="alert alert-warning">{{ __("payment.payment_status") }}: ${data.transaction_status}</div>`;
                        }
                    } catch(err) {
                        console.error('Payment check error:', err);
                        responseDiv.innerHTML = '<div class="alert alert-danger">{{ __("payment.payment_status_unknown") }}</div>';
                    }
                });
            }
        });
        
        /**
         * Get appropriate badge class for status
         */
        function getStatusBadgeClass(status) {
            switch(status) {
                case 'active': return 'bg-success';
                case 'pending': return 'bg-warning text-dark';
                case 'expired': return 'bg-secondary';
                case 'canceled': return 'bg-danger';
                default: return 'bg-secondary';
            }
        }

            // Preview registration email handler
            const previewBtn = document.getElementById('previewRegistrationEmailBtn');
            if (previewBtn) {
                previewBtn.addEventListener('click', async function () {
                    try {
                        // Show loading state on button
                        const originalText = previewBtn.innerHTML;
                        previewBtn.disabled = true;
                        previewBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Loading...';

                        const res = await fetch('{{ route('admin.settings.previewRegistrationEmail') }}', {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        
                        // Reset button state
                        previewBtn.disabled = false;
                        previewBtn.innerHTML = originalText;
                        
                        if (!res.ok) throw new Error('Failed to load preview');
                        const html = await res.text();

                        // Show modal with preview
                        const modalId = 'previewEmailModal_' + Date.now();
                        const modalDiv = document.createElement('div');
                        modalDiv.className = 'modal fade';
                        modalDiv.id = modalId;
                        modalDiv.setAttribute('tabindex', '-1');
                        modalDiv.setAttribute('role', 'dialog');
                        modalDiv.innerHTML = `
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">${DASHBOARD_I18N.registration_email_preview || 'Registration Email Preview'}</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">${html}</div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>`;

                        document.body.appendChild(modalDiv);
                        
                        // Initialize Bootstrap modal and show it
                        $('#' + modalId).modal('show');
                        
                        // Remove modal from DOM when hidden
                        $('#' + modalId).on('hidden.bs.modal', function () {
                            $(this).remove();
                        });
                    } catch (err) {
                        console.error(err);
                        // Reset button state on error
                        previewBtn.disabled = false;
                        previewBtn.innerHTML = originalText;
                        showToast('error', DASHBOARD_I18N.unable_load_preview || 'Unable to load preview');
                    }
                });
            }
        
        /**
         * Set loading state for form elements
         */
        function setFormLoadingState(form, isLoading) {
            // Toggle form overlay
            const overlay = form.querySelector('.form-disabled-overlay');
            if (overlay) {
                overlay.style.display = isLoading ? 'block' : 'none';
            }
            
            // Toggle form class
            if (isLoading) {
                form.classList.add('loading');
            } else {
                form.classList.remove('loading');
            }
            
            // Toggle buttons state
            const buttons = form.querySelectorAll('button');
            buttons.forEach(button => {
                button.disabled = isLoading;
                
                const btnText = button.querySelector('.btn-text');
                const btnLoading = button.querySelector('.btn-loading');
                
                if (btnText && btnLoading) {
                    if (isLoading) {
                        btnText.classList.add('d-none');
                        btnLoading.classList.remove('d-none');
                    } else {
                        btnText.classList.remove('d-none');
                        btnLoading.classList.add('d-none');
                    }
                }
            });
            
            // Toggle inputs state
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.disabled = isLoading;
            });
        }
        
        /**
         * Show toast notification
         */
        function showToast(icon, title) {
            try {
                // Try using SweetAlert2 first
                if (typeof Swal !== 'undefined') {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 4000,
                        timerProgressBar: true,
                        customClass: {
                            popup: 'swal2-toast-custom'
                        },
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer);
                            toast.addEventListener('mouseleave', Swal.resumeTimer);
                        }
                    });
                    
                    Toast.fire({
                        icon: icon,
                        title: title,
                        background: icon === 'success' ? '#10b981' : '#ef4444',
                        color: '#ffffff',
                        iconColor: '#ffffff'
                    });
                } else {
                    // Fallback to native toast
                    showNativeToast(icon, title);
                }
            } catch (error) {
                console.error('Toast error:', error);
                // Fallback to native toast
                showNativeToast(icon, title);
            }
        }
        
        /**
         * Native toast fallback
         */
        function showNativeToast(type, message) {
            // Remove any existing toasts
            const existingToasts = document.querySelectorAll('.native-toast');
            existingToasts.forEach(toast => toast.remove());
            
            const toast = document.createElement('div');
            toast.className = `native-toast native-toast-${type}`;
            toast.innerHTML = `
                <div class="native-toast-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                    <span>${message}</span>
                    <button type="button" class="native-toast-close" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            // Show with animation
            setTimeout(() => toast.classList.add('show'), 10);
            
            // Auto-remove after 4 seconds
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        /**
         * Handle subscription toggle with confirmation
         */
        (function() {
            const subscriptionToggleForm = document.getElementById('subscription-toggle-form');
            
            if (subscriptionToggleForm) {
                // Use capture phase to intercept before any other handlers
                subscriptionToggleForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    
                    // Check current state dynamically from the button
                    const submitBtn = subscriptionToggleForm.querySelector('button[type="submit"]');
                    const isCurrentlyEnabled = submitBtn.classList.contains('btn-danger');
                    
                    // If trying to disable (button is red/danger), show confirmation
                    if (isCurrentlyEnabled) {
                        showConfirmationModal(
                            'Disable Subscription Requirement?',
                            'Are you sure you want to disable subscription enforcement? Users will be able to use the system without an active subscription.',
                            function() {
                                submitToggle();
                            }
                        );
                    } else {
                        // Enabling, no confirmation needed
                        submitToggle();
                    }
                    
                    function submitToggle() {
                        const formData = new FormData(subscriptionToggleForm);
                        const submitBtn = subscriptionToggleForm.querySelector('button[type="submit"]');
                        const btnText = submitBtn.querySelector('.btn-text');
                        const btnLoading = submitBtn.querySelector('.btn-loading');
                        
                        // Show loading state
                        submitBtn.disabled = true;
                        if (btnText) btnText.classList.add('d-none');
                        if (btnLoading) btnLoading.classList.remove('d-none');
                        
                        fetch(subscriptionToggleForm.action, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Update UI immediately
                                updateSubscriptionToggleUI(data.subscription_required);
                                
                                // Show success message
                                showToast('success', data.message);
                            } else {
                                throw new Error(data.message || 'Failed to toggle subscription requirement');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showToast('error', error.message || 'An error occurred');
                            
                            // Reset button state
                            submitBtn.disabled = false;
                            if (btnText) btnText.classList.remove('d-none');
                            if (btnLoading) btnLoading.classList.add('d-none');
                        });
                    }
                });
            }
            
            /**
             * Update the subscription toggle UI without page reload
             */
            function updateSubscriptionToggleUI(isEnabled) {
                // Close the modal
                $('#subscriptionEnforcementModal').modal('hide');
                
                // Update the card's icon and text
                const card = document.querySelector('[onclick="openModal(\'subscriptionEnforcementModal\')"]');
                if (card) {
                    const iconCircle = card.querySelector('.tw-inline-flex');
                    const statusText = card.querySelectorAll('p')[1]; // Second p tag has the status
                    
                    // Update icon circle colors
                    if (isEnabled) {
                        iconCircle.className = 'tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-green-100 tw-text-green-600';
                        statusText.innerHTML = 'Enabled: Users must have an active subscription';
                    } else {
                        iconCircle.className = 'tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-gray-100 tw-text-gray-400';
                        statusText.innerHTML = 'Disabled: Users can use the system without subscription';
                    }
                }
                
                // Update modal elements for next time it opens
                const modal = document.getElementById('subscriptionEnforcementModal');
                if (modal) {
                    const modalHeader = modal.querySelector('.modal-header');
                    const badge = modal.querySelector('.badge');
                    const iconContainer = modal.querySelector('[style*="border-radius: 50%"]');
                    const icon = iconContainer?.querySelector('i');
                    const statusMessage = modal.querySelector('.text-muted');
                    const alert = modal.querySelector('.alert');
                    const submitBtn = modal.querySelector('button[type="submit"]');
                    const btnText = submitBtn?.querySelector('.btn-text');
                    const btnLoading = submitBtn?.querySelector('.btn-loading');
                    
                    // Update modal header color
                    if (isEnabled) {
                        modalHeader.className = 'modal-header bg-success text-white';
                    } else {
                        modalHeader.className = 'modal-header bg-secondary text-white';
                    }
                    
                    // Update badge
                    if (badge) {
                        badge.className = isEnabled ? 'badge bg-success' : 'badge bg-secondary';
                        badge.textContent = isEnabled ? 'ENABLED' : 'DISABLED';
                    }
                    
                    // Update icon container
                    if (iconContainer) {
                        iconContainer.style.backgroundColor = isEnabled ? '#d1fae5' : '#e5e7eb';
                    }
                    if (icon) {
                        icon.style.color = isEnabled ? '#059669' : '#6b7280';
                    }
                    
                    // Update status message
                    if (statusMessage) {
                        statusMessage.innerHTML = isEnabled 
                            ? '<i class="fas fa-check-circle text-success me-1"></i> Users must have an active subscription to use the system.'
                            : '<i class="fas fa-info-circle text-secondary me-1"></i> Users can use the system without a subscription.';
                    }
                    
                    // Update alert
                    if (alert) {
                        alert.className = isEnabled ? 'alert alert-warning' : 'alert alert-info';
                        alert.innerHTML = isEnabled
                            ? '<i class="fas fa-exclamation-triangle me-2"></i> Disabling this will allow all users to access the system regardless of subscription status.'
                            : '<i class="fas fa-exclamation-triangle me-2"></i> Enabling this will require all users to have an active subscription to use the system.';
                    }
                    
                    // Update button
                    if (submitBtn) {
                        submitBtn.className = isEnabled ? 'btn btn-danger px-4' : 'btn btn-success px-4';
                    }
                    if (btnText) {
                        btnText.innerHTML = isEnabled 
                            ? '<i class="fas fa-times me-1"></i>Disable Enforcement'
                            : '<i class="fas fa-check me-1"></i>Enable Enforcement';
                    }
                    
                    // Reset button state
                    if (submitBtn) submitBtn.disabled = false;
                    if (btnText) btnText.classList.remove('d-none');
                    if (btnLoading) btnLoading.classList.add('d-none');
                }
            }
        })();

        /**
         * Show a custom confirmation modal
         */
        function showConfirmationModal(title, message, onConfirm) {
            // Create a toast-style confirmation overlay
            const overlay = document.createElement('div');
            overlay.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;';
            
            const toast = document.createElement('div');
            toast.style.cssText = 'background: white; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); max-width: 450px; width: 90%; animation: slideDown 0.3s ease-out;';
            toast.innerHTML = `
                <style>
                    @keyframes slideDown {
                        from { transform: translateY(-20px); opacity: 0; }
                        to { transform: translateY(0); opacity: 1; }
                    }
                </style>
                <div style="padding: 24px; border-bottom: 1px solid #e5e7eb;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 48px; height: 48px; border-radius: 50%; background: #fef3c7; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-exclamation-triangle" style="color: #f59e0b; font-size: 24px;"></i>
                        </div>
                        <div>
                            <h5 style="margin: 0; font-size: 18px; font-weight: 600; color: #111827;">${title}</h5>
                        </div>
                    </div>
                </div>
                <div style="padding: 24px;">
                    <p style="margin: 0; color: #6b7280; font-size: 15px; line-height: 1.6;">${message}</p>
                </div>
                <div style="padding: 16px 24px; background: #f9fafb; border-radius: 0 0 12px 12px; display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="btn btn-secondary" data-action="cancel" style="min-width: 100px;">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-danger" data-action="confirm" style="min-width: 100px;">
                        <i class="fas fa-check me-1"></i>Yes, Disable
                    </button>
                </div>
            `;
            
            overlay.appendChild(toast);
            document.body.appendChild(overlay);
            
            // Fade in
            setTimeout(() => {
                overlay.style.transition = 'opacity 0.2s';
                overlay.style.opacity = '1';
            }, 10);
            
            // Handle button clicks
            toast.querySelector('[data-action="confirm"]').addEventListener('click', function() {
                closeToast();
                if (onConfirm) onConfirm();
            });
            
            toast.querySelector('[data-action="cancel"]').addEventListener('click', closeToast);
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) closeToast();
            });
            
            function closeToast() {
                overlay.style.opacity = '0';
                setTimeout(() => {
                    if (overlay.parentNode) {
                        document.body.removeChild(overlay);
                    }
                }, 200);
            }
        }

        // Toggle password visibility for M-Pesa credentials
        const togglePasswordBtn = document.getElementById('toggle-password-visibility');
        if (togglePasswordBtn) {
            togglePasswordBtn.addEventListener('click', function() {
                const passwordFields = [
                    document.getElementById('subscription_mpesa_consumer_secret'),
                    document.getElementById('subscription_mpesa_passkey')
                ];

                passwordFields.forEach(field => {
                    if (field) {
                        field.type = field.type === 'password' ? 'text' : 'password';
                    }
                });

                const icon = this.querySelector('i');
                if (icon.classList.contains('fa-eye')) {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                    this.innerHTML = '<i class="fas fa-eye-slash"></i> Hide Credentials';
                } else {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                    this.innerHTML = '<i class="fas fa-eye"></i> Show Credentials';
                }
            });
        }

        // Handle M-Pesa credentials form submission
        const mpesaForm = document.getElementById('mpesa-credentials-form');
        if (mpesaForm) {
            mpesaForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const submitBtn = this.querySelector('button[type="submit"]');
                const btnText = submitBtn.querySelector('.btn-text');
                const btnLoading = submitBtn.querySelector('.btn-loading');
                
                if (btnText) btnText.classList.add('d-none');
                if (btnLoading) btnLoading.classList.remove('d-none');
                submitBtn.disabled = true;

                fetch(this.action, {
                    method: 'POST',
                    body: new FormData(this),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close modal
                        $('#mpesaCredentialsModal').modal('hide');
                        
                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.message || 'M-Pesa credentials updated successfully',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        throw new Error(data.message || 'Failed to update credentials');
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error.message || 'Failed to update M-Pesa credentials'
                    });
                })
                .finally(() => {
                    if (btnText) btnText.classList.remove('d-none');
                    if (btnLoading) btnLoading.classList.add('d-none');
                    submitBtn.disabled = false;
                });
            });
        }
    </script>
@endpush