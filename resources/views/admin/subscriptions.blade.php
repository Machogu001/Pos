@extends('layouts.app')

@section('title', __('payment.subscription_management'))

@section('content')
@php
    $isSuperadminAdminPanel = request()->routeIs('superadmin.admin.*');
    $dashboardRoute = $isSuperadminAdminPanel ? 'superadmin.admin.dashboard' : 'admin.dashboard';
    $subscriptionsIndexRoute = $isSuperadminAdminPanel ? 'superadmin.admin.subscriptions' : 'admin.subscriptions';
    $subscriptionManualRoute = $isSuperadminAdminPanel ? 'superadmin.admin.subscriptions.manual' : 'admin.subscriptions.manual';
    $subscriptionRenewRoute = $isSuperadminAdminPanel ? 'superadmin.admin.subscriptions.renew' : 'admin.subscriptions.renew';
    $subscriptionActivateRoute = $isSuperadminAdminPanel ? 'superadmin.admin.subscriptions.activate' : 'admin.subscriptions.activate';
    $subscriptionDestroyRoute = $isSuperadminAdminPanel ? 'superadmin.admin.subscriptions.destroy' : 'admin.subscriptions.destroy';
    $subscriptionShowRoute = $isSuperadminAdminPanel ? 'superadmin.admin.subscriptions.show' : 'admin.subscriptions.show';
    $subscriptionDownloadStatementsRoute = $isSuperadminAdminPanel ? 'superadmin.admin.subscriptions.download_statements' : 'admin.subscriptions.download_statements';
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
            <span class="tw-font-medium tw-text-gray-700">{{ __('payment.subscription_management') }}</span>
        </div>
    </section>

    <section class="content">
@endif

