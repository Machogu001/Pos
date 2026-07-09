@extends('layouts.app')

@section('title', __('payment.admin_dashboard'))

@section('content')
@php
    $isSuperadminAdminPanel = request()->routeIs('superadmin.admin.*');
    $usersIndexRoute = $isSuperadminAdminPanel ? 'superadmin.admin.users' : 'admin.users';
    $subscriptionsIndexRoute = $isSuperadminAdminPanel ? 'superadmin.admin.subscriptions' : 'admin.subscriptions';
    $userStatusRoute = $isSuperadminAdminPanel ? 'superadmin.admin.users.update-status' : 'admin.users.update-status';
    $subscriptionStatusRoute = $isSuperadminAdminPanel ? 'superadmin.admin.subscriptions.update-status' : 'admin.subscriptions.update-status';
    $manualSubscriptionRoute = $isSuperadminAdminPanel ? 'superadmin.admin.subscriptions.manual' : 'admin.subscriptions.manual';
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
            <span class="tw-font-medium tw-text-gray-700">{{ __('payment.admin_dashboard') }}</span>
        </div>
    </section>

    <section class="content">
@endif

<div class="dashboard-wrapper" style="max-width: 100%; overflow-x: hidden; padding: 1.5rem; margin: 0 auto;">

    <div class="tw-mb-5 tw-overflow-hidden tw-rounded-2xl tw-border tw-border-slate-200 tw-bg-white" style="box-shadow: 0 20px 46px rgba(15, 23, 42, 0.10);">
        <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-[minmax(0,1fr)_300px]">
            <div class="tw-px-5 tw-py-5 md:tw-px-6 md:tw-py-6" style="background: linear-gradient(135deg, #0f172a 0%, #0b3b66 45%, #0f766e 100%);">
                <div class="tw-max-w-3xl">
                    <span class="tw-inline-flex tw-items-center tw-gap-2 tw-rounded-full tw-px-3 tw-py-1 tw-text-xs tw-font-semibold tw-uppercase tw-tracking-[0.18em] tw-text-white" style="background: rgba(255,255,255,0.16); border: 1px solid rgba(255,255,255,0.20);">
                        <i class="fas fa-tachometer-alt"></i>
                        Operations Hub
                    </span>
                    <h1 class="tw-mt-3 tw-text-2xl tw-font-semibold tw-tracking-tight tw-text-white md:tw-text-3xl">
                        {{ __('payment.admin_dashboard') }}
                    </h1>
                    <p class="tw-mt-2 tw-max-w-2xl tw-text-sm tw-leading-6 tw-text-white" style="opacity: 0.94;">
                        {{ __('payment.overview_text') }}. Review users, subscription health, and key admin activity in one place.
                    </p>
                    <div class="tw-mt-4 tw-flex tw-flex-wrap tw-gap-2">
                        <span class="tw-inline-flex tw-items-center tw-gap-2 tw-rounded-full tw-px-3 tw-py-1.5 tw-text-xs tw-font-medium tw-text-white" style="background: rgba(15, 23, 42, 0.24); border: 1px solid rgba(255,255,255,0.12);">
                            <i class="fas fa-users" style="color: #fbbf24;"></i>
                            User oversight
                        </span>
                        <span class="tw-inline-flex tw-items-center tw-gap-2 tw-rounded-full tw-px-3 tw-py-1.5 tw-text-xs tw-font-medium tw-text-white" style="background: rgba(15, 23, 42, 0.24); border: 1px solid rgba(255,255,255,0.12);">
                            <i class="fas fa-receipt" style="color: #fbbf24;"></i>
                            Subscription controls
                        </span>
                    </div>
                </div>
            </div>
            <div class="tw-p-4" style="background: linear-gradient(180deg, #f8fafc 0%, #eef6ff 100%);">
                <div class="tw-grid tw-h-full tw-grid-cols-1 tw-gap-2.5 sm:tw-grid-cols-2 lg:tw-grid-cols-1 xl:tw-grid-cols-2">
                    <a href="{{ route($subscriptionsIndexRoute) }}"
                       class="tw-group tw-flex tw-items-start tw-gap-3 tw-rounded-xl tw-px-3.5 tw-py-3 tw-text-left tw-transition" style="background: linear-gradient(180deg, #fff8e8 0%, #ffffff 100%); border: 1px solid #f3d28b; box-shadow: 0 10px 24px rgba(217, 119, 6, 0.10);">
                        <span class="tw-inline-flex tw-h-9 tw-w-9 tw-items-center tw-justify-center tw-rounded-lg tw-text-white tw-shrink-0" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                            <i class="fas fa-receipt"></i>
                        </span>
                        <span class="tw-flex tw-min-w-0 tw-flex-1 tw-flex-col">
                            <span class="tw-text-sm tw-font-semibold tw-leading-5 tw-text-slate-900">
                                {{ __('payment.view_subscriptions') }}
                            </span>
                            <span class="tw-mt-0.5 tw-text-xs tw-leading-4" style="color: #6b4f1d;">
                                Review plans, renewals, and pending payments.
                            </span>
                        </span>
                    </a>
                    <a href="{{ route($usersIndexRoute) }}"
                       class="tw-group tw-flex tw-items-start tw-gap-3 tw-rounded-xl tw-px-3.5 tw-py-3 tw-text-left tw-transition" style="background: linear-gradient(135deg, #0f172a 0%, #0f4c5c 100%); border: 1px solid rgba(15, 118, 110, 0.24); box-shadow: 0 10px 24px rgba(15, 23, 42, 0.16);">
                        <span class="tw-inline-flex tw-h-9 tw-w-9 tw-items-center tw-justify-center tw-rounded-lg tw-text-white tw-shrink-0" style="background: rgba(255,255,255,0.14); border: 1px solid rgba(255,255,255,0.12);">
                            <i class="fas fa-users"></i>
                        </span>
                        <span class="tw-flex tw-min-w-0 tw-flex-1 tw-flex-col">
                            <span class="tw-text-sm tw-font-semibold tw-leading-5 tw-text-white">
                                {{ __('payment.view_users') }}
                            </span>
                            <span class="tw-mt-0.5 tw-text-xs tw-leading-4" style="color: #c9f7ef;">
                                Open user status, business mapping, and account management.
                            </span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="tw-mb-5 tw-rounded-xl tw-border tw-border-green-200 tw-bg-green-50 tw-p-4 tw-text-green-900">
            <div class="tw-flex tw-items-start tw-gap-3">
                <i class="fas fa-check-circle tw-mt-0.5"></i>
                <p class="tw-mb-0 tw-text-sm tw-font-medium">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="tw-mb-5 tw-rounded-xl tw-border tw-border-red-200 tw-bg-red-50 tw-p-4 tw-text-red-900">
            <div class="tw-flex tw-items-start tw-gap-3">
                <i class="fas fa-exclamation-circle tw-mt-0.5"></i>
                <p class="tw-mb-0 tw-text-sm tw-font-medium">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    @if(!empty($sellPostingAuditSummary) && (($sellPostingAuditSummary['missing_cogs_count'] ?? 0) > 0 || ($sellPostingAuditSummary['missing_inventory_count'] ?? 0) > 0))
        <div class="tw-mb-5 tw-rounded-xl tw-border tw-border-amber-200 tw-bg-amber-50 tw-p-5 tw-shadow-sm">
            <div class="lg:tw-flex lg:tw-items-center lg:tw-justify-between lg:tw-gap-6">
                <div>
                    <div class="tw-flex tw-items-center tw-gap-3 tw-text-amber-900">
                        <span class="tw-inline-flex tw-h-10 tw-w-10 tw-items-center tw-justify-center tw-rounded-full tw-bg-amber-100">
                            <i class="fas fa-triangle-exclamation"></i>
                        </span>
                        <div>
                            <h2 class="tw-mb-1 tw-text-lg tw-font-semibold">Missing sell accounting postings detected</h2>
                            <p class="tw-mb-0 tw-text-sm tw-text-amber-800">
                                Item sells with missing journals can distort cost of goods, gross profit, and net profit. Subscription invoices are excluded from this audit.
                            </p>
                        </div>
                    </div>
                    <div class="tw-mt-4 tw-flex tw-flex-wrap tw-gap-3 tw-text-sm tw-text-amber-900">
                        <span class="tw-rounded-full tw-bg-white/80 tw-px-3 tw-py-1 tw-font-medium">
                            Missing COGS: {{ number_format($sellPostingAuditSummary['missing_cogs_count'] ?? 0) }}
                        </span>
                        <span class="tw-rounded-full tw-bg-white/80 tw-px-3 tw-py-1 tw-font-medium">
                            Missing inventory: {{ number_format($sellPostingAuditSummary['missing_inventory_count'] ?? 0) }}
                        </span>
                        <span class="tw-rounded-full tw-bg-white/80 tw-px-3 tw-py-1 tw-font-medium">
                            Affected businesses: {{ number_format(count($sellPostingAuditSummary['affected_businesses'] ?? [])) }}
                        </span>
                    </div>
                </div>
                <form method="POST" action="{{ request()->routeIs('superadmin.admin.dashboard') ? route('superadmin.admin.dashboard.fix-sell-postings') : route('admin.dashboard.fix-sell-postings') }}" class="tw-mt-4 lg:tw-mt-0">
                    @csrf
                    <button type="submit" class="tw-inline-flex tw-items-center tw-justify-center tw-gap-2 tw-rounded-lg tw-bg-amber-600 tw-px-4 tw-py-2.5 tw-text-sm tw-font-semibold tw-text-white tw-transition hover:tw-bg-amber-700">
                        <i class="fas fa-wrench"></i>
                        Fix Missing Transactions
                    </button>
                </form>
            </div>
        </div>
    @endif

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
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">{{ __('payment.subscription_enforcement') }}</p>
                        <p class="tw-mt-0.5 tw-text-xs tw-text-gray-600 tw-line-clamp-2">
                            @if($settings->subscription_required ?? false)
                                {{ __('payment.subscription_required_enabled') }}
                            @else
                                {{ __('payment.subscription_required_disabled') }}
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
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">{{ __('payment.subscription_mpesa_credentials') }}</p>
                        <p class="tw-mt-0.5 tw-text-xs tw-text-gray-600 tw-line-clamp-2">{{ __('payment.configure_subscription_mpesa_credentials') }}</p>
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
                        <p class="tw-mt-0.5 tw-text-xs tw-text-gray-600 tw-line-clamp-2">{{ __('payment.manual_subscription_without_mpesa') }}</p>
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
                    <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">{{ __('payment.human_resource_management') }}</p>
                    <p class="tw-mt-0.5 tw-text-xs tw-text-gray-600 tw-line-clamp-2">
                        {{ __('payment.open_hrm_modules') }}
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

        <!-- Push Updates to Clients -->
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-green-200 tw-cursor-pointer" onclick="if(typeof window.openUpdateModal==='function'){window.openUpdateModal();}else{alert('Update modal not available.');}">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 tw-bg-green-100 tw-text-green-600">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">Push Updates</p>
                        <p class="tw-mt-0.5 tw-text-xs tw-text-gray-600 tw-line-clamp-2">Build &amp; push update package to all client servers</p>
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
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">{{ __('payment.etims_integration') }}</p>
                        <p class="tw-mt-0.5 tw-text-xs tw-text-gray-600 tw-line-clamp-2">{{ __('payment.configure_etims_settings') }}</p>
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
                                                <span class="text-muted">{{ __('payment.na') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-medium">{{ $user->username ?? $user->name ?? __('payment.na') }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-medium">{{ $user->phone ?? __('payment.na') }}</span>
                                        </td>
                                        <td>
                                            <form action="{{ route($userStatusRoute, $user) }}" 
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
                    <a href="{{ route($usersIndexRoute) }}" class="btn btn-primary">
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
                                            <form action="{{ route($subscriptionStatusRoute, $subscription) }}" 
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
                    <a href="{{ route($subscriptionsIndexRoute) }}" class="btn btn-success">
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
                        <i class="fas fa-user-plus me-2"></i>{{ __('payment.registration') }} {{ __('messages.settings') }}
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="{{ __('payment.close') }}">
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
                                    <input type="number" step="0.01" min="0" max="500000" class="form-control" name="registration_price"
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
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('payment.close') }}</button>
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
                        <i class="fas fa-money-bill-wave me-2"></i>{{ __('payment.payroll_defaults') }}
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="{{ __('payment.close') }}">
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
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('payment.close') }}</button>
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
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('payment.close') }}">
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
                                <label class="form-label fw-medium">{{ __('payment.subscription_invoice_prefix') }}</label>
                                <input type="text" class="form-control" name="subscription_invoice_prefix" value="{{ $settings?->subscription_invoice_prefix ?? '' }}" placeholder="{{ __('payment.subscription_invoice_prefix_placeholder') }}">
                                <small class="text-muted">{{ __('payment.subscription_invoice_prefix_help') }}</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">{{ __('payment.subscription_invoice_next') }}</label>
                                <input type="number" class="form-control" name="subscription_invoice_next" value="{{ $settings?->subscription_invoice_next ?? 1 }}" min="0">
                                <small class="text-muted">{{ __('payment.subscription_invoice_next_help') }}</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">{{ __('payment.subscription_vat_percent') }}</label>
                                <input type="number" step="0.01" class="form-control" name="subscription_vat_percent" value="{{ $settings?->subscription_vat_percent ?? 0 }}" min="0" max="100">
                                <small class="text-muted">{{ __('payment.subscription_vat_percent_help') }}</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">{{ __('payment.subscription_round_precision') }}</label>
                                <input type="number" class="form-control" name="subscription_round_precision" value="{{ $settings?->subscription_round_precision ?? 0 }}" min="0" max="6">
                                <small class="text-muted">{{ __('payment.subscription_round_precision_help') }}</small>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label class="form-label fw-medium">{{ __('payment.invoice_footer_note') }}</label>
                                <textarea class="form-control" name="invoice_footer" rows="3">{{ $settings?->invoice_footer ?? '' }}</textarea>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label class="form-label fw-medium">{{ __('payment.statement_footer_note') }}</label>
                                <textarea class="form-control" name="statement_footer" rows="3">{{ $settings?->statement_footer ?? '' }}</textarea>
                                <small class="text-muted">{{ __('payment.statement_footer_help') }}</small>
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
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('payment.close') }}</button>
                </div>
              </form>
            </div>
        </div>
    </div>

    <!-- System Modal -->
    <div class="modal fade" id="systemModal" tabindex="-1" aria-labelledby="systemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header py-2" style="background:linear-gradient(135deg,#17a2b8 0%,#138496 100%);">
                    <h6 class="modal-title text-white mb-0" id="systemModalLabel">
                        <i class="fas fa-server me-2"></i>{{ __('payment.system') }} {{ __('messages.settings') }}
                    </h6>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="{{ __('payment.close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.settings.update') }}" method="POST" class="settings-form"
                      onsubmit="return handleModalFormSubmit(event, this, 'systemModal');">
                    @csrf
                    <input type="hidden" name="monthly_price" value="{{ $settings?->monthly_price ?? 0 }}">
                    <input type="hidden" name="quarterly_price" value="{{ $settings?->quarterly_price ?? 0 }}">
                    <input type="hidden" name="yearly_price" value="{{ $settings?->yearly_price ?? 0 }}">

                    <div class="modal-body py-3 px-4" style="background:#f8fafc;">

                        {{-- ── General ─────────────────────────────────────────────── --}}
                        <div class="sys-section-card mb-3">
                            <div class="sys-section-title">
                                <i class="fas fa-sliders-h me-1"></i>{{ __('lang_v1.general') }}
                            </div>
                            <div class="row g-2 align-items-end">
                                <div class="col-auto" style="padding-top:1.5rem;">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" name="auto_renewal"
                                               id="auto_renewal" value="1"
                                               {{ ($settings?->auto_renewal ?? false) ? 'checked' : '' }}>
                                        <label for="auto_renewal" class="form-check-label fw-medium" style="font-size:.85rem;">
                                            {{ __('payment.enable_auto_renewal') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <label class="form-label mb-1 fw-medium" style="font-size:.8rem;">{{ __('payment.grace_period_days') }}</label>
                                    <input type="number" class="form-control form-control-sm" name="grace_period_days"
                                           value="{{ $settings?->grace_period_days ?? 7 }}" required>
                                </div>
                                <div class="col-sm-3">
                                    <label class="form-label mb-1 fw-medium" style="font-size:.8rem;">{{ __('payment.recent_records_limit') }}</label>
                                    <input type="number" min="1" max="100" class="form-control form-control-sm" name="recent_limit"
                                           value="{{ $settings?->recent_limit ?? 5 }}" required>
                                    <small class="text-muted" style="font-size:.72rem;">{{ __('payment.controls_how_many_recent') }}</small>
                                </div>
                            </div>
                        </div>

                        {{-- ── Scheduled Tasks ─────────────────────────────────────── --}}
                        <div class="sys-section-card">
                            <div class="sys-section-title">
                                <i class="fas fa-clock me-1"></i>{{ __('lang_v1.scheduled_tasks') ?? 'Scheduled Tasks' }}
                            </div>
                            <div class="row g-3">

                                {{-- Card 1: Auto-close Register --}}
                                <div class="col-lg-4 col-md-6">
                                    <input type="hidden" name="auto_close_register" value="0">
                                    <div class="sched-card h-100">
                                        <div class="sched-card__head">
                                            <div class="sched-card__icon" style="background:#e0f2fe;color:#0369a1;"><i class="fas fa-cash-register"></i></div>
                                            <div class="sched-card__title">{{ __('payment.auto_close_register') }}</div>
                                            <label class="otp-switch ms-auto flex-shrink-0" title="{{ __('payment.toggle_auto_close_register') }}">
                                                <input type="checkbox" name="auto_close_register" id="autoCloseRegister" value="1"
                                                       {{ ($settings?->auto_close_register ?? false) ? 'checked' : '' }}>
                                                <span class="otp-slider"></span>
                                            </label>
                                        </div>
                                        <div class="sched-card__hint">{{ __('payment.auto_close_register_hint') }}</div>
                                        <div class="sched-card__badge {{ ($settings?->auto_close_register ?? false) ? 'is-on' : 'is-off' }}" id="autoCloseRegisterStatus">
                                            {{ ($settings?->auto_close_register ?? false) ? __('payment.enabled') : __('payment.disabled') }}
                                        </div>
                                        <div class="sched-card__note">{{ __('payment.auto_close_register_note') }}</div>
                                        <div class="sched-card__row mt-auto pt-2">
                                            <span class="sched-card__lbl">{{ __('payment.close_at') }}</span>
                                            <input type="time" class="form-control form-control-sm sched-card__time"
                                                   name="auto_close_register_time" id="autoCloseRegisterTime"
                                                   value="{{ $settings?->auto_close_register_time ?? '23:59' }}">
                                        </div>
                                    </div>
                                </div>

                                {{-- Card 2: Accounting Backfill --}}
                                <div class="col-lg-4 col-md-6">
                                    <input type="hidden" name="accounting_backfill_enabled" value="0">
                                    <div class="sched-card h-100">
                                        <div class="sched-card__head">
                                            <div class="sched-card__icon" style="background:#fef9c3;color:#854d0e;"><i class="fas fa-calculator"></i></div>
                                            <div class="sched-card__title">{{ __('payment.accounting_backfill_schedule') }}</div>
                                            <label class="otp-switch ms-auto flex-shrink-0" title="{{ __('payment.toggle_accounting_backfill_schedule') }}">
                                                <input type="checkbox" name="accounting_backfill_enabled" id="accountingBackfillEnabled" value="1"
                                                       {{ ($settings?->accounting_backfill_enabled ?? false) ? 'checked' : '' }}>
                                                <span class="otp-slider"></span>
                                            </label>
                                        </div>
                                        <div class="sched-card__hint">{{ __('payment.accounting_backfill_schedule_hint') }}</div>
                                        <div class="sched-card__badge {{ ($settings?->accounting_backfill_enabled ?? false) ? 'is-on' : 'is-off' }}" id="accountingBackfillStatus">
                                            {{ ($settings?->accounting_backfill_enabled ?? false) ? __('payment.enabled') : __('payment.disabled') }}
                                        </div>
                                        <div class="sched-card__note">{{ __('payment.accounting_backfill_schedule_note') }}</div>
                                        <div class="sched-card__runinfo">
                                            <span><i class="far fa-check-circle me-1"></i>{{ __('payment.last_run_at') }}: <strong>{{ !empty($accountingBackfillStatus['last_run']) ? \Carbon\Carbon::parse($accountingBackfillStatus['last_run'])->format('d M H:i') : __('payment.not_available') }}</strong></span>
                                            <span><i class="far fa-clock me-1"></i>{{ __('payment.next_run_at') }}: <strong>{{ !empty($accountingBackfillStatus['next_run']) ? \Carbon\Carbon::parse($accountingBackfillStatus['next_run'])->format('d M H:i') : __('payment.not_available') }}</strong></span>
                                        </div>
                                        <div class="sched-card__row mt-2">
                                            <span class="sched-card__lbl">{{ __('payment.frequency') }}</span>
                                            <select class="form-select form-select-sm sched-card__sel" name="accounting_backfill_frequency" id="accountingBackfillFrequency">
                                                @php $backfillFrequency = $settings?->accounting_backfill_frequency ?? 'hourly'; @endphp
                                                <option value="every_fifteen_minutes" {{ $backfillFrequency === 'every_fifteen_minutes' ? 'selected' : '' }}>{{ __('payment.every_15_minutes') }}</option>
                                                <option value="every_thirty_minutes"  {{ $backfillFrequency === 'every_thirty_minutes'  ? 'selected' : '' }}>{{ __('payment.every_30_minutes') }}</option>
                                                <option value="hourly" {{ $backfillFrequency === 'hourly' ? 'selected' : '' }}>{{ __('payment.hourly') }}</option>
                                                <option value="daily"  {{ $backfillFrequency === 'daily'  ? 'selected' : '' }}>{{ __('payment.daily') }}</option>
                                            </select>
                                        </div>
                                        <div class="sched-card__row mt-1" id="accountingBackfillTimeWrap"
                                             style="display:{{ ($settings?->accounting_backfill_frequency ?? 'hourly') === 'daily' ? 'flex' : 'none' }};">
                                            <span class="sched-card__lbl">{{ __('payment.run_at') }}</span>
                                            <input type="time" class="form-control form-control-sm sched-card__time"
                                                   name="accounting_backfill_time" id="accountingBackfillTime"
                                                   value="{{ $settings?->accounting_backfill_time ?? '02:00' }}">
                                        </div>
                                    </div>
                                </div>

                                {{-- Card 3: Stock Costing Backfill --}}
                                <div class="col-lg-4 col-md-12">
                                    <input type="hidden" name="stock_costing_backfill_enabled" value="0">
                                    <input type="hidden" name="run_stock_costing_backfill_now" id="runStockCostingBackfillNow" value="0">
                                    <input type="hidden" name="run_stock_costing_backfill_dry_run" id="runStockCostingBackfillDryRun" value="0">
                                    <div class="sched-card h-100">
                                        <div class="sched-card__head">
                                            <div class="sched-card__icon" style="background:#dcfce7;color:#166534;"><i class="fas fa-boxes"></i></div>
                                            <div class="sched-card__title">{{ __('payment.stock_costing_backfill_schedule') }}</div>
                                            <label class="otp-switch ms-auto flex-shrink-0" title="{{ __('payment.toggle_stock_costing_backfill_schedule') }}">
                                                <input type="checkbox" name="stock_costing_backfill_enabled" id="stockCostingBackfillEnabled" value="1"
                                                       {{ ($settings?->stock_costing_backfill_enabled ?? false) ? 'checked' : '' }}>
                                                <span class="otp-slider"></span>
                                            </label>
                                        </div>
                                        <div class="sched-card__hint">{{ __('payment.stock_costing_backfill_schedule_hint') }}</div>
                                        <div class="sched-card__badge {{ ($settings?->stock_costing_backfill_enabled ?? false) ? 'is-on' : 'is-off' }}" id="stockCostingBackfillStatus">
                                            {{ ($settings?->stock_costing_backfill_enabled ?? false) ? __('payment.enabled') : __('payment.disabled') }}
                                        </div>
                                        <div class="sched-card__note">{{ __('payment.stock_costing_backfill_schedule_note') }}</div>
                                        <div class="sched-card__runinfo">
                                            <span><i class="far fa-check-circle me-1"></i>{{ __('payment.last_run_at') }}: <strong>{{ !empty($stockCostingBackfillStatus['last_run']) ? \Carbon\Carbon::parse($stockCostingBackfillStatus['last_run'])->format('d M H:i') : __('payment.not_available') }}</strong></span>
                                            <span><i class="far fa-clock me-1"></i>{{ __('payment.next_run_at') }}: <strong>{{ !empty($stockCostingBackfillStatus['next_run']) ? \Carbon\Carbon::parse($stockCostingBackfillStatus['next_run'])->format('d M H:i') : __('payment.not_available') }}</strong></span>
                                        </div>
                                        <div class="sched-card__row mt-2">
                                            <span class="sched-card__lbl">{{ __('payment.frequency') }}</span>
                                            <select class="form-select form-select-sm sched-card__sel" name="stock_costing_backfill_frequency" id="stockCostingBackfillFrequency">
                                                @php $stockBackfillFrequency = $settings?->stock_costing_backfill_frequency ?? 'daily'; @endphp
                                                <option value="every_fifteen_minutes" {{ $stockBackfillFrequency === 'every_fifteen_minutes' ? 'selected' : '' }}>{{ __('payment.every_15_minutes') }}</option>
                                                <option value="every_thirty_minutes"  {{ $stockBackfillFrequency === 'every_thirty_minutes'  ? 'selected' : '' }}>{{ __('payment.every_30_minutes') }}</option>
                                                <option value="hourly" {{ $stockBackfillFrequency === 'hourly' ? 'selected' : '' }}>{{ __('payment.hourly') }}</option>
                                                <option value="daily"  {{ $stockBackfillFrequency === 'daily'  ? 'selected' : '' }}>{{ __('payment.daily') }}</option>
                                            </select>
                                        </div>
                                        <div class="sched-card__row mt-1" id="stockCostingBackfillTimeWrap"
                                             style="display:{{ ($settings?->stock_costing_backfill_frequency ?? 'daily') === 'daily' ? 'flex' : 'none' }};">
                                            <span class="sched-card__lbl">{{ __('payment.run_at') }}</span>
                                            <input type="time" class="form-control form-control-sm sched-card__time"
                                                   name="stock_costing_backfill_time" id="stockCostingBackfillTime"
                                                   value="{{ $settings?->stock_costing_backfill_time ?? '01:30' }}">
                                        </div>
                                        <div class="row g-2 mt-1">
                                            <div class="col-6">
                                                <label class="sched-card__lbl d-block mb-1">{{ __('lang_v1.business') }}</label>
                                                <select class="form-select form-select-sm" name="stock_costing_backfill_business_id" id="stockCostingBackfillBusinessId">
                                                    <option value="">— {{ __('lang_v1.business') }} —</option>
                                                    @foreach($schedulerBusinesses ?? [] as $sb)
                                                        <option value="{{ $sb->id }}" {{ (int)($settings?->stock_costing_backfill_business_id) === (int)$sb->id ? 'selected' : '' }}>{{ $sb->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label class="sched-card__lbl d-block mb-1">{{ __('lang_v1.location') }}</label>
                                                <select class="form-select form-select-sm" name="stock_costing_backfill_location_id" id="stockCostingBackfillLocationId">
                                                    <option value="">— {{ __('lang_v1.location') }} —</option>
                                                    @foreach($schedulerLocations ?? [] as $sl)
                                                        <option value="{{ $sl->id }}" data-business="{{ $sl->business_id }}"
                                                            {{ (int)($settings?->stock_costing_backfill_location_id) === (int)$sl->id ? 'selected' : '' }}>{{ $sl->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div id="stockCostingBackfillIdsWarning" class="alert alert-warning py-1 px-2 mt-2 small" style="display:none;">
                                            <i class="fas fa-exclamation-triangle me-1"></i>{{ __('payment.stock_costing_backfill_ids_required') }}
                                        </div>
                                        <div class="sched-card__row mt-1">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="runStockCostingBackfillDryRunBtn">
                                                <i class="fas fa-search me-1"></i>{{ __('payment.run_stock_backfill_dry_run') }}
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-success" id="runStockCostingBackfillNowBtn">
                                                <i class="fas fa-play me-1"></i>{{ __('payment.run_stock_backfill_now') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>{{-- /row --}}
                        </div>{{-- /sys-section-card --}}

                    </div>{{-- /modal-body --}}

                    <div class="modal-footer py-2">
                        <button type="submit" class="btn btn-sm btn-info px-4">
                            <span class="btn-text">{{ __('payment.update_settings') }}</span>
                            <span class="btn-loading d-none"><span class="spinner-border spinner-border-sm" role="status"></span></span>
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">{{ __('payment.close') }}</button>
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
                        <i class="fas fa-building me-2"></i>{{ __('payment.business_information') }}
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="{{ __('payment.close') }}">
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
                                <label class="form-label fw-medium">{{ __('payment.business_name') }}</label>
                                <input type="text" class="form-control" name="company_name" value="{{ $settings?->company_name ?? '' }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">{{ __('payment.company_logo') }}</label>
                                <input type="file" class="form-control" name="company_logo" accept="image/*">
                                @if(!empty($settings?->company_logo))
                                    <div class="mt-2 small text-muted">{{ __('payment.current_label') }} <a href="{{ asset($settings->company_logo) }}" target="_blank">{{ __('messages.view') }}</a></div>
                                @endif
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-medium">{{ __('payment.business_phone') }}</label>
                                <input type="text" class="form-control" name="company_contact_phone" value="{{ $settings?->company_contact_phone ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">{{ __('payment.business_email') }}</label>
                                <input type="email" class="form-control" name="company_contact_email" value="{{ $settings?->company_contact_email ?? '' }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">{{ __('payment.invoice_pin') }}</label>
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
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('payment.close') }}</button>
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
                        <i class="fas fa-exchange-alt me-2"></i>{{ __('payment.etims_integration_settings') }}
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="{{ __('payment.close') }}">
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
                            <strong>{{ __('payment.configure_etims_api_integration') }}</strong>
                            <br><small class="text-dark">{{ __('payment.etims_integration_help') }}</small>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label class="form-label fw-medium">{{ __('payment.etims_api_url_label') }}</label>
                                <input type="url" class="form-control" name="etims_api_url" 
                                       value="{{ $settings?->etims_api_url ?? '' }}" 
                                       placeholder="{{ __('payment.etims_api_url_placeholder') }}">
                                <small class="text-muted">{{ __('payment.etims_api_url_help') }}</small>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label class="form-label fw-medium">{{ __('payment.api_token') }}</label>
                                <textarea class="form-control" name="etims_api_token" rows="3" 
                                          placeholder="{{ __('payment.etims_api_token_placeholder') }}">{{ $settings?->etims_api_token ?? '' }}</textarea>
                                <small class="text-muted">{{ __('payment.etims_api_token_help') }}</small>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">{{ __('payment.branch_id') }}</label>
                                <input type="text" class="form-control" name="etims_branch_id" 
                                       value="{{ $settings?->etims_branch_id ?? '' }}" 
                                       placeholder="{{ __('payment.etims_branch_id_placeholder') }}" maxlength="10">
                                <small class="text-muted">{{ __('payment.etims_branch_id_help') }}</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">{{ __('payment.auto_transmit_products') }}</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="etims_auto_transmit" 
                                           id="etimsAutoTransmit" value="1"
                                           {{ ($settings?->etims_auto_transmit ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="etimsAutoTransmit">
                                        {{ __('payment.automatically_transmit_product_sales') }}
                                    </label>
                                </div>
                                <small class="text-muted">{{ __('payment.auto_transmit_products_help') }}</small>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">{{ __('payment.transmit_subscriptions') }}</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="etims_transmit_subscriptions" 
                                           id="etimsTransmitSubscriptions" value="1"
                                           {{ ($settings?->etims_transmit_subscriptions ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="etimsTransmitSubscriptions">
                                        {{ __('payment.include_subscription_invoices') }}
                                    </label>
                                </div>
                                <small class="text-muted">{{ __('payment.transmit_subscriptions_help') }}</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">{{ __('payment.transmit_registrations') }}</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="etims_transmit_registrations" 
                                           id="etimsTransmitRegistrations" value="1"
                                           {{ ($settings?->etims_transmit_registrations ?? false) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="etimsTransmitRegistrations">
                                        {{ __('payment.include_registration_payments') }}
                                    </label>
                                </div>
                                <small class="text-muted">{{ __('payment.transmit_registrations_help') }}</small>
                            </div>
                        </div>

                        <div class="alert alert-light border">
                            <div class="text-dark">
                                <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
                                <strong>{{ __('payment.tax_code_information') }}</strong>
                            </div>
                            <ul class="mb-0 mt-2 small">
                                <li>{{ __('payment.tax_code_a_description') }}</li>
                                <li>{{ __('payment.tax_code_b_description') }}</li>
                                <li>{{ __('payment.tax_code_e_description') }}</li>
                            </ul>
                            <small class="d-block mt-2">{{ __('payment.tax_code_summary_note') }}</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success px-4">
                            <span class="btn-text">
                                <i class="fas fa-save me-2"></i>{{ __('payment.save_etims_settings') }}
                            </span>
                            <span class="btn-loading d-none">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </span>
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('payment.close') }}</button>
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
                        <i class="fas fa-shield-alt me-2"></i>{{ __('payment.subscription_enforcement') }}
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="{{ __('payment.close') }}">
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
                        
                        <h4 class="mb-3">{{ __('payment.current_status') }}: 
                            <span class="badge {{ ($settings->subscription_required ?? false) ? 'bg-success' : 'bg-secondary' }}">
                                {{ ($settings->subscription_required ?? false) ? __('payment.enabled_upper') : __('payment.disabled_upper') }}
                            </span>
                        </h4>
                        
                        <p class="text-muted mb-4">
                            @if($settings->subscription_required ?? false)
                                <i class="fas fa-check-circle text-success me-1"></i>
                                {{ __('payment.users_must_have_active_subscription') }}
                            @else
                                <i class="fas fa-info-circle text-secondary me-1"></i>
                                {{ __('payment.users_can_use_without_subscription') }}
                            @endif
                        </p>

                        <div class="alert {{ ($settings->subscription_required ?? false) ? 'alert-warning' : 'alert-info' }}">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            @if($settings->subscription_required ?? false)
                                {{ __('payment.disabling_subscription_enforcement_warning') }}
                            @else
                                {{ __('payment.enabling_subscription_enforcement_warning') }}
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
                                {{ ($settings->subscription_required ?? false) ? __('payment.disable_enforcement') : __('payment.enable_enforcement') }}
                            </span>
                            <span class="btn-loading d-none">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </span>
                        </button>
                    </form>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('payment.close') }}</button>
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
                        <i class="fas fa-mobile-alt me-2"></i>{{ __('payment.subscription_mpesa_credentials') }}
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="{{ __('payment.close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.settings.update-subscription-mpesa') }}" method="POST" id="mpesa-credentials-form">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            {{ __('payment.configure_subscription_mpesa_credentials_help') }}
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="subscription_mpesa_consumer_key" class="form-label fw-medium">{{ __('payment.consumer_key') }}</label>
                                <input type="text" class="form-control" id="subscription_mpesa_consumer_key" 
                                       name="subscription_mpesa_consumer_key" 
                                       value="{{ $settings->subscription_mpesa_consumer_key ?? '' }}"
                                    placeholder="{{ __('payment.enter_mpesa_consumer_key') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="subscription_mpesa_consumer_secret" class="form-label fw-medium">{{ __('payment.consumer_secret') }}</label>
                                <input type="password" class="form-control" id="subscription_mpesa_consumer_secret" 
                                       name="subscription_mpesa_consumer_secret" 
                                       value="{{ $settings->subscription_mpesa_consumer_secret ?? '' }}"
                                    placeholder="{{ __('payment.enter_mpesa_consumer_secret') }}">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label for="subscription_mpesa_shortcode" class="form-label fw-medium">{{ __('payment.shortcode') }}</label>
                                <input type="text" class="form-control" id="subscription_mpesa_shortcode" 
                                       name="subscription_mpesa_shortcode" 
                                       value="{{ $settings->subscription_mpesa_shortcode ?? '' }}"
                                    placeholder="{{ __('payment.shortcode_example') }}">
                            </div>
                            <div class="col-md-3">
                                <label for="subscription_mpesa_shortcode_type" class="form-label fw-medium">{{ __('payment.shortcode_type') }}</label>
                                <select class="form-select" id="subscription_mpesa_shortcode_type" name="subscription_mpesa_shortcode_type"
                                        onchange="document.getElementById('sub_store_number_row').style.display = this.value === 'till' ? '' : 'none'">
                                    <option value="paybill" {{ ($settings->subscription_mpesa_shortcode_type ?? 'paybill') === 'paybill' ? 'selected' : '' }}>
                                        PayBill
                                    </option>
                                    <option value="till" {{ ($settings->subscription_mpesa_shortcode_type ?? 'paybill') === 'till' ? 'selected' : '' }}>
                                        Till (Buy Goods)
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3" id="sub_store_number_row" style="display: {{ ($settings->subscription_mpesa_shortcode_type ?? 'paybill') === 'till' ? '' : 'none' }}">
                                <label for="subscription_mpesa_store_number" class="form-label fw-medium">Store Number</label>
                                <input type="text" class="form-control" id="subscription_mpesa_store_number"
                                       name="subscription_mpesa_store_number"
                                       value="{{ $settings->subscription_mpesa_store_number ?? '' }}"
                                       placeholder="e.g. 5426425">
                                <small class="text-muted">Head-office / agent number (PartyB)</small>
                            </div>
                            <div class="col-md-3">
                                <label for="subscription_mpesa_passkey" class="form-label fw-medium">{{ __('payment.passkey') }}</label>
                                <input type="password" class="form-control" id="subscription_mpesa_passkey" 
                                       name="subscription_mpesa_passkey" 
                                       value="{{ $settings->subscription_mpesa_passkey ?? '' }}"
                                    placeholder="{{ __('payment.enter_mpesa_passkey') }}">
                            </div>
                            <div class="col-md-3">
                                <label for="subscription_mpesa_callback" class="form-label fw-medium">{{ __('payment.callback_url') }}</label>
                                <input type="url" class="form-control" id="subscription_mpesa_callback" 
                                       name="subscription_mpesa_callback" 
                                       value="{{ $settings->subscription_mpesa_callback ?? '' }}"
                                    placeholder="{{ __('payment.mpesa_callback_placeholder') }}">
                            </div>
                        </div>

                        <div class="d-flex justify-content-start">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="toggle-password-visibility">
                                <i class="fas fa-eye"></i> {{ __('payment.show_credentials') }}
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary px-4">
                            <span class="btn-text">
                                <i class="fas fa-save me-1"></i>{{ __('payment.save_mpesa_credentials') }}
                            </span>
                            <span class="btn-loading d-none">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </span>
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('payment.close') }}</button>
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
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="{{ __('payment.close') }}">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route($manualSubscriptionRoute) }}" method="POST" class="ajax-form" id="manual-subscription-form">
                    @csrf
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <span class="fw-semibold">{{ __('payment.quick_creation') }}</span> {{ __('payment.manual_subscription_quick_creation_help') }}
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">
                                    <i class="fas fa-user me-1 text-primary"></i>
                                    {{ __('payment.select_user') }} <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="user_id" required>
                                    <option value="">{{ __('payment.select_user') }}</option>
                                    @foreach($manualSubscriptionUsers ?? [] as $user)
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
                                    {{ __('payment.custom_amount') }} ({{ __('payment.ksh') }})
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light fw-semibold">{{ __('payment.ksh') }}</span>
                                    <input type="number" step="0.01" class="form-control" name="custom_amount" placeholder="{{ __('payment.optional') }}">
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
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('payment.close') }}</button>
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

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}">
    <style>
        /* ── OTP-style switch (shared with manage_user/edit) ── */
        .otp-card {
            padding: 14px 16px;
            border: 1px solid #dbe2ea;
            border-radius: 14px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            min-height: 120px;
        }
        .otp-card__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .otp-card__label {
            font-weight: 700;
            color: #111827;
            font-size: 0.95rem;
        }
        .otp-card__hint,
        .otp-card__phone {
            color: #6b7280;
            font-size: 0.85rem;
            margin-top: 3px;
        }
        .otp-card__status {
            display: inline-flex;
            align-items: center;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 700;
            margin-top: 10px;
        }
        .otp-card__status.is-on  { background: #dcfce7; color: #166534; }
        .otp-card__status.is-off { background: #e5e7eb; color: #374151; }

            /* ── System modal redesign ────────────────────────────────── */
            .sys-section-card {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                padding: 14px 16px;
            }
            .sys-section-title {
                font-size: .78rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .06em;
                color: #64748b;
                margin-bottom: 12px;
            }
            /* scheduler card */
            .sched-card {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                padding: 12px 14px;
                display: flex;
                flex-direction: column;
                gap: 6px;
            }
            .sched-card__head {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .sched-card__icon {
                width: 30px; height: 30px;
                border-radius: 8px;
                display: flex; align-items: center; justify-content: center;
                font-size: .85rem;
                flex-shrink: 0;
            }
            .sched-card__title {
                font-size: .82rem;
                font-weight: 700;
                color: #1e293b;
                line-height: 1.2;
            }
            .sched-card__hint {
                font-size: .75rem;
                color: #64748b;
                line-height: 1.3;
            }
            .sched-card__badge {
                display: inline-flex;
                align-items: center;
                padding: 2px 9px;
                border-radius: 999px;
                font-size: .72rem;
                font-weight: 700;
                width: fit-content;
            }
            .sched-card__badge.is-on  { background: #dcfce7; color: #166534; }
            .sched-card__badge.is-off { background: #e5e7eb; color: #374151; }
            .sched-card__note {
                font-size: .72rem;
                color: #94a3b8;
                line-height: 1.3;
            }
            .sched-card__runinfo {
                display: flex;
                flex-direction: column;
                gap: 2px;
                font-size: .72rem;
                color: #475569;
                background: #f8fafc;
                border-radius: 6px;
                padding: 5px 8px;
            }
            .sched-card__runinfo strong { color: #1e293b; }
            .sched-card__row {
                display: flex;
                align-items: center;
                gap: 8px;
                flex-wrap: wrap;
            }
            .sched-card__lbl {
                font-size: .75rem;
                font-weight: 600;
                color: #475569;
                white-space: nowrap;
            }
            .sched-card__sel  { max-width: 100%; }
            .sched-card__time { max-width: 105px; }
        .otp-switch {
            position: relative;
            display: inline-block;
            width: 54px;
            height: 30px;
            margin: 0;
            flex: 0 0 auto;
        }
        .otp-switch input {
            position: absolute !important;
            opacity: 0 !important;
            width: 0 !important;
            height: 0 !important;
            pointer-events: none !important;
        }
        .otp-slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background-color: #cbd5e1;
            transition: 0.25s;
            border-radius: 999px;
            box-shadow: inset 0 0 0 1px rgba(15,23,42,0.08);
        }
        .otp-slider:before {
            position: absolute;
            content: '';
            height: 22px; width: 22px;
            left: 4px; top: 4px;
            background-color: white;
            transition: 0.25s;
            border-radius: 50%;
            box-shadow: 0 2px 6px rgba(15,23,42,0.18);
        }
        .otp-switch input:checked + .otp-slider              { background-color: #2563eb; }
        .otp-switch input:checked + .otp-slider:before       { transform: translateX(24px); }
        .otp-switch input:disabled + .otp-slider             { cursor: not-allowed; opacity: 0.6; }

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
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // ── Auto-close register switch: live status badge update ──────────
        document.getElementById('autoCloseRegister')?.addEventListener('change', function () {
            var badge = document.getElementById('autoCloseRegisterStatus');
            if (!badge) return;
            if (this.checked) {
                badge.textContent = '{{ __('payment.enabled') }}';
                badge.classList.replace('is-off', 'is-on');
            } else {
                badge.textContent = '{{ __('payment.disabled') }}';
                badge.classList.replace('is-on', 'is-off');
            }
        });

        document.getElementById('accountingBackfillEnabled')?.addEventListener('change', function () {
            var badge = document.getElementById('accountingBackfillStatus');
            if (!badge) return;
            if (this.checked) {
                badge.textContent = '{{ __('payment.enabled') }}';
                badge.classList.replace('is-off', 'is-on');
            } else {
                badge.textContent = '{{ __('payment.disabled') }}';
                badge.classList.replace('is-on', 'is-off');
            }
        });

        document.getElementById('accountingBackfillFrequency')?.addEventListener('change', function () {
            var wrap = document.getElementById('accountingBackfillTimeWrap');
            if (!wrap) return;
            wrap.style.display = this.value === 'daily' ? 'flex' : 'none';
        });

        document.getElementById('stockCostingBackfillEnabled')?.addEventListener('change', function () {
            var badge = document.getElementById('stockCostingBackfillStatus');
            if (!badge) return;
            if (this.checked) {
                badge.textContent = '{{ __('payment.enabled') }}';
                badge.classList.replace('is-off', 'is-on');
            } else {
                badge.textContent = '{{ __('payment.disabled') }}';
                badge.classList.replace('is-on', 'is-off');
            }
        });

        document.getElementById('stockCostingBackfillFrequency')?.addEventListener('change', function () {
            var wrap = document.getElementById('stockCostingBackfillTimeWrap');
            if (!wrap) return;
            wrap.style.display = this.value === 'daily' ? 'flex' : 'none';
        });

        (function () {
            var toggle   = document.getElementById('stockCostingBackfillEnabled');
            var bizSel   = document.getElementById('stockCostingBackfillBusinessId');
            var locSel   = document.getElementById('stockCostingBackfillLocationId');
            var warning  = document.getElementById('stockCostingBackfillIdsWarning');
            var runNowInput = document.getElementById('runStockCostingBackfillNow');
            var dryRunInput = document.getElementById('runStockCostingBackfillDryRun');
            var runNowBtn = document.getElementById('runStockCostingBackfillNowBtn');
            var dryRunBtn = document.getElementById('runStockCostingBackfillDryRunBtn');
            if (!toggle || !bizSel || !locSel) return;

            // Cascade: filter location options by selected business
            var allLocOptions = Array.from(locSel.options).map(function(o) {
                return { value: o.value, text: o.text, biz: o.dataset.business };
            });

            function filterLocations() {
                var bizId = bizSel.value;
                var currentLoc = locSel.value;
                locSel.innerHTML = '';
                var blank = document.createElement('option');
                blank.value = ''; blank.textContent = '— Select location —';
                locSel.appendChild(blank);
                allLocOptions.forEach(function(opt) {
                    if (!opt.value) return;
                    if (!bizId || opt.biz === bizId) {
                        var o = document.createElement('option');
                        o.value = opt.value; o.textContent = opt.text;
                        o.dataset.business = opt.biz;
                        if (opt.value === currentLoc) o.selected = true;
                        locSel.appendChild(o);
                    }
                });
            }

            bizSel.addEventListener('change', filterLocations);
            filterLocations(); // run on page load to apply saved business filter

            function validateIds() {
                var enabled = toggle.checked;
                var missing = enabled && (!bizSel.value || !locSel.value);
                if (warning) warning.style.display = missing ? '' : 'none';
                bizSel.classList.toggle('is-invalid', !!(enabled && !bizSel.value));
                locSel.classList.toggle('is-invalid', !!(enabled && !locSel.value));
                return !missing;
            }

            toggle.addEventListener('change', validateIds);
            bizSel.addEventListener('change', validateIds);
            locSel.addEventListener('change', validateIds);

            // Hook into the System modal form submit to block when IDs are missing
            var systemForm = document.querySelector('#systemModal form');
            if (systemForm) {
                systemForm.addEventListener('submit', function (e) {
                    if (!validateIds()) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        (bizSel.value ? locSel : bizSel).focus();
                    }
                }, true);

                // Reset run-now flag for normal saves unless explicitly triggered.
                systemForm.addEventListener('submit', function () {
                    if ((!runNowBtn || !runNowBtn.dataset.triggered) && runNowInput) {
                        runNowInput.value = '0';
                    }
                    if ((!dryRunBtn || !dryRunBtn.dataset.triggered) && dryRunInput) {
                        dryRunInput.value = '0';
                    }
                    if (runNowBtn) {
                        delete runNowBtn.dataset.triggered;
                    }
                    if (dryRunBtn) {
                        delete dryRunBtn.dataset.triggered;
                    }
                });
            }

            if (dryRunBtn && systemForm) {
                dryRunBtn.addEventListener('click', function () {
                    if (!validateIds()) {
                        (bizSel.value ? locSel : bizSel).focus();
                        return;
                    }

                    if (runNowInput) {
                        runNowInput.value = '0';
                    }
                    if (dryRunInput) {
                        dryRunInput.value = '1';
                    }
                    dryRunBtn.dataset.triggered = '1';
                    systemForm.requestSubmit();
                });
            }

            if (runNowBtn && systemForm) {
                runNowBtn.addEventListener('click', function () {
                    if (!validateIds()) {
                        (bizSel.value ? locSel : bizSel).focus();
                        return;
                    }

                    if (runNowInput) {
                        runNowInput.value = '1';
                    }
                    if (dryRunInput) {
                        dryRunInput.value = '0';
                    }
                    runNowBtn.dataset.triggered = '1';
                    systemForm.requestSubmit();
                });
            }
        })();

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
                                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('payment.close') }}">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">${html}</div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('payment.close') }}</button>
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