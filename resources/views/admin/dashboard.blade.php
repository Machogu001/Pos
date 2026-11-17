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

        <!-- Microsoft Dynamics 365 Business Central -->
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200 tw-cursor-pointer" onclick="openModal('businessCentralModal')">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full sm:tw-w-12 sm:tw-h-12 tw-shrink-0 {{ ($settings->business_central_enabled ?? false) ? 'tw-bg-green-100 tw-text-green-600' : 'tw-bg-gray-100 tw-text-gray-400' }}">
                        <i class="fab fa-microsoft"></i>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">Dynamics 365 Business Central</p>
                        <p class="tw-mt-0.5 tw-text-xs tw-text-gray-600 tw-line-clamp-2">
                            {{ ($settings->business_central_enabled ?? false) ? 'Continuous synchronization enabled' : 'Disabled - POS works independently' }}
                        </p>
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
    @php
        $systemSchedulesEnabled = collect([
            (bool) ($settings?->auto_close_register ?? false),
            (bool) ($settings?->accounting_backfill_enabled ?? false),
            (bool) ($settings?->stock_costing_backfill_enabled ?? false),
            (bool) ($settings?->top_selling_low_stock_alert_enabled ?? false),
        ])->filter()->count();
    @endphp
    <div class="modal fade" id="systemModal" tabindex="-1" aria-labelledby="systemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content sys-modal">
                <div class="modal-header sys-modal-header text-white">
                    <div class="d-flex align-items-center">
                        <span class="sys-brand-icon"><i class="fas fa-server"></i></span>
                        <div>
                            <h5 class="modal-title mb-0" id="systemModalLabel">{{ __('payment.system') }} {{ __('messages.settings') }}</h5>
                            <small class="sys-header-subtitle">Automation, maintenance schedules, and operational controls</small>
                        </div>
                    </div>
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

                    <div class="modal-body sys-modal-body">
                        <div class="sys-overview mb-3">
                            <div>
                                <h6 class="mb-1 fw-bold">System automation</h6>
                                <p class="small text-muted mb-0">Configure each operational task independently. Changes apply after saving.</p>
                            </div>
                            <div class="sys-overview-stats">
                                <span class="sys-overview-pill">
                                    <i class="fas fa-clock"></i>
                                    {{ $systemSchedulesEnabled }} of 4 schedules enabled
                                </span>
                                <span class="sys-overview-pill {{ ($settings?->auto_renewal ?? false) ? 'is-success' : '' }}">
                                    <i class="fas fa-sync-alt"></i>
                                    Auto renewal {{ ($settings?->auto_renewal ?? false) ? 'enabled' : 'disabled' }}
                                </span>
                            </div>
                        </div>

                        <div class="row sys-setup-workspace">
                            <div class="col-lg-3 mb-3 sys-setup-sidebar">
                                <div class="list-group sys-setup-nav">
                                    <a href="#sys-setup-general" class="list-group-item list-group-item-action active"
                                       onclick="navigateSystemSetup(this, 'sys-setup-general'); return false;">
                                        <span class="sys-nav-icon"><i class="fas fa-sliders-h"></i></span>
                                        <span><strong>{{ __('lang_v1.general') }}</strong><small>Renewal and display defaults</small></span>
                                    </a>
                                    <a href="#sys-setup-register" class="list-group-item list-group-item-action"
                                       onclick="navigateSystemSetup(this, 'sys-setup-register'); return false;">
                                        <span class="sys-nav-icon"><i class="fas fa-cash-register"></i></span>
                                        <span><strong>Register closing</strong><small>Daily automatic close</small></span>
                                    </a>
                                    <a href="#sys-setup-accounting" class="list-group-item list-group-item-action"
                                       onclick="navigateSystemSetup(this, 'sys-setup-accounting'); return false;">
                                        <span class="sys-nav-icon"><i class="fas fa-calculator"></i></span>
                                        <span><strong>Accounting backfill</strong><small>Ledger repair schedule</small></span>
                                    </a>
                                    <a href="#sys-setup-stock-costing" class="list-group-item list-group-item-action"
                                       onclick="navigateSystemSetup(this, 'sys-setup-stock-costing'); return false;">
                                        <span class="sys-nav-icon"><i class="fas fa-boxes"></i></span>
                                        <span><strong>Stock costing</strong><small>Costing repair schedule</small></span>
                                    </a>
                                    <a href="#sys-setup-low-stock" class="list-group-item list-group-item-action"
                                       onclick="navigateSystemSetup(this, 'sys-setup-low-stock'); return false;">
                                        <span class="sys-nav-icon"><i class="fas fa-bell"></i></span>
                                        <span><strong>Low-stock alerts</strong><small>Recipients and frequency</small></span>
                                    </a>
                                </div>
                            </div>

                            <div class="col-lg-9 sys-setup-content">

                        {{-- ── General ─────────────────────────────────────────────── --}}
                        <div class="sys-section-card sys-tab-pane sys-tab-active mb-3" id="sys-setup-general">
                            <div class="sys-section-title">
                                <i class="fas fa-sliders-h me-1"></i>{{ __('lang_v1.general') }}
                            </div>
                            <div class="sys-general-grid">
                                <div class="bc-toggle-card">
                                    <div class="bc-toggle-copy">
                                        <label class="fw-bold mb-1" for="auto_renewal">{{ __('payment.enable_auto_renewal') }}</label>
                                        <small class="text-muted d-block">Automatically renew eligible subscriptions using the configured renewal rules.</small>
                                    </div>
                                    <div class="bc-toggle-control">
                                        <input class="bc-toggle-input" type="checkbox" name="auto_renewal"
                                               id="auto_renewal" value="1"
                                               {{ ($settings?->auto_renewal ?? false) ? 'checked' : '' }}>
                                        <label class="bc-toggle-slider" for="auto_renewal">
                                            <span class="sr-only">{{ __('payment.enable_auto_renewal') }}</span>
                                        </label>
                                        <span class="bc-toggle-state" aria-hidden="true"></span>
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label mb-1 fw-medium" style="font-size:.8rem;">{{ __('payment.grace_period_days') }}</label>
                                    <input type="number" class="form-control" name="grace_period_days"
                                           value="{{ $settings?->grace_period_days ?? 7 }}" required>
                                </div>
                                <div>
                                    <label class="form-label mb-1 fw-medium" style="font-size:.8rem;">{{ __('payment.recent_records_limit') }}</label>
                                    <input type="number" min="1" max="100" class="form-control" name="recent_limit"
                                           value="{{ $settings?->recent_limit ?? 5 }}" required>
                                    <small class="text-muted" style="font-size:.72rem;">{{ __('payment.controls_how_many_recent') }}</small>
                                </div>
                            </div>
                        </div>

                        {{-- ── Scheduled Tasks ─────────────────────────────────────── --}}
                        <div class="sys-section-card sys-scheduled-shell d-none">
                            <div class="sys-section-title">
                                <i class="fas fa-clock me-1"></i>{{ __('lang_v1.scheduled_tasks') ?? 'Scheduled Tasks' }}
                            </div>
                            <div class="row g-3">

                                {{-- Card 1: Auto-close Register --}}
                                <div class="col-12 sys-tab-pane" id="sys-setup-register">
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
                                <div class="col-12 sys-tab-pane" id="sys-setup-accounting">
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
                                <div class="col-12 sys-tab-pane" id="sys-setup-stock-costing">
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
                                        <div class="sched-card__note">{{ __('payment.stock_costing_repair_note') }}</div>
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
                                                    <option value="0" {{ $settings?->stock_costing_backfill_business_id !== null && (int) $settings->stock_costing_backfill_business_id === 0 ? 'selected' : '' }}>{{ __('lang_v1.all') }} {{ \Illuminate\Support\Str::plural(__('lang_v1.business')) }}</option>
                                                    @foreach($schedulerBusinesses ?? [] as $sb)
                                                        <option value="{{ $sb->id }}" {{ (int)($settings?->stock_costing_backfill_business_id) === (int)$sb->id ? 'selected' : '' }}>{{ $sb->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label class="sched-card__lbl d-block mb-1">{{ __('lang_v1.location') }}</label>
                                                <select class="form-select form-select-sm" name="stock_costing_backfill_location_id" id="stockCostingBackfillLocationId">
                                                    <option value="">— {{ __('lang_v1.location') }} —</option>
                                                    <option value="0" {{ $settings?->stock_costing_backfill_location_id !== null && (int) $settings->stock_costing_backfill_location_id === 0 ? 'selected' : '' }}>{{ __('lang_v1.all') }} {{ \Illuminate\Support\Str::plural(__('lang_v1.location')) }}</option>
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
                                                <i class="fas fa-search me-1"></i>{{ __('payment.preview_stock_costing_repair') }}
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-success" id="runStockCostingBackfillNowBtn">
                                                <i class="fas fa-play me-1"></i>{{ __('payment.run_stock_costing_repair_now') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12 sys-tab-pane" id="sys-setup-low-stock">
                                    <input type="hidden" name="top_selling_low_stock_alert_enabled" value="0">
                                    <input type="hidden" name="top_selling_low_stock_alert_send_in_app" value="0">
                                    <input type="hidden" name="top_selling_low_stock_alert_send_email" value="0">
                                    <input type="hidden" name="top_selling_low_stock_alert_send_sms" value="0">
                                    <input type="hidden" name="top_selling_low_stock_alert_send_whatsapp" value="0">
                                    <input type="hidden" name="run_top_selling_low_stock_now" id="runTopSellingLowStockNow" value="0">
                                    <input type="hidden" name="run_top_selling_low_stock_dry_run" id="runTopSellingLowStockDryRun" value="0">
                                    <div class="sched-card h-100">
                                        <div class="sched-card__head">
                                            <div class="sched-card__icon" style="background:#fee2e2;color:#b91c1c;"><i class="fas fa-bell"></i></div>
                                            <div class="sched-card__title">{{ __('payment.top_selling_low_stock_alerts_title') }}</div>
                                            <label class="otp-switch ms-auto flex-shrink-0" title="Enable top-selling low-stock alerts">
                                                <input type="checkbox" name="top_selling_low_stock_alert_enabled" id="topSellingLowStockAlertEnabled" value="1"
                                                       {{ ($settings?->top_selling_low_stock_alert_enabled ?? false) ? 'checked' : '' }}>
                                                <span class="otp-slider"></span>
                                            </label>
                                        </div>
                                        <div class="sched-card__hint">{{ __('payment.top_selling_low_stock_alerts_hint') }}</div>
                                        <div class="sched-card__badge {{ ($settings?->top_selling_low_stock_alert_enabled ?? false) ? 'is-on' : 'is-off' }}" id="topSellingLowStockAlertStatus">
                                            {{ ($settings?->top_selling_low_stock_alert_enabled ?? false) ? __('payment.enabled') : __('payment.disabled') }}
                                        </div>
                                        <div class="sched-card__note">{{ __('payment.top_selling_low_stock_alerts_note') }}</div>
                                        <div class="sched-card__runinfo">
                                            <span><i class="far fa-check-circle me-1"></i>{{ __('payment.last_run_at') }} ({{ __('lang_v1.success') }}): <strong>{{ !empty($topSellingLowStockAlertStatus['last_run']) ? \Carbon\Carbon::parse($topSellingLowStockAlertStatus['last_run'])->format('d M H:i') : __('payment.not_available') }}</strong></span>
                                            <span><i class="far fa-clock me-1"></i>{{ __('payment.next_run_at') }}: <strong>{{ !empty($topSellingLowStockAlertStatus['next_run']) ? \Carbon\Carbon::parse($topSellingLowStockAlertStatus['next_run'])->format('d M H:i') : __('payment.not_available') }}</strong></span>
                                        </div>
                                        @if(!empty($topSellingLowStockAlertStatus['last_failed_at']) || !empty($topSellingLowStockAlertStatus['last_failure_message']))
                                            <div class="sched-card__runinfo mt-1 text-danger">
                                                <span><i class="far fa-times-circle me-1"></i>{{ __('payment.last_run_at') }} ({{ __('ui.failed') }}): <strong>{{ !empty($topSellingLowStockAlertStatus['last_failed_at']) ? \Carbon\Carbon::parse($topSellingLowStockAlertStatus['last_failed_at'])->format('d M H:i') : __('payment.not_available') }}</strong></span>
                                                <span><i class="far fa-comment-alt me-1"></i>{{ __('payment.failure_reason') }}: <strong>{{ $topSellingLowStockAlertStatus['last_failure_message'] ?? __('payment.not_available') }}</strong></span>
                                            </div>
                                        @endif
                                        <div class="row g-3 mt-1">
                                            <div class="col-lg-3 col-md-6">
                                                <label class="sched-card__lbl d-block mb-1">Frequency</label>
                                                @php $topSellingFrequency = $settings?->top_selling_low_stock_alert_frequency ?? 'every_thirty_minutes'; @endphp
                                                <select class="form-select form-select-sm" name="top_selling_low_stock_alert_frequency" id="topSellingLowStockAlertFrequency">
                                                    <option value="every_fifteen_minutes" {{ $topSellingFrequency === 'every_fifteen_minutes' ? 'selected' : '' }}>Every 15 minutes</option>
                                                    <option value="every_thirty_minutes" {{ $topSellingFrequency === 'every_thirty_minutes' ? 'selected' : '' }}>Every 30 minutes</option>
                                                    <option value="hourly" {{ $topSellingFrequency === 'hourly' ? 'selected' : '' }}>Hourly</option>
                                                    <option value="daily" {{ $topSellingFrequency === 'daily' ? 'selected' : '' }}>{{ __('payment.once_a_day') }}</option>
                                                    <option value="twice_weekly" {{ $topSellingFrequency === 'twice_weekly' ? 'selected' : '' }}>{{ __('payment.twice_a_week') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-lg-2 col-md-6" id="topSellingLowStockAlertTimeWrap" style="display:{{ in_array(($settings?->top_selling_low_stock_alert_frequency ?? 'every_thirty_minutes'), ['daily', 'twice_weekly']) ? 'block' : 'none' }};">
                                                <label class="sched-card__lbl d-block mb-1">Run at</label>
                                                <input type="time" class="form-control form-control-sm" name="top_selling_low_stock_alert_time" id="topSellingLowStockAlertTime" value="{{ $settings?->top_selling_low_stock_alert_time ?? '08:00' }}">
                                            </div>
                                            @php
                                                $topSellingWeekdays = [
                                                    0 => __('lang_v1.sunday'),
                                                    1 => __('lang_v1.monday'),
                                                    2 => __('lang_v1.tuesday'),
                                                    3 => __('lang_v1.wednesday'),
                                                    4 => __('lang_v1.thursday'),
                                                    5 => __('lang_v1.friday'),
                                                    6 => __('lang_v1.saturday'),
                                                ];
                                                $topSellingWeekdayOne = (int) ($settings?->top_selling_low_stock_alert_weekday_1 ?? 1);
                                                $topSellingWeekdayTwo = (int) ($settings?->top_selling_low_stock_alert_weekday_2 ?? 4);
                                            @endphp
                                            <div class="col-lg-2 col-md-6" id="topSellingLowStockAlertWeekdayOneWrap" style="display:{{ ($settings?->top_selling_low_stock_alert_frequency ?? 'every_thirty_minutes') === 'twice_weekly' ? 'block' : 'none' }};">
                                                <label class="sched-card__lbl d-block mb-1">{{ __('payment.day_1') }}</label>
                                                <select class="form-select form-select-sm" name="top_selling_low_stock_alert_weekday_1" id="topSellingLowStockAlertWeekdayOne">
                                                    @foreach($topSellingWeekdays as $dayValue => $dayLabel)
                                                        <option value="{{ $dayValue }}" {{ $topSellingWeekdayOne === $dayValue ? 'selected' : '' }}>{{ $dayLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-lg-2 col-md-6" id="topSellingLowStockAlertWeekdayTwoWrap" style="display:{{ ($settings?->top_selling_low_stock_alert_frequency ?? 'every_thirty_minutes') === 'twice_weekly' ? 'block' : 'none' }};">
                                                <label class="sched-card__lbl d-block mb-1">{{ __('payment.day_2') }}</label>
                                                <select class="form-select form-select-sm" name="top_selling_low_stock_alert_weekday_2" id="topSellingLowStockAlertWeekdayTwo">
                                                    @foreach($topSellingWeekdays as $dayValue => $dayLabel)
                                                        <option value="{{ $dayValue }}" {{ $topSellingWeekdayTwo === $dayValue ? 'selected' : '' }}>{{ $dayLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-lg-2 col-md-6">
                                                <label class="sched-card__lbl d-block mb-1">Lookback days</label>
                                                <input type="number" min="1" max="365" class="form-control form-control-sm" name="top_selling_low_stock_alert_days" value="{{ $settings?->top_selling_low_stock_alert_days ?? 30 }}">
                                            </div>
                                            <div class="col-lg-2 col-md-6">
                                                <label class="sched-card__lbl d-block mb-1">Top products</label>
                                                <input type="number" min="1" max="50" class="form-control form-control-sm" name="top_selling_low_stock_alert_limit" value="{{ $settings?->top_selling_low_stock_alert_limit ?? 5 }}">
                                            </div>
                                            <div class="col-lg-3 col-md-6">
                                                <label class="sched-card__lbl d-block mb-1">{{ __('payment.business_filter') }}</label>
                                                <select class="form-select form-select-sm" name="top_selling_low_stock_alert_business_id">
                                                    <option value="">{{ __('business.all') }}</option>
                                                    @foreach($schedulerBusinesses ?? [] as $sb)
                                                        <option value="{{ $sb->id }}" {{ (int)($settings?->top_selling_low_stock_alert_business_id) === (int)$sb->id ? 'selected' : '' }}>{{ $sb->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row g-3 mt-1">
                                            <div class="col-lg-3 col-md-6">
                                                <label class="form-check d-flex align-items-center gap-2 mt-2">
                                                    <input class="form-check-input" type="checkbox" name="top_selling_low_stock_alert_send_in_app" value="1" {{ ($settings?->top_selling_low_stock_alert_send_in_app ?? true) ? 'checked' : '' }}>
                                                    <span class="small fw-medium">In-app notifications</span>
                                                </label>
                                            </div>
                                            <div class="col-lg-3 col-md-6">
                                                <label class="form-check d-flex align-items-center gap-2 mt-2">
                                                    <input class="form-check-input" type="checkbox" name="top_selling_low_stock_alert_send_email" value="1" {{ ($settings?->top_selling_low_stock_alert_send_email ?? false) ? 'checked' : '' }}>
                                                    <span class="small fw-medium">Email alerts</span>
                                                </label>
                                            </div>
                                            <div class="col-lg-3 col-md-6">
                                                <label class="form-check d-flex align-items-center gap-2 mt-2">
                                                    <input class="form-check-input" type="checkbox" name="top_selling_low_stock_alert_send_sms" value="1" {{ ($settings?->top_selling_low_stock_alert_send_sms ?? false) ? 'checked' : '' }}>
                                                    <span class="small fw-medium">SMS alerts</span>
                                                </label>
                                            </div>
                                            <div class="col-lg-3 col-md-6">
                                                <label class="form-check d-flex align-items-center gap-2 mt-2">
                                                    <input class="form-check-input" type="checkbox" name="top_selling_low_stock_alert_send_whatsapp" value="1" {{ ($settings?->top_selling_low_stock_alert_send_whatsapp ?? false) ? 'checked' : '' }}>
                                                    <span class="small fw-medium">WhatsApp webhook</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="row g-3 mt-1">
                                            <div class="col-lg-6">
                                                <label class="sched-card__lbl d-block mb-1">{{ __('payment.extra_email_recipients') }}</label>
                                                <textarea class="form-control form-control-sm" rows="2" name="top_selling_low_stock_alert_custom_emails" placeholder="ops@example.com, owner@example.com">{{ $settings?->top_selling_low_stock_alert_custom_emails ?? '' }}</textarea>
                                            </div>
                                            <div class="col-lg-6">
                                                <label class="sched-card__lbl d-block mb-1">{{ __('payment.extra_phone_recipients') }}</label>
                                                <textarea class="form-control form-control-sm" rows="2" name="top_selling_low_stock_alert_custom_phones" placeholder="2547XXXXXXXX, 2547YYYYYYYY">{{ $settings?->top_selling_low_stock_alert_custom_phones ?? '' }}</textarea>
                                            </div>
                                        </div>
                                        <div class="row g-3 mt-1">
                                            <div class="col-lg-4">
                                                <label class="sched-card__lbl d-block mb-1">{{ __('payment.whatsapp_webhook_url_label') }}</label>
                                                <input type="url" class="form-control form-control-sm" name="top_selling_low_stock_alert_whatsapp_webhook_url" value="{{ $settings?->top_selling_low_stock_alert_whatsapp_webhook_url ?? '' }}" placeholder="https://your-whatsapp-gateway/send">
                                            </div>
                                            <div class="col-lg-2">
                                                <label class="sched-card__lbl d-block mb-1">{{ __('payment.auth_header') }}</label>
                                                <input type="text" class="form-control form-control-sm" name="top_selling_low_stock_alert_whatsapp_auth_header" value="{{ $settings?->top_selling_low_stock_alert_whatsapp_auth_header ?? '' }}" placeholder="Authorization">
                                            </div>
                                            <div class="col-lg-3">
                                                <label class="sched-card__lbl d-block mb-1">{{ __('payment.auth_token') }}</label>
                                                <input type="text" class="form-control form-control-sm" name="top_selling_low_stock_alert_whatsapp_auth_token" value="{{ $settings?->top_selling_low_stock_alert_whatsapp_auth_token ?? '' }}" placeholder="Bearer ...">
                                            </div>
                                            <div class="col-lg-1 col-md-6">
                                                <label class="sched-card__lbl d-block mb-1">{{ __('payment.phone_key') }}</label>
                                                <input type="text" class="form-control form-control-sm" name="top_selling_low_stock_alert_whatsapp_phone_param" value="{{ $settings?->top_selling_low_stock_alert_whatsapp_phone_param ?? 'phone' }}">
                                            </div>
                                            <div class="col-lg-2 col-md-6">
                                                <label class="sched-card__lbl d-block mb-1">{{ __('payment.message_key') }}</label>
                                                <input type="text" class="form-control form-control-sm" name="top_selling_low_stock_alert_whatsapp_message_param" value="{{ $settings?->top_selling_low_stock_alert_whatsapp_message_param ?? 'message' }}">
                                            </div>
                                        </div>
                                        <div class="sched-card__row mt-2">
                                            <button type="button" class="btn btn-sm btn-outline-secondary" id="runTopSellingLowStockDryRunBtn">
                                                <i class="fas fa-search me-1"></i>{{ __('payment.preview_low_stock_alerts') }}
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" id="runTopSellingLowStockNowBtn">
                                                <i class="fas fa-play me-1"></i>{{ __('payment.send_low_stock_alerts_now') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>{{-- /row --}}
                        </div>{{-- /sys-section-card --}}

                            </div>{{-- /sys-setup-content --}}
                        </div>{{-- /sys-setup-workspace --}}
                    </div>{{-- /modal-body --}}

                    <div class="modal-footer sys-modal-footer">
                        <button type="button" class="btn btn-light border me-auto" data-dismiss="modal">{{ __('payment.close') }}</button>
                        <button type="submit" class="btn btn-info px-4">
                            <span class="btn-text">{{ __('payment.update_settings') }}</span>
                            <span class="btn-loading d-none"><span class="spinner-border spinner-border-sm" role="status"></span></span>
                        </button>
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
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content etims-modal">
                <div class="modal-header etims-modal-header text-white">
                    <div class="d-flex align-items-center">
                        <span class="etims-brand-icon"><i class="fas fa-exchange-alt"></i></span>
                        <div>
                            <h5 class="modal-title mb-0" id="etimsModalLabel">{{ __('payment.etims_integration_settings') }}</h5>
                            <small class="etims-header-subtitle">Tax transmission connection, document rules, and tax-code guidance</small>
                        </div>
                    </div>
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
                    <input type="hidden" name="etims_auto_transmit" value="0">
                    <input type="hidden" name="etims_transmit_subscriptions" value="0">
                    <input type="hidden" name="etims_transmit_registrations" value="0">
                    
                    <div class="modal-body etims-modal-body">
                        @php
                            $etimsConfiguredFields = collect([
                                $settings?->etims_api_url,
                                $settings?->etims_api_token,
                                $settings?->etims_branch_id,
                            ])->filter(fn ($value) => !empty($value))->count();
                            $etimsReadiness = (int) round(($etimsConfiguredFields / 3) * 100);
                        @endphp
                        <div class="etims-overview mb-3">
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <h6 class="mb-0 fw-bold">{{ __('payment.configure_etims_api_integration') }}</h6>
                                    <span class="etims-readiness-value">{{ $etimsReadiness }}%</span>
                                </div>
                                <p class="small text-muted mb-0 mt-1">{{ __('payment.etims_integration_help') }}</p>
                            </div>
                            <span class="etims-state-pill {{ $etimsReadiness === 100 ? 'is-ready' : '' }}">
                                <i class="fas {{ $etimsReadiness === 100 ? 'fa-check-circle' : 'fa-tools' }}"></i>
                                {{ $etimsReadiness === 100 ? 'Connection configured' : 'Setup incomplete' }}
                            </span>
                        </div>
                        <div class="progress etims-readiness-progress mb-3">
                            <div class="progress-bar" role="progressbar" style="width: {{ $etimsReadiness }}%;"
                                 aria-valuenow="{{ $etimsReadiness }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>

                        <div class="row etims-setup-workspace">
                            <div class="col-lg-3 mb-3 etims-setup-sidebar">
                                <div class="list-group etims-setup-nav">
                                    <a href="#etims-setup-connection" class="list-group-item list-group-item-action active"
                                       onclick="navigateEtimsSetup(this, 'etims-setup-connection'); return false;">
                                        <span class="etims-nav-icon"><i class="fas fa-plug"></i></span>
                                        <span><strong>Connection</strong><small>API, token, and branch</small></span>
                                    </a>
                                    <a href="#etims-setup-transmission" class="list-group-item list-group-item-action"
                                       onclick="navigateEtimsSetup(this, 'etims-setup-transmission'); return false;">
                                        <span class="etims-nav-icon"><i class="fas fa-paper-plane"></i></span>
                                        <span><strong>Transmission rules</strong><small>Choose documents to send</small></span>
                                    </a>
                                    <a href="#etims-setup-tax-codes" class="list-group-item list-group-item-action"
                                       onclick="navigateEtimsSetup(this, 'etims-setup-tax-codes'); return false;">
                                        <span class="etims-nav-icon"><i class="fas fa-percent"></i></span>
                                        <span><strong>Tax codes</strong><small>Reference and guidance</small></span>
                                    </a>
                                </div>
                            </div>

                            <div class="col-lg-9 etims-setup-content">
                                <div class="etims-tab-pane etims-tab-active" id="etims-setup-connection">
                                    <div class="etims-section-card">
                                        <div class="etims-section-heading">
                                            <span><i class="fas fa-plug"></i></span>
                                            <div>
                                                <h6 class="mb-0 fw-bold">Connect to the eTIMS service</h6>
                                                <small class="text-muted">Enter the credentials supplied by your eTIMS integration provider.</small>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-medium">{{ __('payment.etims_api_url_label') }}</label>
                                            <input type="url" class="form-control" name="etims_api_url"
                                                   value="{{ $settings?->etims_api_url ?? '' }}"
                                                   placeholder="{{ __('payment.etims_api_url_placeholder') }}">
                                            <small class="text-muted">{{ __('payment.etims_api_url_help') }}</small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-medium">{{ __('payment.api_token') }}</label>
                                            <textarea class="form-control" name="etims_api_token" rows="3"
                                                      placeholder="{{ __('payment.etims_api_token_placeholder') }}">{{ $settings?->etims_api_token ?? '' }}</textarea>
                                            <small class="text-muted">{{ __('payment.etims_api_token_help') }}</small>
                                        </div>
                                        <div>
                                            <label class="form-label fw-medium">{{ __('payment.branch_id') }}</label>
                                            <input type="text" class="form-control" name="etims_branch_id"
                                                   value="{{ $settings?->etims_branch_id ?? '' }}"
                                                   placeholder="{{ __('payment.etims_branch_id_placeholder') }}" maxlength="10">
                                            <small class="text-muted">{{ __('payment.etims_branch_id_help') }}</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="etims-tab-pane" id="etims-setup-transmission">
                                    <div class="etims-section-card">
                                        <div class="etims-section-heading">
                                            <span><i class="fas fa-paper-plane"></i></span>
                                            <div>
                                                <h6 class="mb-0 fw-bold">Choose what POS sends automatically</h6>
                                                <small class="text-muted">Green switches are enabled; gray switches are disabled.</small>
                                            </div>
                                        </div>
                                        <div class="bc-toggle-card mb-3">
                                            <div class="bc-toggle-copy">
                                                <label class="fw-bold mb-1" for="etimsAutoTransmit">{{ __('payment.automatically_transmit_product_sales') }}</label>
                                                <small class="text-muted d-block">{{ __('payment.auto_transmit_products_help') }}</small>
                                            </div>
                                            <div class="bc-toggle-control">
                                                <input class="bc-toggle-input" type="checkbox" name="etims_auto_transmit"
                                                       id="etimsAutoTransmit" value="1"
                                                       {{ ($settings?->etims_auto_transmit ?? false) ? 'checked' : '' }}>
                                                <label class="bc-toggle-slider" for="etimsAutoTransmit"><span class="sr-only">{{ __('payment.auto_transmit_products') }}</span></label>
                                                <span class="bc-toggle-state" aria-hidden="true"></span>
                                            </div>
                                        </div>
                                        <div class="bc-toggle-card mb-3">
                                            <div class="bc-toggle-copy">
                                                <label class="fw-bold mb-1" for="etimsTransmitSubscriptions">{{ __('payment.include_subscription_invoices') }}</label>
                                                <small class="text-muted d-block">{{ __('payment.transmit_subscriptions_help') }}</small>
                                            </div>
                                            <div class="bc-toggle-control">
                                                <input class="bc-toggle-input" type="checkbox" name="etims_transmit_subscriptions"
                                                       id="etimsTransmitSubscriptions" value="1"
                                                       {{ ($settings?->etims_transmit_subscriptions ?? false) ? 'checked' : '' }}>
                                                <label class="bc-toggle-slider" for="etimsTransmitSubscriptions"><span class="sr-only">{{ __('payment.transmit_subscriptions') }}</span></label>
                                                <span class="bc-toggle-state" aria-hidden="true"></span>
                                            </div>
                                        </div>
                                        <div class="bc-toggle-card">
                                            <div class="bc-toggle-copy">
                                                <label class="fw-bold mb-1" for="etimsTransmitRegistrations">{{ __('payment.include_registration_payments') }}</label>
                                                <small class="text-muted d-block">{{ __('payment.transmit_registrations_help') }}</small>
                                            </div>
                                            <div class="bc-toggle-control">
                                                <input class="bc-toggle-input" type="checkbox" name="etims_transmit_registrations"
                                                       id="etimsTransmitRegistrations" value="1"
                                                       {{ ($settings?->etims_transmit_registrations ?? false) ? 'checked' : '' }}>
                                                <label class="bc-toggle-slider" for="etimsTransmitRegistrations"><span class="sr-only">{{ __('payment.transmit_registrations') }}</span></label>
                                                <span class="bc-toggle-state" aria-hidden="true"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="etims-tab-pane" id="etims-setup-tax-codes">
                                    <div class="etims-section-card">
                                        <div class="etims-section-heading">
                                            <span><i class="fas fa-percent"></i></span>
                                            <div>
                                                <h6 class="mb-0 fw-bold">{{ __('payment.tax_code_information') }}</h6>
                                                <small class="text-muted">Use the correct tax category when configuring products and services.</small>
                                            </div>
                                        </div>
                                        <div class="etims-tax-code-list">
                                            <div><span class="etims-tax-badge">A</span><p>{{ __('payment.tax_code_a_description') }}</p></div>
                                            <div><span class="etims-tax-badge">B</span><p>{{ __('payment.tax_code_b_description') }}</p></div>
                                            <div><span class="etims-tax-badge">E</span><p>{{ __('payment.tax_code_e_description') }}</p></div>
                                        </div>
                                        <div class="alert alert-warning mb-0 mt-3">
                                            <i class="fas fa-exclamation-triangle me-1"></i>
                                            {{ __('payment.tax_code_summary_note') }}
                                        </div>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer etims-modal-footer">
                        <button type="button" class="btn btn-light border me-auto" data-dismiss="modal">{{ __('payment.close') }}</button>
                        <button type="submit" class="btn btn-success px-4">
                            <span class="btn-text">
                                <i class="fas fa-save me-2"></i>{{ __('payment.save_etims_settings') }}
                            </span>
                            <span class="btn-loading d-none">
                                <span class="spinner-border spinner-border-sm" role="status"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Microsoft Dynamics 365 Business Central Modal -->
    @php
        $bcLocationMappings = $businessCentralLocationMappings ?? collect();
        $bcLocationTotal = ($schedulerLocations ?? collect())->count();
        $bcLocationMapped = ($schedulerLocations ?? collect())->filter(function ($location) use ($bcLocationMappings) {
            return !empty($bcLocationMappings->get($location->id)?->enabled);
        })->count();
        $bcUserTotal = ($businessCentralUsers ?? collect())->count();
        $bcUserMapped = ($businessCentralSalespeople ?? collect())->whereNotNull('pos_user_id')->count();
        $bcReadinessChecks = [
            !empty($settings?->business_central_api_url) && !empty($settings?->business_central_auth_type),
            !empty($settings?->business_central_company_id),
            $bcLocationTotal > 0 && $bcLocationMapped > 0,
            $bcUserTotal > 0 && $bcUserMapped === $bcUserTotal,
        ];
        $bcReadinessPercent = (int) round((collect($bcReadinessChecks)->filter()->count() / count($bcReadinessChecks)) * 100);
    @endphp
    <div class="modal fade" id="businessCentralModal" tabindex="-1" aria-labelledby="businessCentralModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content bc-modal">
                <div class="modal-header bc-modal-header text-white">
                    <div class="d-flex align-items-center">
                        <span class="bc-brand-icon"><i class="fab fa-microsoft"></i></span>
                        <div>
                            <h5 class="modal-title mb-0" id="businessCentralModalLabel">Dynamics 365 Business Central</h5>
                            <small class="bc-header-subtitle">Inventory, sales, customers, vendors, and financial documents</small>
                        </div>
                        <span class="badge bc-version-badge ms-3">Dynamics 365 Business Central</span>
                    </div>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('admin.settings.update') }}" method="POST" class="settings-form"
                      onsubmit="return handleModalFormSubmit(event, this, 'businessCentralModal');">
                    @csrf
                    <input type="hidden" name="monthly_price" value="{{ $settings?->monthly_price ?? 0 }}">
                    <input type="hidden" name="quarterly_price" value="{{ $settings?->quarterly_price ?? 0 }}">
                    <input type="hidden" name="yearly_price" value="{{ $settings?->yearly_price ?? 0 }}">
                    <input type="hidden" name="grace_period_days" value="{{ $settings?->grace_period_days ?? 7 }}">
                    <input type="hidden" name="recent_limit" value="{{ $settings?->recent_limit ?? 5 }}">
                    <input type="hidden" name="business_central_enabled" value="0">
                    <input type="hidden" name="business_central_require_sale_sync" value="0">

                    <div class="modal-body bc-modal-body">
                        <div class="bc-readiness-panel mb-3">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="mb-0 fw-bold">Setup readiness</h6>
                                        <span class="bc-readiness-value">{{ $bcReadinessPercent }}%</span>
                                    </div>
                                    <p class="text-muted small mb-0 mt-1">Complete the numbered sections, save your settings, then run synchronization.</p>
                                </div>
                                <span class="bc-state-pill {{ ($settings?->business_central_enabled ?? false) ? 'is-enabled' : 'is-disabled' }}">
                                    <i class="fas {{ ($settings?->business_central_enabled ?? false) ? 'fa-check-circle' : 'fa-pause-circle' }}"></i>
                                    {{ ($settings?->business_central_enabled ?? false) ? 'Integration enabled' : 'Integration disabled' }}
                                </span>
                            </div>
                            <div class="progress bc-readiness-progress mt-3">
                                <div class="progress-bar" role="progressbar" style="width: {{ $bcReadinessPercent }}%;"
                                     aria-valuenow="{{ $bcReadinessPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div class="row g-2 mt-2">
                                <div class="col-6 col-lg-3">
                                    <div class="bc-check-tile {{ $bcReadinessChecks[0] ? 'is-complete' : '' }}">
                                        <i class="fas {{ $bcReadinessChecks[0] ? 'fa-check-circle' : 'fa-plug' }}"></i>
                                        <span><strong>Connection</strong><small>{{ $bcReadinessChecks[0] ? 'Configured' : 'Needs setup' }}</small></span>
                                    </div>
                                </div>
                                <div class="col-6 col-lg-3">
                                    <div class="bc-check-tile {{ $bcReadinessChecks[1] ? 'is-complete' : '' }}">
                                        <i class="fas {{ $bcReadinessChecks[1] ? 'fa-check-circle' : 'fa-building' }}"></i>
                                        <span><strong>Company</strong><small>{{ $bcReadinessChecks[1] ? ($settings?->business_central_company_name ?: 'Selected') : 'Not selected' }}</small></span>
                                    </div>
                                </div>
                                <div class="col-6 col-lg-3">
                                    <div class="bc-check-tile {{ $bcReadinessChecks[2] ? 'is-complete' : '' }}">
                                        <i class="fas {{ $bcReadinessChecks[2] ? 'fa-check-circle' : 'fa-map-marker-alt' }}"></i>
                                        <span><strong>Locations</strong><small>{{ $bcLocationMapped }} of {{ $bcLocationTotal }} enabled</small></span>
                                    </div>
                                </div>
                                <div class="col-6 col-lg-3">
                                    <div class="bc-check-tile {{ $bcReadinessChecks[3] ? 'is-complete' : '' }}">
                                        <i class="fas {{ $bcReadinessChecks[3] ? 'fa-check-circle' : 'fa-user-tag' }}"></i>
                                        <span><strong>Salespeople</strong><small>{{ $bcUserMapped }} of {{ $bcUserTotal }} mapped</small></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 bc-sync-summary mb-3">
                            <div>
                                <strong><i class="fas fa-history me-1 text-primary"></i> Synchronization status</strong>
                                <span class="text-muted small ms-1">
                                    {{ !empty($settings?->business_central_last_sync_at) ? 'Last successful sync: '.$settings->business_central_last_sync_at : 'No successful synchronization recorded yet.' }}
                                </span>
                            </div>
                        </div>

                        @if(!empty($settings?->business_central_last_error))
                            <div class="alert alert-danger d-flex align-items-start">
                                <i class="fas fa-exclamation-circle mt-1 me-2"></i>
                                <div><strong>Last synchronization error</strong><br>{{ $settings->business_central_last_error }}</div>
                            </div>
                        @endif

                        <div class="row bc-setup-workspace">
                            <div class="col-lg-3 mb-3 bc-setup-sidebar">
                                <div class="list-group bc-setup-nav">
                                    <a href="#bc-setup-behavior" class="list-group-item list-group-item-action active"
                                       onclick="navigateBusinessCentralSetup(this, 'bc-setup-behavior'); return false;">
                                        <span class="bc-nav-number">1</span><span><strong>Integration mode</strong><small>Business and sale policy</small></span>
                                    </a>
                                    <a href="#bc-setup-connection" class="list-group-item list-group-item-action"
                                       onclick="navigateBusinessCentralSetup(this, 'bc-setup-connection'); return false;">
                                        <span class="bc-nav-number">2</span><span><strong>Connection</strong><small>API and authentication</small></span>
                                    </a>
                                    <a href="#bc-setup-services" class="list-group-item list-group-item-action"
                                       onclick="navigateBusinessCentralSetup(this, 'bc-setup-services'); return false;">
                                        <span class="bc-nav-number">3</span><span><strong>BC services</strong><small>Company and endpoints</small></span>
                                    </a>
                                    <a href="#bc-setup-locations" class="list-group-item list-group-item-action"
                                       onclick="navigateBusinessCentralSetup(this, 'bc-setup-locations'); return false;">
                                        <span class="bc-nav-number">4</span><span><strong>Locations</strong><small>Branch mapping</small></span>
                                    </a>
                                    <a href="#bc-setup-salespeople" class="list-group-item list-group-item-action"
                                       onclick="navigateBusinessCentralSetup(this, 'bc-setup-salespeople'); return false;">
                                        <span class="bc-nav-number">5</span><span><strong>Salespeople</strong><small>User mapping</small></span>
                                    </a>
                                    <a href="#bc-setup-invoices" class="list-group-item list-group-item-action"
                                       onclick="navigateBusinessCentralSetup(this, 'bc-setup-invoices'); return false;">
                                        <span class="bc-nav-number">6</span><span><strong>Invoice reports</strong><small>Registered and walk-in</small></span>
                                    </a>
                                </div>
                                <div class="alert alert-info small mt-3 mb-0">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Business Central supplies master data and stock. POS sends finalized sales back to BC.
                                </div>
                            </div>

                            <div class="col-lg-9 bc-setup-content">
                                <div class="card bc-step-card bc-step-active mb-3" id="bc-setup-behavior">
                                    <div class="card-header bg-white d-flex align-items-center">
                                        <span class="badge bg-primary rounded-circle me-2">1</span>
                                        <div>
                                            <h6 class="mb-0 fw-bold">Choose the integration mode</h6>
                                            <small class="text-muted">Select the POS business and decide how sales behave when BC is unavailable.</small>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">POS business</label>
                                            <select class="form-control" name="business_central_business_id" id="businessCentralBusiness">
                                                <option value="">Select the business to connect</option>
                                                @foreach(($schedulerBusinesses ?? collect()) as $business)
                                                    <option value="{{ $business->id }}" {{ (int) ($settings?->business_central_business_id ?? 0) === (int) $business->id ? 'selected' : '' }}>
                                                        {{ $business->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="bc-toggle-card mb-3">
                                            <div class="bc-toggle-copy">
                                                <label class="fw-bold mb-1" for="businessCentralEnabled">Use Business Central as the inventory system of record</label>
                                                <small class="text-muted d-block">When disabled, POS continues to work independently and no BC synchronization runs.</small>
                                            </div>
                                            <div class="bc-toggle-control">
                                                <input class="bc-toggle-input" type="checkbox" name="business_central_enabled"
                                                       id="businessCentralEnabled" value="1"
                                                       {{ ($settings?->business_central_enabled ?? false) ? 'checked' : '' }}>
                                                <label class="bc-toggle-slider" for="businessCentralEnabled">
                                                    <span class="sr-only">Toggle Business Central integration</span>
                                                </label>
                                                <span class="bc-toggle-state" aria-hidden="true"></span>
                                            </div>
                                        </div>
                                        <div class="bc-toggle-card">
                                            <div class="bc-toggle-copy">
                                                <label class="fw-bold mb-1" for="businessCentralRequireSaleSync">Require BC posting before completing a POS sale</label>
                                                <small class="text-muted d-block">
                                                    <strong>On:</strong> a BC error cancels the sale. <strong>Off (recommended):</strong> POS saves the sale and retries BC automatically.
                                                </small>
                                            </div>
                                            <div class="bc-toggle-control">
                                                <input class="bc-toggle-input" type="checkbox"
                                                       name="business_central_require_sale_sync"
                                                       id="businessCentralRequireSaleSync" value="1"
                                                       {{ ($settings?->business_central_require_sale_sync ?? false) ? 'checked' : '' }}>
                                                <label class="bc-toggle-slider" for="businessCentralRequireSaleSync">
                                                    <span class="sr-only">Toggle required BC posting</span>
                                                </label>
                                                <span class="bc-toggle-state" aria-hidden="true"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card bc-step-card mb-3" id="bc-setup-connection">
                                    <div class="card-header bg-white d-flex align-items-center">
                                        <span class="badge bg-primary rounded-circle me-2">2</span>
                                        <div>
                                            <h6 class="mb-0 fw-bold">Enter connection and sign-in details</h6>
                                            <small class="text-muted">These credentials are used to load companies and synchronize data.</small>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label fw-bold">Standard API v2.0 URL</label>
                                                <input type="url" class="form-control" name="business_central_api_url"
                                                       value="{{ $settings?->business_central_api_url ?? '' }}"
                                                       placeholder="https://bc-server:7048/{instance}/api/v2.0">
                                                <small class="text-muted">Base URL only; do not add a company ID or resource name.</small>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold">Authentication method</label>
                                                <select class="form-control" name="business_central_auth_type" id="businessCentralAuthType"
                                                        onchange="toggleBusinessCentralAuthFields()">
                                                    @foreach(['basic' => 'Basic / Web Service Access Key', 'oauth2' => 'OAuth 2.0 client credentials', 'bearer' => 'Bearer token'] as $value => $label)
                                                        <option value="{{ $value }}" {{ ($settings?->business_central_auth_type ?? 'basic') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4 bc-auth-basic">
                                                <label class="form-label fw-bold">Username</label>
                                                <input type="text" class="form-control" name="business_central_username"
                                                       value="{{ $settings?->business_central_username ?? '' }}" autocomplete="off">
                                                <small class="text-muted">Required for Basic authentication.</small>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label fw-bold" id="businessCentralSecretLabel">Password, secret, or token</label>
                                                <input type="password" class="form-control" name="business_central_secret"
                                                       placeholder="{{ !empty($settings?->business_central_secret) ? 'Leave blank to keep current secret' : 'Enter credential' }}"
                                                       autocomplete="new-password">
                                            </div>
                                            <div class="col-md-6 bc-auth-oauth">
                                                <label class="form-label">OAuth token URL <span class="text-muted">(OAuth only)</span></label>
                                                <input type="url" class="form-control" name="business_central_token_url"
                                                       value="{{ $settings?->business_central_token_url ?? '' }}"
                                                       placeholder="https://login.microsoftonline.com/{tenant}/oauth2/v2.0/token">
                                            </div>
                                            <div class="col-md-3 bc-auth-oauth">
                                                <label class="form-label">OAuth client ID</label>
                                                <input type="text" class="form-control" name="business_central_client_id"
                                                       value="{{ $settings?->business_central_client_id ?? '' }}">
                                            </div>
                                            <div class="col-md-3 bc-auth-oauth">
                                                <label class="form-label">OAuth scope</label>
                                                <input type="text" class="form-control" name="business_central_scope"
                                                       value="{{ $settings?->business_central_scope ?? '' }}"
                                                       placeholder="https://api.businesscentral.dynamics.com/.default">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card bc-step-card mb-3" id="bc-setup-services">
                                    <div class="card-header bg-white d-flex align-items-center">
                                        <span class="badge bg-primary rounded-circle me-2">3</span>
                                        <div>
                                            <h6 class="mb-0 fw-bold">Select the BC company and configure services</h6>
                                            <small class="text-muted">Load companies after completing steps 1 and 2.</small>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Business Central company</label>
                                            <div class="input-group">
                                                <select class="form-control" name="business_central_company_id" id="businessCentralCompany"
                                                        onchange="syncBusinessCentralCompanyName(this)">
                                                    <option value="">Load and select a company</option>
                                                    @if(!empty($settings?->business_central_company_id))
                                                        <option value="{{ $settings->business_central_company_id }}" selected>
                                                            {{ $settings?->business_central_company_name ?: 'Saved Business Central company' }}
                                                        </option>
                                                    @endif
                                                </select>
                                                <input type="hidden" name="business_central_company_name" id="businessCentralCompanyName"
                                                       value="{{ $settings?->business_central_company_name ?? '' }}">
                                                <span class="input-group-btn">
                                                    <button type="button" class="btn btn-outline-primary" id="loadBusinessCentralCompanies"
                                                            onclick="loadBusinessCentralCompanies(this)">
                                                        <i class="fas fa-cloud-download-alt me-1"></i>Load companies
                                                    </button>
                                                </span>
                                            </div>
                                            <small class="text-muted" id="businessCentralCompanyStatus">Enter connection details, then load companies.</small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Inventory API collection URL</label>
                                            <input type="url" class="form-control" name="business_central_inventory_url"
                                                   value="{{ $settings?->business_central_inventory_url ?? '' }}"
                                                   placeholder="https://bc-server:7048/{instance}/api/bremac/pos/v1.0/companies({company_id})/posInventoryEntries">
                                            <small class="text-muted">Endpoint used to import Business Central inventory quantities into POS.</small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Salespeople OData collection URL <span class="badge bg-light text-dark border">Optional</span></label>
                                            <input type="url" class="form-control" name="business_central_salespeople_url"
                                                   value="{{ $settings?->business_central_salespeople_url ?? '' }}"
                                                   placeholder="https://bc-server:7048/{instance}/ODataV4/Company('COMPANY')/SalespeoplePurchasers">
                                            <small class="text-muted">Only needed when the standard API does not expose salespeoplePurchasers.</small>
                                        </div>
                                        <div>
                                            <label class="form-label fw-bold">BreMac POS sales codeunit URL <span class="badge bg-light text-dark border">Recommended</span></label>
                                            <input type="url" class="form-control" name="business_central_sales_codeunit_url"
                                                   value="{{ $settings?->business_central_sales_codeunit_url ?? '' }}"
                                                   placeholder="https://bc-server:7047/{instance}/WS/COMPANY/Codeunit/BreMacPOSSalesIntegration">
                                            <small class="text-muted">Required for location-aware Business Central sale posting and configured BC invoice reports.</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="card bc-step-card mb-3" id="bc-setup-locations">
                                    <div class="card-header bg-white d-flex align-items-center">
                                        <span class="badge bg-primary rounded-circle me-2">4</span>
                                        <div>
                                            <h6 class="mb-0 fw-bold">Map POS branches to BC locations</h6>
                                            <small class="text-muted">Enable each branch that should synchronize and enter its BC location code.</small>
                                        </div>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover table-sm align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="ps-3" style="width: 80px;">Sync</th>
                                                        <th>POS business</th>
                                                        <th>POS branch</th>
                                                        <th>BC location code</th>
                                                        <th>Resolved BC location ID</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse(($schedulerLocations ?? collect()) as $location)
                                                        @php($bcLocationMapping = ($businessCentralLocationMappings ?? collect())->get($location->id))
                                                        @php($locationBusiness = ($schedulerBusinesses ?? collect())->firstWhere('id', $location->business_id))
                                                        <tr>
                                                            <td class="ps-3">
                                                                <input type="hidden" name="business_central_location_mappings[{{ $location->id }}][enabled]" value="0">
                                                                <input type="checkbox" class="form-check-input"
                                                                       name="business_central_location_mappings[{{ $location->id }}][enabled]" value="1"
                                                                       aria-label="Synchronize {{ $location->name }}"
                                                                       {{ !empty($bcLocationMapping?->enabled) ? 'checked' : '' }}>
                                                            </td>
                                                            <td>{{ $locationBusiness?->name ?? $location->business_id }}</td>
                                                            <td class="fw-medium">{{ $location->name }}</td>
                                                            <td>
                                                                <input type="text" class="form-control form-control-sm"
                                                                       name="business_central_location_mappings[{{ $location->id }}][bc_location_code]"
                                                                       value="{{ $bcLocationMapping?->bc_location_code ?? '' }}"
                                                                       placeholder="e.g. MAIN">
                                                            </td>
                                                            <td class="small text-muted">{{ $bcLocationMapping?->bc_location_id ?? 'Resolved on first sync' }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr><td colspan="5" class="text-center text-muted py-4">No POS locations are available.</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="card bc-step-card mb-3" id="bc-setup-salespeople">
                                    <div class="card-header bg-white d-flex align-items-center">
                                        <span class="badge bg-primary rounded-circle me-2">5</span>
                                        <div>
                                            <h6 class="mb-0 fw-bold">Map POS users to BC salespeople</h6>
                                            <small class="text-muted">Each user who finalizes sales must have an active BC salesperson.</small>
                                        </div>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover table-sm align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="ps-3">POS user</th>
                                                        <th>Username</th>
                                                        <th>Business Central salesperson</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse(($businessCentralUsers ?? collect()) as $posUser)
                                                        @php($assignedSalesperson = ($businessCentralSalespeople ?? collect())->firstWhere('pos_user_id', $posUser->id))
                                                        <tr>
                                                            <td class="ps-3 fw-medium">{{ $posUser->user_full_name ?: $posUser->username }}</td>
                                                            <td>{{ $posUser->username }}</td>
                                                            <td>
                                                                <select class="form-control form-control-sm"
                                                                        name="business_central_salesperson_mappings[{{ $posUser->id }}]">
                                                                    <option value="">Not mapped</option>
                                                                    @foreach(($businessCentralSalespeople ?? collect()) as $salesperson)
                                                                        <option value="{{ $salesperson->id }}"
                                                                            {{ (int) ($assignedSalesperson?->id ?? 0) === (int) $salesperson->id ? 'selected' : '' }}
                                                                            {{ $salesperson->blocked ? 'disabled' : '' }}>
                                                                            {{ $salesperson->display_name }} ({{ $salesperson->code }}){{ $salesperson->blocked ? ' - blocked' : '' }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="3" class="text-center text-muted py-4">
                                                                Save and sync first to import BC salespeople, then return here to map POS users.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-light small">
                                        <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                                        Sales completed by an unmapped user remain in POS, but BC synchronization fails until the user is mapped and the sale is retried.
                                    </div>
                                </div>

                                <div class="card bc-step-card mb-0" id="bc-setup-invoices">
                                    <div class="card-header bg-white d-flex align-items-center">
                                        <span class="badge bg-primary rounded-circle me-2">6</span>
                                        <div>
                                            <h6 class="mb-0 fw-bold">Configure BC invoice reports</h6>
                                            <small class="text-muted">Leave either value blank to use the documented fallback behavior.</small>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold">Registered customer report ID</label>
                                                <input type="number" min="1" class="form-control" name="business_central_invoice_report_id"
                                                       value="{{ $settings?->business_central_invoice_report_id ?? '' }}"
                                                       placeholder="50100">
                                                <small class="text-muted">Blank uses the BC S.Invoice report selection.</small>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold">Unregistered customer report ID</label>
                                                <input type="number" min="1" class="form-control" name="business_central_unregistered_invoice_report_id"
                                                       value="{{ $settings?->business_central_unregistered_invoice_report_id ?? '' }}"
                                                       placeholder="50101">
                                                <small class="text-muted">Blank uses the registered customer report for everyone.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bc-modal-footer">
                        <button type="button" class="btn btn-light border me-auto" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-outline-primary" name="run_business_central_sync_now" value="1">
                            <span class="btn-text"><i class="fas fa-sync me-2"></i>Save and sync now</span>
                            <span class="btn-loading d-none"><span class="spinner-border spinner-border-sm"></span></span>
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <span class="btn-text"><i class="fas fa-save me-2"></i>Save settings</span>
                            <span class="btn-loading d-none"><span class="spinner-border spinner-border-sm"></span></span>
                        </button>
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
        /* eTIMS setup */
        .etims-modal {
            border: 0;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 28px 70px rgba(15, 23, 42, .28);
        }
        .etims-modal-header {
            border: 0;
            padding: 16px 20px;
            background: linear-gradient(120deg, #14532d 0%, #15803d 55%, #0f766e 100%);
        }
        .etims-brand-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            margin-right: 12px;
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 12px;
            background: rgba(255, 255, 255, .13);
            font-size: 1.1rem;
        }
        .etims-header-subtitle {
            color: rgba(255, 255, 255, .75);
        }
        .etims-modal-body {
            background: #f3f6fa;
        }
        .etims-overview {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 16px 18px;
            border: 1px solid #dce5ef;
            border-radius: 14px;
            background: linear-gradient(135deg, #fff 0%, #f0fdf4 100%);
            box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
        }
        .etims-readiness-value {
            padding: 3px 9px;
            border-radius: 999px;
            background: #dcfce7;
            color: #166534;
            font-size: .75rem;
            font-weight: 800;
        }
        .etims-state-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 12px;
            border-radius: 999px;
            background: #e2e8f0;
            color: #475569;
            font-size: .8rem;
            font-weight: 700;
        }
        .etims-state-pill.is-ready {
            background: #dcfce7;
            color: #166534;
        }
        .etims-readiness-progress {
            height: 7px;
            border-radius: 999px;
            background: #e2e8f0;
        }
        .etims-readiness-progress .progress-bar {
            border-radius: inherit;
            background: linear-gradient(90deg, #16a34a 0%, #0f766e 100%);
        }
        .etims-setup-sidebar {
            align-self: flex-start;
        }
        .etims-setup-nav {
            overflow: hidden;
            border: 1px solid #dce5ef;
            border-radius: 12px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .06);
        }
        .etims-setup-nav .list-group-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 12px;
            border-right: 0;
            border-left: 3px solid transparent;
            color: #475569;
        }
        .etims-setup-nav .list-group-item strong,
        .etims-setup-nav .list-group-item small {
            display: block;
        }
        .etims-setup-nav .list-group-item strong {
            font-size: .82rem;
        }
        .etims-setup-nav .list-group-item small {
            margin-top: 1px;
            color: #94a3b8;
            font-size: .68rem;
        }
        .etims-setup-nav .list-group-item.active {
            border-color: #16a34a;
            background: #f0fdf4;
            color: #166534;
        }
        .etims-nav-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 30px;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: #e2e8f0;
            color: #475569;
            font-size: .75rem;
        }
        .etims-setup-nav .active .etims-nav-icon {
            background: #16a34a;
            color: #fff;
        }
        .etims-setup-content {
            overflow-y: auto;
            max-height: calc(100vh - 340px);
            padding-right: 8px;
        }
        .etims-tab-pane {
            display: none;
        }
        .etims-tab-pane.etims-tab-active {
            display: block;
            animation: etimsTabEnter .18s ease-out;
        }
        .etims-section-card {
            padding: 18px;
            border: 1px solid #dce5ef;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 7px 20px rgba(15, 23, 42, .05);
        }
        .etims-section-heading {
            display: flex;
            align-items: center;
            gap: 11px;
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid #e8eef5;
        }
        .etims-section-heading > span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #dcfce7;
            color: #15803d;
        }
        .etims-section-card .form-control {
            border-color: #cbd5e1;
            border-radius: 8px;
        }
        .etims-section-card .form-control:focus {
            border-color: #16a34a;
            box-shadow: 0 0 0 3px rgba(22, 163, 74, .12);
        }
        .etims-tax-code-list {
            display: grid;
            gap: 10px;
        }
        .etims-tax-code-list > div {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 11px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
        }
        .etims-tax-code-list p {
            margin: 0;
            color: #475569;
            font-size: .82rem;
        }
        .etims-tax-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 30px;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: #16a34a;
            color: #fff;
            font-size: .78rem;
            font-weight: 800;
        }
        .etims-modal-footer {
            border-top: 1px solid #dce5ef;
            background: #fff;
            box-shadow: 0 -8px 22px rgba(15, 23, 42, .04);
        }
        @keyframes etimsTabEnter {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 991.98px) {
            .etims-setup-nav {
                display: grid;
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
            .etims-setup-nav .list-group-item {
                border-left-width: 0;
            }
            .etims-setup-content {
                overflow: visible;
                max-height: none;
                padding-right: 15px;
            }
        }
        @media (max-width: 575.98px) {
            .etims-overview {
                align-items: flex-start;
                flex-direction: column;
            }
            .etims-setup-nav {
                grid-template-columns: 1fr;
            }
        }

        /* Business Central setup */
        .bc-modal {
            border: 0;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 28px 70px rgba(15, 23, 42, .28);
        }
        .bc-modal-header {
            border: 0;
            padding: 16px 20px;
            background: linear-gradient(120deg, #0f3b67 0%, #075985 55%, #0f766e 100%);
        }
        .bc-brand-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            margin-right: 12px;
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 12px;
            background: rgba(255, 255, 255, .13);
            font-size: 1.25rem;
        }
        .bc-header-subtitle {
            color: rgba(255, 255, 255, .75);
        }
        .bc-version-badge {
            border: 1px solid rgba(255, 255, 255, .24);
            background: rgba(255, 255, 255, .14);
            color: #fff;
        }
        .bc-modal-body {
            background: #f3f6fa;
        }
        .bc-readiness-panel {
            padding: 18px;
            border: 1px solid #dce5ef;
            border-radius: 14px;
            background: linear-gradient(135deg, #fff 0%, #f7fbff 100%);
            box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
        }
        .bc-readiness-value {
            padding: 3px 9px;
            border-radius: 999px;
            background: #e0f2fe;
            color: #075985;
            font-size: .75rem;
            font-weight: 800;
        }
        .bc-state-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: .8rem;
            font-weight: 700;
        }
        .bc-state-pill.is-enabled {
            background: #dcfce7;
            color: #166534;
        }
        .bc-state-pill.is-disabled {
            background: #e2e8f0;
            color: #475569;
        }
        .bc-readiness-progress {
            height: 7px;
            border-radius: 999px;
            background: #e2e8f0;
        }
        .bc-readiness-progress .progress-bar {
            border-radius: inherit;
            background: linear-gradient(90deg, #0284c7 0%, #0f766e 100%);
        }
        .bc-check-tile {
            display: flex;
            align-items: center;
            gap: 9px;
            min-height: 58px;
            padding: 9px 11px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #fff;
            color: #64748b;
        }
        .bc-check-tile > i {
            width: 18px;
            text-align: center;
        }
        .bc-check-tile span,
        .bc-check-tile small {
            display: block;
            min-width: 0;
        }
        .bc-check-tile strong {
            display: block;
            color: #334155;
            font-size: .78rem;
        }
        .bc-check-tile small {
            overflow: hidden;
            max-width: 145px;
            color: #64748b;
            font-size: .7rem;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .bc-check-tile.is-complete {
            border-color: #bbf7d0;
            background: #f0fdf4;
            color: #16a34a;
        }
        .bc-sync-summary {
            padding: 10px 14px;
            border: 1px solid #dbeafe;
            border-radius: 10px;
            background: #eff6ff;
        }
        .bc-toggle-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 15px 16px;
            border: 1px solid #dce5ef;
            border-radius: 11px;
            background: linear-gradient(180deg, #fff 0%, #f8fafc 100%);
        }
        .bc-toggle-copy {
            flex: 1;
            min-width: 0;
        }
        .bc-toggle-copy label {
            color: #1e293b;
            cursor: pointer;
        }
        .bc-toggle-control {
            display: flex;
            flex: 0 0 auto;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }
        .bc-toggle-input {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
        }
        .bc-toggle-slider {
            position: relative;
            display: block;
            width: 54px;
            height: 30px;
            margin: 0;
            border: 1px solid #94a3b8;
            border-radius: 999px;
            background: #cbd5e1;
            cursor: pointer;
            transition: background-color .2s ease, border-color .2s ease, box-shadow .2s ease;
        }
        .bc-toggle-slider::after {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 2px 5px rgba(15, 23, 42, .28);
            content: "";
            transition: transform .2s ease;
        }
        .bc-toggle-input:checked + .bc-toggle-slider {
            border-color: #16a34a;
            background: #22c55e;
        }
        .bc-toggle-input:checked + .bc-toggle-slider::after {
            transform: translateX(24px);
        }
        .bc-toggle-input:focus-visible + .bc-toggle-slider {
            box-shadow: 0 0 0 4px rgba(2, 132, 199, .18);
        }
        .bc-toggle-state {
            color: #64748b;
            font-size: .66rem;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
        }
        .bc-toggle-state::before {
            content: "Disabled";
        }
        .bc-toggle-input:checked + .bc-toggle-slider + .bc-toggle-state {
            color: #15803d;
        }
        .bc-toggle-input:checked + .bc-toggle-slider + .bc-toggle-state::before {
            content: "Enabled";
        }
        .bc-setup-nav {
            overflow: hidden;
            border: 1px solid #dce5ef;
            border-radius: 12px;
            box-shadow: 0 8px 22px rgba(15, 23, 42, .06);
        }
        .bc-setup-nav .list-group-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            border-right: 0;
            border-left: 3px solid transparent;
            color: #475569;
        }
        .bc-setup-nav .list-group-item strong,
        .bc-setup-nav .list-group-item small {
            display: block;
        }
        .bc-setup-nav .list-group-item strong {
            font-size: .82rem;
        }
        .bc-setup-nav .list-group-item small {
            margin-top: 1px;
            color: #94a3b8;
            font-size: .68rem;
        }
        .bc-setup-nav .list-group-item.active {
            border-color: #0284c7;
            background: #eff6ff;
            color: #075985;
        }
        .bc-nav-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 28px;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: #e2e8f0;
            color: #475569;
            font-size: .75rem;
            font-weight: 800;
        }
        .bc-setup-nav .active .bc-nav-number {
            background: #0284c7;
            color: #fff;
        }
        .bc-step-card {
            display: none;
            scroll-margin-top: 12px;
            border: 1px solid #dce5ef;
            border-radius: 12px;
            box-shadow: 0 7px 20px rgba(15, 23, 42, .05);
        }
        .bc-step-card.bc-step-active {
            display: block;
            animation: bcStepEnter .18s ease-out;
        }
        .bc-setup-content {
            overflow-y: auto;
            max-height: calc(100vh - 390px);
            padding-right: 8px;
        }
        .bc-setup-sidebar {
            align-self: flex-start;
        }
        @keyframes bcStepEnter {
            from {
                opacity: 0;
                transform: translateY(4px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .bc-step-card > .card-header {
            padding: 14px 16px;
            border-bottom-color: #e8eef5;
            border-radius: 12px 12px 0 0;
        }
        .bc-step-card .form-control {
            border-color: #cbd5e1;
            border-radius: 8px;
            min-height: 38px;
        }
        .bc-step-card .form-control:focus {
            border-color: #0284c7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, .12);
        }
        .bc-step-card table thead th {
            border-top: 0;
            color: #64748b;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .035em;
        }
        .bc-modal-footer {
            border-top: 1px solid #dce5ef;
            background: #fff;
            box-shadow: 0 -8px 22px rgba(15, 23, 42, .04);
        }
        @media (max-width: 991.98px) {
            .bc-setup-nav {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .bc-setup-nav .list-group-item {
                border-left-width: 0;
            }
            .bc-setup-content {
                overflow: visible;
                max-height: none;
                padding-right: 15px;
            }
        }
        @media (max-width: 575.98px) {
            .bc-toggle-card {
                align-items: flex-start;
            }
        }

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
            .sys-modal {
                border: 0;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 28px 70px rgba(15, 23, 42, .28);
            }
            .sys-modal-header {
                border: 0;
                padding: 16px 20px;
                background: linear-gradient(120deg, #0f172a 0%, #155e75 58%, #0f766e 100%);
            }
            .sys-brand-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 42px;
                height: 42px;
                margin-right: 12px;
                border: 1px solid rgba(255, 255, 255, .2);
                border-radius: 12px;
                background: rgba(255, 255, 255, .13);
                font-size: 1.1rem;
            }
            .sys-header-subtitle {
                color: rgba(255, 255, 255, .75);
            }
            .sys-modal-body {
                background: #f3f6fa;
            }
            .sys-overview {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 16px;
                padding: 16px 18px;
                border: 1px solid #dce5ef;
                border-radius: 14px;
                background: linear-gradient(135deg, #fff 0%, #f0fdfa 100%);
                box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
            }
            .sys-overview-stats {
                display: flex;
                flex-wrap: wrap;
                justify-content: flex-end;
                gap: 8px;
            }
            .sys-overview-pill {
                display: inline-flex;
                align-items: center;
                gap: 7px;
                padding: 7px 10px;
                border-radius: 999px;
                background: #e2e8f0;
                color: #475569;
                font-size: .75rem;
                font-weight: 700;
            }
            .sys-overview-pill.is-success {
                background: #dcfce7;
                color: #166534;
            }
            .sys-setup-sidebar {
                align-self: flex-start;
            }
            .sys-setup-nav {
                overflow: hidden;
                border: 1px solid #dce5ef;
                border-radius: 12px;
                box-shadow: 0 8px 22px rgba(15, 23, 42, .06);
            }
            .sys-setup-nav .list-group-item {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 12px;
                border-right: 0;
                border-left: 3px solid transparent;
                color: #475569;
            }
            .sys-setup-nav .list-group-item strong,
            .sys-setup-nav .list-group-item small {
                display: block;
            }
            .sys-setup-nav .list-group-item strong {
                font-size: .82rem;
            }
            .sys-setup-nav .list-group-item small {
                margin-top: 1px;
                color: #94a3b8;
                font-size: .68rem;
            }
            .sys-setup-nav .list-group-item.active {
                border-color: #0891b2;
                background: #ecfeff;
                color: #155e75;
            }
            .sys-nav-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex: 0 0 30px;
                width: 30px;
                height: 30px;
                border-radius: 8px;
                background: #e2e8f0;
                color: #475569;
                font-size: .75rem;
            }
            .sys-setup-nav .active .sys-nav-icon {
                background: #0891b2;
                color: #fff;
            }
            .sys-setup-content {
                overflow-y: auto;
                max-height: calc(100vh - 330px);
                padding-right: 8px;
            }
            .sys-tab-pane {
                display: none;
            }
            .sys-tab-pane.sys-tab-active {
                display: block;
                animation: sysTabEnter .18s ease-out;
            }
            .sys-general-grid {
                display: grid;
                grid-template-columns: minmax(0, 2fr) minmax(150px, .7fr) minmax(180px, .9fr);
                gap: 14px;
                align-items: start;
            }
            .sys-modal-footer {
                border-top: 1px solid #dce5ef;
                background: #fff;
                box-shadow: 0 -8px 22px rgba(15, 23, 42, .04);
            }
            @keyframes sysTabEnter {
                from { opacity: 0; transform: translateY(4px); }
                to { opacity: 1; transform: translateY(0); }
            }
            .sys-section-card {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 16px;
                box-shadow: 0 7px 20px rgba(15, 23, 42, .05);
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
            @media (max-width: 991.98px) {
                .sys-setup-nav {
                    display: grid;
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
                .sys-setup-nav .list-group-item {
                    border-left-width: 0;
                }
                .sys-setup-content {
                    overflow: visible;
                    max-height: none;
                    padding-right: 15px;
                }
                .sys-general-grid {
                    grid-template-columns: 1fr;
                }
            }
            @media (max-width: 575.98px) {
                .sys-overview {
                    align-items: flex-start;
                    flex-direction: column;
                }
                .sys-overview-stats {
                    justify-content: flex-start;
                }
            }
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

        document.getElementById('topSellingLowStockAlertEnabled')?.addEventListener('change', function () {
            var badge = document.getElementById('topSellingLowStockAlertStatus');
            if (!badge) return;
            if (this.checked) {
                badge.textContent = '{{ __('payment.enabled') }}';
                badge.classList.replace('is-off', 'is-on');
            } else {
                badge.textContent = '{{ __('payment.disabled') }}';
                badge.classList.replace('is-on', 'is-off');
            }
        });

        document.getElementById('topSellingLowStockAlertFrequency')?.addEventListener('change', function () {
            var timeWrap = document.getElementById('topSellingLowStockAlertTimeWrap');
            var weekdayOneWrap = document.getElementById('topSellingLowStockAlertWeekdayOneWrap');
            var weekdayTwoWrap = document.getElementById('topSellingLowStockAlertWeekdayTwoWrap');
            var showTime = this.value === 'daily' || this.value === 'twice_weekly';
            var showWeekdays = this.value === 'twice_weekly';

            if (timeWrap) {
                timeWrap.style.display = showTime ? 'block' : 'none';
            }

            if (weekdayOneWrap) {
                weekdayOneWrap.style.display = showWeekdays ? 'block' : 'none';
            }

            if (weekdayTwoWrap) {
                weekdayTwoWrap.style.display = showWeekdays ? 'block' : 'none';
            }
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
                return { value: o.value, text: o.text, biz: o.dataset.business, isAll: o.value === '0' };
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
                    if (opt.isAll || !bizId || bizId === '0' || opt.biz === bizId) {
                        var o = document.createElement('option');
                        o.value = opt.value; o.textContent = opt.text;
                        if (opt.biz) o.dataset.business = opt.biz;
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

        (function () {
            var runNowInput = document.getElementById('runTopSellingLowStockNow');
            var dryRunInput = document.getElementById('runTopSellingLowStockDryRun');
            var runNowBtn = document.getElementById('runTopSellingLowStockNowBtn');
            var dryRunBtn = document.getElementById('runTopSellingLowStockDryRunBtn');
            var systemForm = document.querySelector('#systemModal form');
            if (!systemForm) return;

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

            if (dryRunBtn) {
                dryRunBtn.addEventListener('click', function () {
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

            if (runNowBtn) {
                runNowBtn.addEventListener('click', function () {
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

            if (modalId === 'businessCentralModal') {
                toggleBusinessCentralAuthFields();
            }
            
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

        function navigateBusinessCentralSetup(link, sectionId) {
            document.querySelectorAll('#businessCentralModal .bc-setup-nav .list-group-item').forEach(function(item) {
                item.classList.remove('active');
            });
            link.classList.add('active');

            document.querySelectorAll('#businessCentralModal .bc-step-card').forEach(function(section) {
                section.classList.remove('bc-step-active');
            });
            document.getElementById(sectionId)?.classList.add('bc-step-active');

            const content = document.querySelector('#businessCentralModal .bc-setup-content');
            if (content) {
                content.scrollTop = 0;
            }
        }

        function navigateSystemSetup(link, sectionId) {
            document.querySelectorAll('#systemModal .sys-setup-nav .list-group-item').forEach(function(item) {
                item.classList.remove('active');
            });
            link.classList.add('active');

            document.querySelectorAll('#systemModal .sys-tab-pane').forEach(function(section) {
                section.classList.remove('sys-tab-active');
            });

            const scheduledShell = document.querySelector('#systemModal .sys-scheduled-shell');
            const isGeneral = sectionId === 'sys-setup-general';
            scheduledShell?.classList.toggle('d-none', isGeneral);
            document.getElementById(sectionId)?.classList.add('sys-tab-active');

            const content = document.querySelector('#systemModal .sys-setup-content');
            if (content) {
                content.scrollTop = 0;
            }
        }

        function navigateEtimsSetup(link, sectionId) {
            document.querySelectorAll('#etimsModal .etims-setup-nav .list-group-item').forEach(function(item) {
                item.classList.remove('active');
            });
            link.classList.add('active');

            document.querySelectorAll('#etimsModal .etims-tab-pane').forEach(function(section) {
                section.classList.remove('etims-tab-active');
            });
            document.getElementById(sectionId)?.classList.add('etims-tab-active');

            const content = document.querySelector('#etimsModal .etims-setup-content');
            if (content) {
                content.scrollTop = 0;
            }
        }

        function toggleBusinessCentralAuthFields() {
            const authType = document.getElementById('businessCentralAuthType')?.value || 'basic';
            document.querySelectorAll('#businessCentralModal .bc-auth-basic').forEach(function(field) {
                field.classList.toggle('d-none', authType !== 'basic');
            });
            document.querySelectorAll('#businessCentralModal .bc-auth-oauth').forEach(function(field) {
                field.classList.toggle('d-none', authType !== 'oauth2');
            });

            const secretLabel = document.getElementById('businessCentralSecretLabel');
            if (secretLabel) {
                secretLabel.textContent = authType === 'oauth2'
                    ? 'OAuth client secret'
                    : (authType === 'bearer' ? 'Bearer token' : 'Password / Web Service Access Key');
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
            if (event.submitter && event.submitter.name) {
                formData.set(event.submitter.name, event.submitter.value);
            }
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
            
            // Show loading state
            const submitBtn = event.submitter instanceof HTMLButtonElement
                ? event.submitter
                : form.querySelector('button[type="submit"]');
            const submitButtons = Array.from(form.querySelectorAll('button[type="submit"]'));
            const submitButtonStates = submitButtons.map(button => button.disabled);
            const btnText = submitBtn?.querySelector('.btn-text');
            const btnLoading = submitBtn?.querySelector('.btn-loading');

            if (!submitBtn) {
                return false;
            }
            
            if (btnText && btnLoading) {
                btnText.classList.add('d-none');
                btnLoading.classList.remove('d-none');
            }
            submitButtons.forEach(button => {
                button.disabled = true;
            });
            
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
                    submitButtons.forEach((button, index) => {
                        button.disabled = submitButtonStates[index];
                    });
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
                    const toastType = data.toast_type || 'success';
                    if (typeof showToast === 'function') {
                        showToast(toastType, data.message || 'Settings updated successfully!');
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
                submitButtons.forEach((button, index) => {
                    button.disabled = submitButtonStates[index];
                });
            });
            
            return false;
        }

        async function loadBusinessCentralCompanies(button) {
            const form = button.closest('form');
            const select = form?.querySelector('#businessCentralCompany');
            const status = form?.querySelector('#businessCentralCompanyStatus');
            if (!form || !select || !status) {
                return;
            }

            const originalHtml = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Loading';
            status.textContent = 'Connecting to Business Central...';

            try {
                const formData = new FormData(form);
                const response = await fetch('{{ route('admin.settings.business-central.companies') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    const validationMessage = data.errors
                        ? Object.values(data.errors).flat()[0]
                        : null;
                    throw new Error(validationMessage || data.message || 'Could not load Business Central companies.');
                }

                const selectedCompanyId = select.value;
                select.replaceChildren(new Option('Select a Business Central company', ''));
                data.companies.forEach(company => {
                    select.add(new Option(company.label, company.id, false, company.id === selectedCompanyId));
                });

                if (data.companies.length === 1) {
                    select.value = data.companies[0].id;
                } else if (!data.companies.some(company => company.id === selectedCompanyId)) {
                    select.value = '';
                }
                syncBusinessCentralCompanyName(select);

                status.textContent = data.message;
                if (typeof showToast === 'function') {
                    showToast(data.companies.length ? 'success' : 'warning', data.message);
                }
            } catch (error) {
                status.textContent = error.message;
                if (typeof showToast === 'function') {
                    showToast('error', error.message);
                }
            } finally {
                button.disabled = false;
                button.innerHTML = originalHtml;
            }
        }

        function syncBusinessCentralCompanyName(select) {
            const nameInput = select.closest('form')?.querySelector('#businessCentralCompanyName');
            if (!nameInput) {
                return;
            }

            const selectedOption = select.options[select.selectedIndex];
            nameInput.value = select.value && selectedOption ? selectedOption.text.trim() : '';
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
                        background: icon === 'success' ? '#10b981' : (icon === 'info' ? '#2563eb' : '#ef4444'),
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
                    <i class="fas fa-${type === 'success' ? 'check-circle' : (type === 'info' ? 'info-circle' : 'exclamation-triangle')} me-2"></i>
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