<div class="dashboard-wrapper" style="max-width: 100%; overflow-x: hidden; padding: 1.5rem; margin: 0 auto;">

    <div class="tw-mb-5 tw-overflow-hidden tw-rounded-2xl tw-border tw-border-slate-200 tw-bg-white" style="box-shadow: 0 20px 46px rgba(15, 23, 42, 0.10);">
        <div class="tw-grid tw-grid-cols-1 lg:tw-grid-cols-[minmax(0,1fr)_220px]">
            <div class="tw-p-5 md:tw-p-6" style="background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 52%, #2563eb 100%);">
                <div class="sm:tw-flex sm:tw-items-start sm:tw-justify-between sm:tw-gap-6">
                    <div class="tw-max-w-2xl">
                        <span class="tw-inline-flex tw-items-center tw-gap-2 tw-rounded-full tw-px-3 tw-py-1 tw-text-xs tw-font-semibold tw-uppercase tw-tracking-[0.18em] tw-text-white" style="background: rgba(255,255,255,0.16); border: 1px solid rgba(255,255,255,0.20);">
                            <i class="fas fa-receipt"></i>
                            Billing overview
                        </span>
                        <h1 class="tw-mt-3 tw-text-2xl tw-font-semibold tw-tracking-tight tw-text-white md:tw-text-3xl">
                            {{ __('payment.subscription_management') }}
                        </h1>
                        <p class="tw-mt-2 tw-max-w-2xl tw-text-sm tw-leading-6 tw-text-white" style="opacity: 0.94;">
                            Monitor subscription status, pending renewals, receipts, and current revenue signals in one place.
                        </p>
                    </div>
                    <div class="tw-mt-4 sm:tw-mt-0">
                        <a href="{{ route($dashboardRoute) }}"
                           class="tw-inline-flex tw-items-center tw-justify-center tw-gap-2 tw-rounded-xl tw-bg-white tw-px-4 tw-py-2.5 tw-text-sm tw-font-semibold tw-text-slate-900 tw-shadow-sm hover:tw-bg-slate-100">
                            <i class="fas fa-arrow-left"></i> {{ __('payment.back_to_dashboard') }}
                        </a>
                    </div>
                </div>
            </div>
            <div class="tw-flex tw-bg-slate-50 tw-p-4 tw-gap-3">
                <div class="tw-flex tw-flex-1 tw-items-center tw-justify-center tw-rounded-xl tw-bg-white tw-px-4 tw-py-3 tw-ring-1 tw-ring-slate-200">
                    <span class="tw-inline-flex tw-items-center tw-gap-2">
                        <span class="tw-text-xs tw-font-semibold tw-uppercase tw-tracking-[0.16em] tw-text-slate-500">{{ __('payment.active') }}</span>
                        <span class="tw-text-lg tw-font-semibold tw-leading-none tw-text-slate-900">{{ $subscriptionSummary['active'] ?? 0 }}</span>
                    </span>
                </div>
                <div class="tw-flex tw-flex-1 tw-items-center tw-justify-center tw-rounded-xl tw-bg-white tw-px-4 tw-py-3 tw-ring-1 tw-ring-slate-200">
                    <span class="tw-inline-flex tw-items-center tw-gap-2">
                        <span class="tw-text-xs tw-font-semibold tw-uppercase tw-tracking-[0.16em] tw-text-slate-500">{{ __('payment.pending') }}</span>
                        <span class="tw-text-lg tw-font-semibold tw-leading-none tw-text-slate-900">{{ $subscriptionSummary['pending'] ?? 0 }}</span>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <div id="ajaxAlerts"></div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Modal: Download Range for Selected Subscriptions -->
    <div class="modal fade" id="downloadRangeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="downloadRangeForm" method="POST" action="{{ route($subscriptionDownloadStatementsRoute) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Download Statements (Selected)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="subscription_ids" id="download_subscription_ids">
                        <div class="mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start" class="form-control" title="Start date (dd/mm/yyyy)">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end" class="form-control" title="End date (dd/mm/yyyy)">
                        </div>
                        <div class="form-text text-muted">If left empty, each subscription's start/end will be used. Format: dd/mm/yyyy</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Download ZIP</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="tw-grid tw-grid-cols-1 tw-gap-4 sm:tw-grid-cols-2 xl:tw-grid-cols-4 sm:tw-gap-5 tw-mb-5">
        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-12 tw-h-12 tw-rounded-full tw-shrink-0 tw-bg-green-100 tw-text-green-600">
                        <i class="fas fa-check-circle fa-lg"></i>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">Active Subscriptions</p>
                        <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            {{ $subscriptionSummary['active'] ?? 0 }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-12 tw-h-12 tw-rounded-full tw-shrink-0 tw-bg-yellow-100 tw-text-yellow-600">
                        <i class="fas fa-clock fa-lg"></i>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">Pending Payments</p>
                        <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            {{ $subscriptionSummary['pending'] ?? 0 }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-12 tw-h-12 tw-rounded-full tw-shrink-0 tw-bg-gray-100 tw-text-gray-600">
                        <i class="fas fa-calendar-times fa-lg"></i>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">Expired</p>
                        <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            {{ $subscriptionSummary['expired'] ?? 0 }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl hover:tw--translate-y-0.5 tw-ring-1 tw-ring-gray-200">
            <div class="tw-p-4 sm:tw-p-5">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-12 tw-h-12 tw-rounded-full tw-shrink-0 tw-bg-blue-100 tw-text-blue-600">
                        <i class="fas fa-dollar-sign fa-lg"></i>
                    </div>
                    <div class="tw-flex-1 tw-min-w-0">
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500 tw-truncate">Monthly Revenue</p>
                        <p class="tw-mt-0.5 tw-text-gray-900 tw-text-2xl tw-truncate tw-font-semibold tw-tracking-tight tw-font-mono">
                            Ksh {{ number_format($subscriptionSummary['monthly_revenue'] ?? 0, 0) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tw-overflow-hidden tw-rounded-3xl tw-border tw-border-slate-200 tw-bg-white tw-shadow-sm">
        <div class="tw-border-b tw-border-slate-200 tw-bg-slate-50/70 tw-p-4 sm:tw-p-5">
            <div class="tw-rounded-2xl tw-bg-white tw-p-4 sm:tw-p-5 tw-shadow-sm tw-ring-1 tw-ring-slate-200">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-8">
                        <div class="tw-flex tw-flex-wrap tw-items-center tw-gap-3">
                            <span class="tw-inline-flex tw-items-center tw-gap-2 tw-rounded-full tw-bg-slate-900 tw-px-3 tw-py-1 tw-text-[10px] tw-font-semibold tw-uppercase tw-tracking-[0.18em] tw-text-white">
                                <i class="fas fa-wallet"></i>
                                Subscription ledger
                            </span>
                            <div class="tw-flex tw-items-center tw-gap-2 tw-text-xs tw-font-semibold tw-uppercase tw-tracking-[0.16em] tw-text-slate-500">
                                <span class="tw-inline-flex tw-items-center tw-gap-2 tw-rounded-xl tw-bg-slate-100 tw-px-3 tw-py-2">
                                    <span>Visible</span>
                                    <span class="tw-text-base tw-font-semibold tw-normal-case tw-tracking-normal tw-text-slate-900">{{ $subscriptions->count() }}</span>
                                </span>
                                <span class="tw-inline-flex tw-items-center tw-gap-2 tw-rounded-xl tw-bg-slate-100 tw-px-3 tw-py-2">
                                    <span>Total</span>
                                    <span class="tw-text-base tw-font-semibold tw-normal-case tw-tracking-normal tw-text-slate-900">{{ $subscriptions->total() ?? $subscriptions->count() }}</span>
                                </span>
                            </div>
                        </div>
                        <div class="tw-mt-3">
                            <h2 class="tw-mb-1 tw-text-xl tw-font-semibold tw-tracking-tight tw-text-slate-900">{{ __('payment.all_payments') }}</h2>
                            <p class="tw-mb-0 tw-text-sm tw-leading-6 tw-text-slate-600">
                                {{ __('payment.showing_x_of_y', ['count' => $subscriptions->count(), 'total' => $subscriptions->total() ?? $subscriptions->count()]) }}. Review payment status, billing cycles, receipts, and pending invoice links from one workspace.
                            </p>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="tw-flex tw-flex-wrap tw-gap-2 lg:tw-justify-end">
                            <button type="button" id="openBulkActionModal" class="btn tw-inline-flex tw-items-center tw-justify-center tw-gap-2 tw-rounded-xl tw-border-0 tw-px-4 tw-py-2.5 tw-text-sm tw-font-semibold tw-text-white" style="background: linear-gradient(135deg, #334155 0%, #0f172a 100%); box-shadow: 0 10px 24px rgba(15, 23, 42, 0.18);">
                                <i class="fas fa-tasks"></i> Bulk Actions
                            </button>
                            <button type="button" id="openCreateManualModal" class="btn tw-inline-flex tw-items-center tw-justify-center tw-gap-2 tw-rounded-xl tw-border-0 tw-px-4 tw-py-2.5 tw-text-sm tw-font-semibold tw-text-white" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%); box-shadow: 0 10px 24px rgba(16, 185, 129, 0.22);">
                                <i class="fas fa-plus"></i> Create Manual
                            </button>
                        </div>
                    </div>
                </div>

                <form id="filterForm" method="GET" action="{{ route($subscriptionsIndexRoute) }}" class="tw-mt-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3 col-lg-3">
                            <label class="tw-mb-1.5 tw-block tw-text-[11px] tw-font-semibold tw-uppercase tw-tracking-[0.16em] tw-text-slate-500">{{ __('payment.status_filter') }}</label>
                            <select class="form-select tw-h-[44px] tw-rounded-xl tw-border-slate-300 tw-bg-white" name="status" id="statusFilter">
                                <option value="">{{ __('payment.all_statuses') }}</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>{{ __('payment.active') }}</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>{{ __('payment.pending') }}</option>
                                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>{{ __('payment.expired') }}</option>
                                <option value="canceled" {{ request('status') == 'canceled' ? 'selected' : '' }}>{{ __('payment.canceled') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3 col-lg-3">
                            <label class="tw-mb-1.5 tw-block tw-text-[11px] tw-font-semibold tw-uppercase tw-tracking-[0.16em] tw-text-slate-500">{{ __('payment.billing_cycle_filter') }}</label>
                            <select class="form-select tw-h-[44px] tw-rounded-xl tw-border-slate-300 tw-bg-white" name="billing_cycle" id="billingCycleFilter">
                                <option value="">{{ __('payment.all_cycles') }}</option>
                                <option value="monthly" {{ request('billing_cycle') == 'monthly' ? 'selected' : '' }}>{{ __('payment.monthly') }}</option>
                                <option value="quarterly" {{ request('billing_cycle') == 'quarterly' ? 'selected' : '' }}>{{ __('payment.quarterly') }}</option>
                                <option value="yearly" {{ request('billing_cycle') == 'yearly' ? 'selected' : '' }}>{{ __('payment.yearly') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="input-group">
                                <span class="input-group-text tw-h-[44px] tw-rounded-l-xl tw-border-slate-300 tw-bg-slate-100 tw-pe-3 tw-text-[11px] tw-font-semibold tw-uppercase tw-tracking-[0.16em] tw-text-slate-500">Search</span>
                                <span class="input-group-text tw-h-[44px] tw-border-slate-300 tw-bg-white tw-px-3 tw-text-slate-400"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control tw-h-[44px] tw-rounded-r-xl tw-border-slate-300 tw-bg-white" name="search" placeholder="{{ __('payment.search_placeholder') }}" id="searchInput" value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-12 col-lg-2">
                            <div class="tw-flex tw-flex-wrap tw-gap-2 lg:tw-justify-end">
                                <button class="btn btn-primary tw-inline-flex tw-h-[44px] tw-items-center tw-justify-center tw-gap-2 tw-rounded-xl tw-border-0 tw-px-4 tw-text-sm tw-font-semibold tw-text-white" style="background: linear-gradient(135deg, #0f172a 0%, #2563eb 100%); box-shadow: 0 10px 24px rgba(37, 99, 235, 0.24);" type="submit" id="searchButton">
                                    <i class="fas fa-sliders-h"></i> Apply
                                </button>
                                <button type="button" class="btn tw-inline-flex tw-h-[44px] tw-items-center tw-justify-center tw-gap-2 tw-rounded-xl tw-border-0 tw-px-4 tw-text-sm tw-font-semibold tw-text-slate-900" style="background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%); box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.45);" id="resetFilters">
                                    <i class="fas fa-rotate-left"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="tw-p-5 sm:tw-p-6">

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th><input type="checkbox" id="selectAllSubscriptions"></th>
                            <th>{{ __('payment.id') }}</th>
                            <th>{{ __('payment.user') }}</th>
                            <th>{{ __('payment.business') }}</th>
                            <th>{{ __('payment.plan') }}</th>
                            <th>{{ __('payment.billing_cycle') }}</th>
                            <th>{{ __('payment.amount') }}</th>
                            <th>{{ __('payment.start_date') }}</th>
                            <th>{{ __('payment.end_date') }}</th>
                            <th>{{ __('payment.status') }}</th>
                            <th>{{ __('payment.mpesa_receipt') }}</th>
                                <th>{{ __('payment.pending_links') }}</th>
                            <th>{{ __('payment.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subscriptions ?? [] as $subscription)
                        <tr class="subscription-row" data-status="{{ $subscription->status }}" data-billing-cycle="{{ $subscription->billing_cycle }}">
                            <td><input type="checkbox" class="subscription-checkbox" value="{{ $subscription->id }}"></td>
                            <td>{{ $subscription->id }}</td>
                            <td>{{ $subscription->user->username ?? ($subscription->user->name ?? 'N/A') }}</td>
                            <td>{{ $subscription->user->business->name ?? 'N/A' }}</td>
                            <td>{{ $subscription->plan_name }}</td>
                            <td>
                                <span class="badge bg-info text-capitalize">{{ $subscription->billing_cycle }}</span>
                            </td>
                            <td>Ksh {{ number_format($subscription->amount, 2) }}</td>
                            <td>{{ optional($subscription->start_date)->format('M d, Y') }}</td>
                            <td>{{ optional($subscription->end_date)->format('M d, Y') }}</td>
                            <td>
                                <span class="badge subscription-status {{ $subscription->status }} text-capitalize">
                                    {{ $subscription->status }}
                                </span>
                            </td>
                            <td>{{ $subscription->mpesa_receipt ?? 'N/A' }}</td>
                                <td>
                                    {{-- Pending invoice tx and mpesa payment links --}}
                                    @php
                                        $pendingLinks = [];
                                        if (!empty($subscription->pending_invoice_transaction_id)) {
                                            $pendingLinks[] = ['type' => 'invoice', 'id' => $subscription->pending_invoice_transaction_id];
                                        }
                                        if (!empty($subscription->pending_mpesa_payment_id)) {
                                            // Use pre-attached pending_mpesa property (eager-loaded) to avoid per-row queries
                                            if (!empty($subscription->pending_mpesa)) {
                                                $pendingLinks[] = ['type' => 'mpesa', 'id' => $subscription->pending_mpesa->id, 'checkout' => $subscription->pending_mpesa->checkout_request_id];
                                            } else {
                                                $pendingLinks[] = ['type' => 'mpesa', 'id' => $subscription->pending_mpesa_payment_id];
                                            }
                                        }
                                    @endphp
                                    <div class="d-flex flex-column gap-1">
                                        @forelse($pendingLinks as $pl)
                                            @if($pl['type'] === 'invoice')
                                                <a href="{{ action([\App\Http\Controllers\SellController::class, 'show'], [$pl['id']]) }}" target="_blank" class="badge bg-info text-decoration-none">Invoice #{{ $pl['id'] }}</a>
                                            @else
                                                @if(!empty($pl['checkout']))
                                                    <a href="{{ route('mpesa.logs', ['checkout_request_id' => $pl['checkout']]) }}" target="_blank" class="badge bg-warning text-decoration-none">Mpesa #{{ $pl['id'] }}</a>
                                                @else
                                                    <span class="badge bg-secondary">Mpesa #{{ $pl['id'] }}</span>
                                                @endif
                                            @endif
                                        @empty
                                            <span class="text-muted">-</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                    @if($subscription->status === 'active' || $subscription->status === 'expired')
                                        <form action="{{ route($subscriptionRenewRoute, $subscription) }}" method="POST" class="ajax-form d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <span class="btn-text">
                                                    <i class="fas fa-sync-alt me-1"></i> {{ __('payment.renew') }}
                                                </span>
                                                <span class="btn-loading d-none">
                                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                                </span>
                                            </button>
                                        </form>
                                    @endif

                                    @if($subscription->status === 'pending')
                                        <form action="{{ route($subscriptionActivateRoute, $subscription) }}" method="POST" class="ajax-form d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <span class="btn-text">
                                                    <i class="fas fa-check me-1"></i> {{ __('payment.activate') }}
                                                </span>
                                                <span class="btn-loading d-none">
                                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                                </span>
                                            </button>
                                        </form>

                                        <form action="{{ route($subscriptionDestroyRoute, $subscription) }}" method="POST" class="delete-subscription-form d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <span class="btn-text">
                                                    <i class="fas fa-trash-alt me-1"></i> {{ __('messages.delete') }}
                                                </span>
                                                <span class="btn-loading d-none">
                                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                                </span>
                                            </button>
                                        </form>
                                    @endif

                                    @if($subscription->status === 'canceled')
                                        <span class="badge bg-secondary">{{ __('payment.no_actions') }}</span>
                                    @endif
                                    
                    <!-- View Details Button -->
                                    <a href="{{ route($subscriptionShowRoute, $subscription->id) }}" class="btn btn-sm btn-info" title="{{ __('payment.view_details') }}">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    <!-- Download Invoice & Statement -->
                                        <a href="{{ route('subscription.invoice.download', $subscription->id) }}" class="btn btn-sm btn-outline-primary" target="_blank" title="Download Invoice">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                    </a>
                                        <a href="{{ route('subscription.statement.download', $subscription->id) }}" class="btn btn-sm btn-outline-secondary" target="_blank" title="Download Statement">
                                        <i class="fas fa-file-alt"></i>
                                    </a>
                                        <!-- Quick download for a specific date range -->
                                        <button type="button" class="btn btn-sm btn-outline-info btn-download-range" data-subscription-id="{{ $subscription->id }}" data-bs-toggle="modal" data-bs-target="#downloadRangeModal">
                                            <i class="fas fa-calendar-alt"></i>
                                        </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">
                                <i class="fas fa-database fa-2x mb-2 d-block"></i>
                                {{ __('payment.no_subscriptions_found') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($subscriptions?->count() > 0)
            <div class="d-flex justify-content-between align-items-center mt-4">
                <div class="text-muted">
                    {{ __('payment.showing_x_of_y', ['count' => $subscriptions->count(), 'total' => $subscriptions->total()]) }}
                </div>
                <div>
                    {{ $subscriptions->appends(request()->except('page'))->links() }}
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="bulkActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold">Bulk Actions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Run an action against the subscriptions you have selected in the table.</p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-primary" id="openBulkDownloadFromModal">
                        <i class="fas fa-file-download me-2"></i>Download statements for selected
                    </button>
                </div>
                <small class="text-muted d-block mt-3">Select one or more subscriptions first, then choose an action.</small>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createManualModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route($subscriptionManualRoute) }}">
                @csrf
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-semibold">Create Manual Subscription</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">User</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">Select user</option>
                            @foreach(($manualSubscriptionUsers ?? []) as $manualUser)
                                <option value="{{ $manualUser->id }}">
                                    {{ $manualUser->name }}@if(!empty($manualUser->business?->name)) - {{ $manualUser->business->name }}@endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Billing Cycle</label>
                        <select name="billing_cycle" class="form-select" required>
                            <option value="monthly">{{ __('payment.monthly') }}</option>
                            <option value="quarterly">{{ __('payment.quarterly') }}</option>
                            <option value="yearly">{{ __('payment.yearly') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label fw-semibold">Custom Amount</label>
                        <input type="number" step="0.01" min="0" name="custom_amount" class="form-control" placeholder="Optional override amount">
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Create Subscription</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Subscription Details Modal -->
<div class="modal fade" id="subscriptionDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-semibold">{{ __('payment.subscription_details') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="subscriptionDetailsContent">
                <!-- Content will be loaded via JavaScript -->
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('payment.close') }}</button>
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
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea, #764ba2) !important;
    }
    
    .card {
        transition: all 0.3s ease;
        border: none !important;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 .75rem 1.5rem rgba(0, 0, 0, .15) !important;
    }
    
    .table {
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .table th {
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
        border-top: none;
        background-color: #f8f9fc;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .table td {
        vertical-align: middle;
        border-top: 1px solid #f3f4f6;
    }
    
    .table tbody tr {
        transition: all 0.2s ease;
    }
    
    .table tbody tr:hover {
        background-color: #f8f9fc;
        transform: scale(1.01);
    }
    
    .subscription-status {
        font-size: 0.75rem;
        padding: 0.5em 0.75em;
        border-radius: 1rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .subscription-status.active {
        background-color: #10b981;
        color: white;
    }
    
    .subscription-status.pending {
        background-color: #f59e0b;
        color: white;
    }
    
    .subscription-status.expired {
        background-color: #6b7280;
        color: white;
    }
    
    .subscription-status.cancelled {
        background-color: #ef4444;
        color: white;
    }
    
    .btn {
        transition: all 0.2s ease;
        border-radius: 0.5rem;
        font-weight: 500;
    }
    
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
    }
    
    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }
    
    .form-control, .form-select {
        border-radius: 0.5rem;
        border: 1px solid #e3e6f0;
        transition: all 0.2s ease;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.1);
    }
    
    .modal-content {
        border-radius: 1rem;
        border: none;
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.175);
    }
    
    .modal-header {
        border-bottom: 1px solid #f3f4f6;
        border-radius: 1rem 1rem 0 0;
        background-color: #f8f9fc;
    }
    
    .modal-footer {
        border-top: 1px solid #f3f4f6;
        border-radius: 0 0 1rem 1rem;
        background-color: #f8f9fc;
    }
    
    .alert {
        border-radius: 0.75rem;
        border: none;
    }
    
    .pagination {
        margin-bottom: 0;
    }
    
    .page-link {
        border-radius: 0.5rem;
        margin: 0 0.125rem;
        border: 1px solid #e3e6f0;
        color: #6c757d;
        transition: all 0.2s ease;
    }
    
    .page-link:hover {
        background-color: #4e73df;
        border-color: #4e73df;
        color: white;
        transform: translateY(-1px);
    }
    
    .page-item.active .page-link {
        background-color: #4e73df;
        border-color: #4e73df;
    }
    
    .dropdown-menu {
        border-radius: 0.75rem;
        border: none;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    
    .dropdown-item {
        transition: all 0.2s ease;
        border-radius: 0.5rem;
        margin: 0.125rem;
    }
    
    .dropdown-item:hover {
        background-color: #4e73df;
        color: white;
        transform: translateX(5px);
    }
    
    .search-input {
        position: relative;
    }
    
    .search-input::before {
        content: '\f002';
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
        z-index: 5;
    }
    
    .search-input input {
        padding-left: 2.5rem;
    }
    
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: none !important; /* keep hidden by default */
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }
    
    .loading-spinner {
        background-color: white;
        padding: 2rem;
        border-radius: 1rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        text-align: center;
    }
    
    .empty-state {
        padding: 3rem 1rem;
        text-align: center;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.3;
    }
    
    .empty-state h5 {
        color: #495057;
        margin-bottom: 0.5rem;
    }
    
    .filter-badge {
        background-color: #4e73df;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.75rem;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
        display: inline-flex;
        align-items: center;
    }
    
    .filter-badge .btn-close {
        margin-left: 0.5rem;
        font-size: 0.75rem;
        filter: invert(1);
    }
    
    .animate-fade-in {
        animation: fadeIn 0.5s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .animate-slide-up {
        animation: slideUp 0.3s ease-out;
    }
    
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @media (max-width: 768px) {
        .card-body {
            padding: 1rem !important;
        }
        
        .table-responsive {
            font-size: 0.875rem;
        }
        
        .btn {
            font-size: 0.875rem;
            padding: 0.375rem 0.75rem;
        }
        
        .container-fluid {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }
    }
    
    /* Custom scrollbar for table */
    .table-responsive::-webkit-scrollbar {
        height: 8px;
    }
    
    .table-responsive::-webkit-scrollbar-track {
        background: #f8f9fc;
        border-radius: 10px;
    }
    
    .table-responsive::-webkit-scrollbar-thumb {
        background: #4e73df;
        border-radius: 10px;
    }
    
    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #375a9d;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add fade-in animation to cards
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        setTimeout(() => {
            card.classList.add('animate-fade-in');
        }, index * 50);
    });
    
    // Add slide-up animation to table rows
    const tableRows = document.querySelectorAll('.table tbody tr');
    tableRows.forEach((row, index) => {
        setTimeout(() => {
            row.classList.add('animate-slide-up');
        }, index * 25);
    });
    
    // Auto-dismiss alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.querySelector('.btn-close')) {
                alert.querySelector('.btn-close').click();
            }
        }, 5000);
    });
    
    // Add loading overlay (only if not present) and keep it hidden by default
    let loadingOverlay = document.body.querySelector('.loading-overlay');
    if (!loadingOverlay) {
        loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'loading-overlay';
        loadingOverlay.style.display = 'none';
        loadingOverlay.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h6 class="fw-medium mb-0">Processing request...</h6>
            </div>
        `;
        document.body.appendChild(loadingOverlay);
    }
    
    // Global loading functions
    window.showLoading = function() {
        loadingOverlay.style.display = 'flex';
    };
    
    window.hideLoading = function() {
        loadingOverlay.style.display = 'none';
    };
    
    // Enhanced AJAX form handling
    // Attach to both forms declared with data-ajax and legacy .ajax-form class
    document.querySelectorAll('form[data-ajax="true"], form.ajax-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const url = this.action;
            const method = this.method || 'POST';
            
            showLoading();
            
            fetch(url, {
                method: method,
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                
                if (data.success) {
                    showToast('success', data.message || 'Operation completed successfully');
                    
                    // Close modal if exists
                    const modal = this.closest('.modal');
                    if (modal) {
                        bootstrap.Modal.getInstance(modal).hide();
                    }
                    
                    // Reload page or update content
                    if (data.reload) {
                        setTimeout(() => location.reload(), 1000);
                    }
                } else {
                    showToast('error', data.message || 'Operation failed');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showToast('error', 'An error occurred. Please try again.');
            });
        });
    });
    // Ensure any stray overlay is hidden on initial load
    try { hideLoading(); } catch (e) { /* noop */ }
    
});
</script>
@endpush

@push('styles')
<style>
.subscription-status.active {
    background-color: #198754 !important;
}
.subscription-status.pending {
    background-color: #ffc107 !important;
    color: #000 !important;
}
.subscription-status.expired {
    background-color: #6c757d !important;
}
.subscription-status.canceled {
    background-color: #dc3545 !important;
}
.btn-loading .spinner-border-sm {
    width: 1rem;
    height: 1rem;
}
.ajax-form {
    display: inline-block;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    // AJAX forms are handled centrally in /public/js/ajax-forms.js
    console.log('Using centralized AJAX form handler for .ajax-form');

    // Filter functionality - server-side implementation
    const filterForm = document.getElementById('filterForm');
    const statusFilter = document.getElementById('statusFilter');
    const billingCycleFilter = document.getElementById('billingCycleFilter');
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');
    const resetFilters = document.getElementById('resetFilters');

    // Auto-submit form when filters change
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            filterForm.submit();
        });
    }

    if (billingCycleFilter) {
        billingCycleFilter.addEventListener('change', function() {
            filterForm.submit();
        });
    }

    // Search functionality
    if (searchButton) {
        searchButton.addEventListener('click', function() {
            filterForm.submit();
        });
    }

    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                filterForm.submit();
            }
        });
    }

    // Reset filters
    if (resetFilters) {
        resetFilters.addEventListener('click', function() {
            // Clear all form inputs
            statusFilter.value = '';
            billingCycleFilter.value = '';
            searchInput.value = '';
            
            // Submit the form to reset everything
            filterForm.submit();
        });
    }

    // Subscription details modal
    const viewDetailsButtons = document.querySelectorAll('.view-details');
    const detailsContent = document.getElementById('subscriptionDetailsContent');

    viewDetailsButtons.forEach(button => {
        button.addEventListener('click', function() {
            const subscription = JSON.parse(this.getAttribute('data-subscription'));
            const user = JSON.parse(this.getAttribute('data-user'));
            const business = JSON.parse(this.getAttribute('data-business'));
            
            // Format dates
            const startDate = subscription.start_date ? new Date(subscription.start_date).toLocaleDateString() : 'N/A';
            const endDate = subscription.end_date ? new Date(subscription.end_date).toLocaleDateString() : 'N/A';
            const createdAt = subscription.created_at ? new Date(subscription.created_at).toLocaleDateString() : 'N/A';
            const updatedAt = subscription.updated_at ? new Date(subscription.updated_at).toLocaleDateString() : 'N/A';

            detailsContent.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-semibold">{{ __('payment.subscription_info') }}</h6>
                        <p><strong class="text-muted">{{ __('payment.id') }}:</strong> <span class="fw-medium">${subscription.id}</span></p>
                        <p><strong class="text-muted">{{ __('payment.plan') }}:</strong> <span class="fw-medium">${subscription.plan_name}</span></p>
                        <p><strong class="text-muted">{{ __('payment.billing_cycle') }}:</strong> <span class="fw-medium text-capitalize">${subscription.billing_cycle}</span></p>
                        <p><strong class="text-muted">{{ __('payment.amount') }}:</strong> <span class="fw-medium">Ksh ${subscription.amount.toLocaleString()}</span></p>
                        <p><strong class="text-muted">{{ __('payment.status') }}:</strong> <span class="badge subscription-status ${subscription.status} text-capitalize">${subscription.status}</span></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-semibold">{{ __('payment.dates') }}</h6>
                        <p><strong class="text-muted">{{ __('payment.start_date') }}:</strong> <span class="fw-medium">${startDate}</span></p>
                        <p><strong class="text-muted">{{ __('payment.end_date') }}:</strong> <span class="fw-medium">${endDate}</span></p>
                        <p><strong class="text-muted">{{ __('payment.created_at') }}:</strong> <span class="fw-medium">${createdAt}</span></p>
                        <p><strong class="text-muted">{{ __('payment.updated_at') }}:</strong> <span class="fw-medium">${updatedAt}</span></p>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6 class="fw-semibold">{{ __('payment.user_info') }}</h6>
                        <p><strong class="text-muted">{{ __('payment.name') }}:</strong> <span class="fw-medium">${user.name || 'N/A'}</span></p>
                        <p><strong class="text-muted">{{ __('payment.email') }}:</strong> <span class="fw-medium">${user.email || 'N/A'}</span></p>
                        <p><strong class="text-muted">{{ __('payment.phone') }}:</strong> <span class="fw-medium">${user.phone || 'N/A'}</span></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-semibold">{{ __('payment.business_info') }}</h6>
                        <p><strong class="text-muted">{{ __('payment.business_name') }}:</strong> <span class="fw-medium">${business?.name || 'N/A'}</span></p>
                    </div>
                </div>
                ${subscription.mpesa_receipt ? `
                <div class="row mt-3">
                    <div class="col-12">
                        <h6 class="fw-semibold">{{ __('payment.payment_info') }}</h6>
                        <p><strong class="text-muted">{{ __('payment.mpesa_receipt') }}:</strong> <span class="fw-medium">${subscription.mpesa_receipt}</span></p>
                    </div>
                </div>
                ` : ''}
            `;
        });
    });

    // Show alert function
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.getElementById('ajaxAlerts').appendChild(alertDiv);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }

    // Handle select all subscriptions
    $('#selectAllSubscriptions').on('change', function() {
        $('.subscription-checkbox').prop('checked', $(this).is(':checked'));
    });

    // Show download modal when clicking download range button
    $('.btn-download-range').on('click', function() {
        var selectedIds = $('.subscription-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedIds.length === 0) {
            showToast('warning', '{{ __("payment.please_select_subscriptions") }}');
            return;
        }

        $('#downloadRangeModal').modal('show');
    });

    // Handle download range modal
    $('#downloadRangeModal').on('show.bs.modal', function () {
        var selectedIds = $('.subscription-checkbox:checked').map(function() {
            return $(this).val();
        }).get();
        
        $('#download_subscription_ids').val(selectedIds.join(','));
    });

    $('#openBulkDownloadFromModal').on('click', function() {
        var selectedIds = $('.subscription-checkbox:checked').map(function() {
            return $(this).val();
        }).get();

        if (selectedIds.length === 0) {
            showAlert('warning', '{{ __('payment.please_select_subscriptions') }}');
            return;
        }

        $('#bulkActionModal').modal('hide');
        $('#download_subscription_ids').val(selectedIds.join(','));
        $('#downloadRangeModal').modal('show');
    });

    $('#openBulkActionModal').on('click', function(e) {
        e.preventDefault();
        $('#bulkActionModal').modal('show');
    });

    $('#openCreateManualModal').on('click', function(e) {
        e.preventDefault();
        $('#createManualModal').modal('show');
    });

    $('.delete-subscription-form').on('submit', function(e) {
        e.preventDefault();

        const form = this;

        if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
            Swal.fire({
                title: 'Delete pending subscription?',
                text: 'This will permanently remove the pending subscription record.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b'
            }).then((result) => {
                if (result.isConfirmed) {
                    HTMLFormElement.prototype.submit.call(form);
                }
            });
            return;
        }

        if (typeof swal === 'function') {
            swal({
                title: 'Delete pending subscription?',
                text: 'This will permanently remove the pending subscription record.',
                icon: 'warning',
                buttons: true,
                dangerMode: true,
            }).then((confirmed) => {
                if (confirmed) {
                    HTMLFormElement.prototype.submit.call(form);
                }
            });
            return;
        }

        if (confirm('Delete this pending subscription?')) {
            HTMLFormElement.prototype.submit.call(form);
        }
    });
});
</script>
@endpush