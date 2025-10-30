@extends('layouts.app')

@section('title', auth()->user()->is_admin ? __('Admin Dashboard') : __('Subscription Plans'))

@section('content')
@php
    $user = auth()->user();
    $businessName = optional($user->business)->name ?? '';
    $userName = $user->name ?? $user->username ?? '';
    $userSubscription = !$user->is_admin ? $user->subscriptions()->latest()->first() : null;
    $settings = \App\AdminSetting::first();
    $plans = [
        'monthly' => ['name' => __('payment.monthly'), 'price' => $settings->monthly_price],
        'quarterly' => ['name' => __('payment.quarterly'), 'price' => $settings->quarterly_price],
        'yearly' => ['name' => __('payment.yearly'), 'price' => $settings->yearly_price],
    ];
    
    // Get latest payment for this user
    $latestPayment = !$user->is_admin ? $user->mpesaPayments()->latest()->first() : null;
    
    // Check if user has active subscription (not expired)
    $hasActiveSubscription = $userSubscription && 
                           $userSubscription->status === 'active' && 
                           \Carbon\Carbon::parse($userSubscription->end_date)->isFuture();
    
    // Check if user has expired subscription
    $hasExpiredSubscription = $userSubscription && 
                            $userSubscription->status === 'expired';

    // Buttons should show when user does not have an active subscription
    // (expired/pending/cancelled subscriptions are allowed to show action buttons)
    $canShowActionButtons = !$hasActiveSubscription;
@endphp

<!-- Header Section -->
<div class="bg-gradient-primary text-white py-5 mb-4">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h2 mb-2 fw-bold">
                    @if($user->is_admin)
                        <i class="fas fa-tachometer-alt me-3"></i>Admin Dashboard
                    @else
                        <i class="fas fa-crown me-3"></i>Subscription Plans
                    @endif
                </h1>
                <p class="mb-0 opacity-75">
                    @if($user->is_admin)
                        Manage users, subscriptions, and system settings
                    @else
                        Choose the perfect plan for your business needs
                    @endif
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                @if(!$user->is_admin && $hasActiveSubscription)
                    <div class="bg-white bg-opacity-20 rounded-3 p-3 text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h6 class="mb-1">Active Until</h6>
                        <p class="mb-0 fw-bold">{{ \Carbon\Carbon::parse($userSubscription->end_date)->format('M j, Y') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-4">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Dashboard Stats -->
    <div class="row g-4 mb-5">
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 overflow-hidden">
                <div class="card-body p-4 position-relative">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-2 fw-medium">{{ __('payment.total_businesses') }}</h6>
                            <h2 class="mb-0 fw-bold text-primary"><span id="card-total-businesses">{{ $user->is_admin ? ($totalUsers ?? 0) : ($user->business ? 1 : 0) }}</span></h2>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-building fa-2x text-primary"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-primary bg-opacity-5 py-2">
                    <small class="text-primary fw-medium">
                        <i class="fas fa-chart-line me-1"></i>Total registered
                    </small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 overflow-hidden">
                <div class="card-body p-4 position-relative">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-2 fw-medium">{{ __('payment.active') }}</h6>
                            <h2 class="mb-0 fw-bold text-success"><span id="card-active-count">{{ $user->is_admin ? ($activeUsers ?? 0) : ($hasActiveSubscription ? 1 : 0) }}</span></h2>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-check-circle fa-2x text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-success bg-opacity-5 py-2">
                    <small class="text-success fw-medium">
                        <i class="fas fa-arrow-up me-1"></i>Currently active
                    </small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 overflow-hidden">
                <div class="card-body p-4 position-relative">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-2 fw-medium">{{ __('payment.pending') }}</h6>
                            <h2 class="mb-0 fw-bold text-warning"><span id="card-pending-count">{{ $user->is_admin ? ($pendingSubscriptions ?? 0) : ($userSubscription?->status === 'pending' ? 1 : 0) }}</span></h2>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-clock fa-2x text-warning"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-warning bg-opacity-5 py-2">
                    <small class="text-warning fw-medium">
                        <i class="fas fa-hourglass-half me-1"></i>Awaiting payment
                    </small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 overflow-hidden">
                <div class="card-body p-4 position-relative">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-2 fw-medium">{{ __('payment.monthly_revenue') }}</h6>
                            <h2 class="mb-0 fw-bold text-info">Ksh <span id="card-monthly-revenue">{{ $user->is_admin ? number_format($monthlyRevenue ?? 0, 0) : number_format($userSubscription?->amount ?? 0, 0) }}</span></h2>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-dollar-sign fa-2x text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-info bg-opacity-5 py-2">
                    <small class="text-info fw-medium">
                        <i class="fas fa-coins me-1"></i>Monthly revenue
                    </small>
                </div>
            </div>
        </div>
    </div>

    @if(!$user->is_admin)
    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow-sm rounded-3">
                <div class="card-header bg-success text-white">
                    <h5>{{ __('payment.subscription_management') }}</h5>
                </div>
                <div class="card-body">
                    
                    <!-- Current Subscription Status -->
                    <div class="mb-3">
                        <h6>{{ __('payment.current_subscription_status') }}:</h6>
                        @if($userSubscription)
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-{{ $userSubscription->status === 'active' ? 'success' : ($userSubscription->status === 'pending' ? 'warning' : ($userSubscription->status === 'expired' ? 'danger' : 'secondary')) }} me-2">
                                    {{ ucfirst($userSubscription->status) }}
                                </span>
                                @if($userSubscription->status === 'pending')
                                    <span class="spinner-border spinner-border-sm text-warning ms-2" role="status">
                                        <span class="visually-hidden">{{ __('payment.processing') }}</span>
                                    </span>
                                @endif
                            </div>
                            
                            <div class="subscription-details">
                                <p><i class="fas fa-tag me-2"></i>{{ __('payment.plan') }}: <strong>{{ $userSubscription->plan_name }}</strong></p>
                                <p><i class="fas fa-money-bill me-2"></i>{{ __('payment.amount') }}: <strong>Ksh {{ number_format($userSubscription->amount, 2) }}</strong></p>
                                <p><i class="fas fa-calendar me-2"></i>
                                    @if($userSubscription->status === 'active')
                                        {{ __('payment.expires') }}: {{ \Carbon\Carbon::parse($userSubscription->end_date)->format('d M Y') }}
                                        @if($userSubscription->billing_cycle === 'monthly' && 
                                        \Carbon\Carbon::parse($userSubscription->end_date)->diffInMonths(now()) > 1)
                                        <br><small class="text-warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            {{ __('payment.date_calculation_issue') }}
                                        </small>
                                    @endif
                                    @elseif($userSubscription->status === 'pending')
                                        {{ __('payment.waiting_payment_confirmation') }}
                                    @endif
                                </p>
                                @if($userSubscription->mpesa_receipt)
                                    <p><i class="fas fa-receipt me-2"></i>{{ __('payment.mpesa_receipt') }}: <strong>{{ $userSubscription->mpesa_receipt }}</strong></p>
                                @endif
                            </div>

                            @if($canShowActionButtons)
                                @if($userSubscription && $userSubscription->status === 'pending')
                                    <button id="renewSubscriptionBtn" type="button" class="btn btn-info mb-2">
                                        <i class="fas fa-clock me-1"></i>
                                        {{ __('payment.complete_pending_payment') }}
                                    </button>
                                @elseif($userSubscription && $userSubscription->status === 'expired')
                                    <button id="renewSubscriptionBtn" type="button" class="btn btn-success mb-2">
                                        <i class="fas fa-redo me-1"></i>
                                        {{ __('payment.renew_plan') }}
                                    </button>
                                @elseif($userSubscription && $userSubscription->status === 'cancelled')
                                    <button id="renewSubscriptionBtn" type="button" class="btn btn-success mb-2">
                                        <i class="fas fa-plus-circle me-1"></i>
                                        {{ __('payment.create_subscription') }}
                                    </button>
                                @elseif(!$userSubscription)
                                    <button id="renewSubscriptionBtn" type="button" class="btn btn-success mb-2">
                                        <i class="fas fa-plus-circle me-1"></i>
                                        {{ __('payment.create_subscription') }}
                                    </button>
                                @endif
                            @endif
                        @else
                            @if($userSubscription && $userSubscription->status === 'expired')
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    <strong>{{ __('payment.subscription_expired') }}</strong>
                                    <p class="mb-0 mt-1">{{ __('payment.expired_on', ['date' => \Carbon\Carbon::parse($userSubscription->end_date)->format('F j, Y')]) }}</p>
                                    <p class="mb-0 mt-1"><small>{{ __('payment.renew_to_continue') }}</small></p>
                                </div>
                            @elseif($userSubscription && $userSubscription->status === 'cancelled')
                                <div class="alert alert-secondary">
                                    <i class="fas fa-ban me-1"></i>
                                    <strong>{{ __('payment.subscription_cancelled') }}</strong>
                                    <p class="mb-0 mt-1">{{ __('payment.reactivate_anytime') }}</p>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-1"></i>{{ __('payment.no_active_subscription') }}
                                </div>
                            @endif
                        @endif
                    </div>

                    <hr>

                    <!-- Subscription Action Panel - Only show if user doesn't have active subscription -->
                    <div id="subscriptionActionPanel" class="{{ $hasActiveSubscription ? 'd-none' : '' }}">
                        @if($hasActiveSubscription)
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-1"></i>
                                <strong>{{ __('payment.subscription_active') }}</strong>
                                <p class="mb-0 mt-1">{{ __('payment.cannot_create_new_subscription') }}</p>
                            </div>
                        @elseif($canShowActionButtons)
                            <h6>
                                @if($userSubscription && $userSubscription->status === 'pending')
                                    {{ __('payment.complete_your_payment') }}
                                @else
                                    {{ __('payment.select_plan') }}
                                @endif
                            </h6>
                            
                            <select class="form-select mb-3" id="subscription_plan">
                                @foreach($plans as $key => $plan)
                                    <option value="{{ $key }}" data-price="{{ $plan['price'] }}" {{ ($userSubscription && ($userSubscription->billing_cycle ?? '') === $key) ? 'selected' : '' }}>
                                        {{ $plan['name'] }} - Ksh {{ number_format($plan['price'],2) }}
                                    </option>
                                @endforeach
                            </select>

                            <div class="mb-3">
                                <label class="form-label">{{ __('payment.phone_number_format') }}</label>
                                <input type="text" class="form-control" id="phone" placeholder="2547XXXXXXXX" required value="{{ $latestPayment->phone_number ?? '' }}">
                                <div class="form-text">{{ __('payment.we_will_send') }}</div>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <button id="createSubscriptionBtn" type="button" class="btn btn-primary">
                                    <i class="fas fa-plus-circle me-1"></i>
                                    @if($userSubscription && $userSubscription->status === 'pending')
                                        {{ __('payment.complete_payment') }}
                                    @else
                                        {{ __('payment.create_subscription') }}
                                    @endif
                                </button>
                                <button id="stkPushBtn" type="button" class="btn btn-success">
                                    <i class="fas fa-mobile-alt me-1"></i>{{ __('payment.pay_via_mpesa') }}
                                </button>
                            </div>

                            <!-- Manual Check Button - Only show for pending payments when action buttons are allowed -->
                            @if($userSubscription && $userSubscription->status === 'pending')
                            <button id="manualCheckStatus" type="button" class="btn btn-warning mt-2">
                                <i class="fas fa-refresh me-1"></i>{{ __('payment.check_payment_status') }}
                            </button>
                            @endif

                            <div id="stkResponse" class="mt-3"></div>
                            
                            <div id="paymentPendingSection" class="alert alert-info mt-3" style="display:none;">
                                <div class="d-flex">
                                    <div class="me-2">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div>
                                        <h6>{{ __('payment.payment_processing') }}</h6>
                                        <p>{{ __('payment.waiting_payment') }}</p>
                                        <div class="progress mb-2" style="height: 5px;">
                                            <div id="paymentProgress" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <button id="checkPaymentStatus" type="button" class="btn btn-sm btn-info">{{ __('payment.check_status_now') }}</button>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-1"></i>
                                <strong>{{ __('payment.no_action_needed') }}</strong>
                                <p class="mb-0 mt-1">{{ __('payment.subscription_management_unavailable') }}</p>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Active Subscription Message -->
                    @if($hasActiveSubscription)
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-1"></i>
                        <strong>{{ __('payment.subscription_active') }}</strong>
                        <p class="mb-0 mt-1">{{ __('payment.your_subscription_active_until', ['date' => \Carbon\Carbon::parse($userSubscription->end_date)->format('F j, Y')]) }}</p>
                        <p class="mb-0 mt-1"><small>{{ __('payment.contact_support_date_issue') }}</small></p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Payment History -->
        <div class="col-md-6">
            <div class="card shadow-sm rounded-3">
                <div class="card-header bg-info text-white">
                    <h5>{{ __('payment.payment_history') }}</h5>
                </div>
                <div class="card-body">
                    @if(isset($paymentHistory) && $paymentHistory->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>{{ __('payment.date') }}</th>
                                        <th>{{ __('payment.amount') }}</th>
                                        <th>{{ __('payment.status') }}</th>
                                        <th>{{ __('payment.receipt') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($paymentHistory as $payment)
                                    <tr data-checkout="{{ $payment->checkout_request_id ?? '' }}" data-payment-id="{{ $payment->id }}">
                                        <td>{{ $payment->created_at->format('d M Y H:i') }}</td>
                                        <td>Ksh {{ number_format($payment->amount, 2) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $payment->transaction_status === 'paid' ? 'success' : ($payment->transaction_status === 'pending' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($payment->transaction_status) }}
                                            </span>
                                        </td>
                                        <td>{{ $payment->mpesa_receipt_number ?? 'N/A' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-muted">{{ __('payment.no_payment_history') }}</p>
                    @endif
                </div>
            </div>
            
            <!-- Subscription Activation Guide -->
            <div class="card shadow-sm rounded-3 mt-4">
                <div class="card-header bg-secondary text-white">
                    <h5>{{ __('payment.activate_subscription_guide') }}</h5>
                </div>
                <div class="card-body">
                    <ol class="list-group list-group-numbered">
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold">{{ __('payment.select_a_plan') }}</div>
                                {{ __('payment.choose_plan_text') }}
                            </div>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold">{{ __('payment.enter_phone_number') }}</div>
                                {{ __('payment.phone_number_example') }}
                            </div>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold">{{ __('payment.initiate_payment') }}</div>
                                {{ __('payment.click_pay_via_mpesa') }}
                            </div>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold">{{ __('payment.complete_payment_step') }}</div>
                                {{ __('payment.enter_pin') }}
                            </div>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold">{{ __('payment.automatic_activation') }}</div>
                                {{ __('payment.auto_activate_text') }}
                            </div>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>

@if(!$user->is_admin)
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log("Dashboard JS Loaded ✅");

    const phoneInput = document.getElementById('phone');
    const planSelect = document.getElementById('subscription_plan');
    const createBtn = document.getElementById('createSubscriptionBtn');
    const stkBtn = document.getElementById('stkPushBtn');
    const renewBtn = document.getElementById('renewSubscriptionBtn');
    const manualCheckBtn = document.getElementById('manualCheckStatus');
    const checkPaymentBtn = document.getElementById('checkPaymentStatus');
    const responseDiv = document.getElementById('stkResponse');
    const paymentPendingSection = document.getElementById('paymentPendingSection');
    const paymentProgress = document.getElementById('paymentProgress');
    const subscriptionActionPanel = document.getElementById('subscriptionActionPanel');
    
    let pollingInterval, pollCount = 0, maxPolls = 12;
    let checkoutRequestId = null;
    let paymentCheckInterval = null;
    let progressValue = 0;

    // Check if user has active subscription
    const hasActiveSubscription = '{{ $hasActiveSubscription }}' === '1';

    function getSelectedPlan() {
        const option = planSelect.selectedOptions[0];
        return {
            key: option.value,
            name: option.text.split('-')[0].trim(),
            price: parseFloat(option.dataset.price)
        };
    }

    function showPaymentPendingUI() {
        paymentPendingSection.style.display = 'block';
        progressValue = 0;
        updateProgressBar();
        
        // Animate progress bar
        const progressInterval = setInterval(() => {
            if (progressValue < 90) { // Don't go to 100% automatically
                progressValue += 5;
                updateProgressBar();
            }
        }, 2000);
    }
    
    function updateProgressBar() {
        paymentProgress.style.width = `${progressValue}%`;
    }
    
    function completeProgressBar() {
        progressValue = 100;
        updateProgressBar();
        setTimeout(() => {
            paymentPendingSection.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <strong>{{ __('payment.payment_confirmed_reload') }}</strong>
                </div>
            `;
        }, 1000);
    }

    // Apply activation UI changes without a full page reload
    function applyActivationUI(receipt, startDate, endDate) {
        // Ensure any global loading overlay is hidden
        if (typeof window.hideLoadingOverlay === 'function') {
            try { window.hideLoadingOverlay(); } catch(e) { console.debug('hideLoadingOverlay failed', e); }
        }

        // Update subscription status badge
        const badge = document.querySelector('.subscription-details .badge');
        if (badge) {
            badge.className = 'badge bg-success me-2';
            badge.textContent = 'Active';
        }

        // Remove any spinner
        const spinner = document.querySelector('.subscription-details .spinner-border');
        if (spinner) spinner.remove();

        // Update subscription dates and plan info if present
        const subDetails = document.querySelector('.subscription-details');
        if (subDetails) {
            // Update expires line if exists
            const expiresLine = Array.from(subDetails.querySelectorAll('p')).find(p => p.textContent.includes('{{ __('payment.expires') }}') || p.textContent.includes('{{ __('payment.waiting_payment_confirmation') }}'));
            if (expiresLine && endDate) {
                expiresLine.innerHTML = `<i class="fas fa-calendar me-2"></i> {{ __('payment.expires') }}: <strong>${new Date(endDate).toLocaleDateString()}</strong>`;
            }

            // Add/Update mpesa receipt line
            let receiptEl = Array.from(subDetails.querySelectorAll('p')).find(p => p.textContent.includes('{{ __('payment.mpesa_receipt') }}'));
            if (receiptEl) {
                receiptEl.innerHTML = `<i class="fas fa-receipt me-2"></i>{{ __('payment.mpesa_receipt') }}: <strong>${receipt}</strong>`;
            } else if (receipt) {
                const el = document.createElement('p');
                el.innerHTML = `<i class="fas fa-receipt me-2"></i>{{ __('payment.mpesa_receipt') }}: <strong>${receipt}</strong>`;
                subDetails.appendChild(el);
            }
        }

        // Hide action panel and disable buttons
        if (subscriptionActionPanel) subscriptionActionPanel.classList.add('d-none');
        if (stkBtn) stkBtn.disabled = true;
        if (renewBtn) renewBtn.disabled = true;
        if (createBtn) createBtn.disabled = true;

        // Show success toast and finalize progress
        completeProgressBar();
        responseDiv.innerHTML = '<div class="alert alert-success">{{ __("payment.payment_confirmed_reload") }}</div>';
        // Mark in sessionStorage that subscription is active
        sessionStorage.removeItem('checkout_request_id');

        // --- Update payment history row in-place ---
        try {
            const checkoutId = sessionStorage.getItem('checkout_request_id') || '';
            let tr = null;
            if (checkoutId) {
                tr = document.querySelector(`tr[data-checkout="${checkoutId}"]`);
            }
            if (!tr) {
                tr = document.querySelector('.table.table-sm tbody tr');
            }

            if (tr) {
                const tds = tr.querySelectorAll('td');
                if (tds.length >= 4) {
                    // status
                    tds[2].innerHTML = `<span class="badge bg-success">Paid</span>`;
                    // receipt
                    tds[3].textContent = receipt || 'N/A';
                }
            }
        } catch (e) {
            console.error('Error updating payment history row:', e);
        }

        // --- Update dashboard counters ---
        try {
            const activeEl = document.getElementById('card-active-count');
            const pendingEl = document.getElementById('card-pending-count');
            const revenueEl = document.getElementById('card-monthly-revenue');

            // increment active, decrement pending
            if (activeEl) {
                const v = parseInt(activeEl.textContent || '0', 10) || 0;
                activeEl.textContent = v + 1;
            }
            if (pendingEl) {
                const p = parseInt(pendingEl.textContent || '0', 10) || 0;
                pendingEl.textContent = Math.max(0, p - 1);
            }

            // increase monthly revenue by amount if we can determine it
            if (revenueEl) {
                // Try to parse amount from subscription-details or fallback to selected plan price
                let add = 0;
                try {
                    const amountP = Array.from(document.querySelectorAll('.subscription-details p')).find(p => p.textContent.includes('{{ __('payment.amount') }}'));
                    if (amountP) {
                        const m = amountP.textContent.match(/Ksh\s*([0-9,\.]+)/);
                        if (m && m[1]) {
                            add = parseFloat(m[1].replace(/,/g, '')) || 0;
                        }
                    }
                } catch (e) { /* ignore */ }

                if (!add) {
                    try { add = parseFloat(getSelectedPlan().price) || 0; } catch(e) { add = 0; }
                }

                const current = parseFloat(revenueEl.textContent.replace(/[,\sKsh]/g, '')) || 0;
                revenueEl.textContent = (current + add).toLocaleString(undefined, {maximumFractionDigits:0});
            }
        } catch (e) {
            console.error('Error updating dashboard counters:', e);
        }

        // --- Show toast with receipt and link to success page ---
        try {
            const subId = sessionStorage.getItem('subscription_id');
            const baseUrl = '{{ url('/subscription/success') }}';
            const link = subId ? `<br><a href="${baseUrl}/${subId}" class="text-white">{{ __('payment.view_subscription') }}</a>` : '';
            if (receipt) {
                showToast('success', `{{ __('payment.payment_confirmed_toast') }}: <strong>${receipt}</strong>${link}`, '{{ __('payment.subscription_activated') }}');
            } else {
                showToast('success', '{{ __('payment.subscription_activated') }}' + (link ? link : ''));
            }
        } catch (e) {
            console.error('Error showing activation toast:', e);
        }
    }

    async function createSubscription(plan, phone, checkoutRequestId = null) {
        // Prevent if user has active subscription
        if (hasActiveSubscription) {
            responseDiv.innerHTML = '<div class="alert alert-warning">{{ __("payment.cannot_create_active_subscription") }}</div>';
            return;
        }

        try {
            const res = await fetch("{{ route('subscription.processPayment') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    billing_cycle: plan.key, 
                    phone,
                    checkout_request_id: checkoutRequestId 
                })
            });
            const data = await res.json();
            
            if (data.success) {
                responseDiv.innerHTML = '<div class="alert alert-success">{{ __("payment.subscription_activated") }}</div>';
                setTimeout(() => location.reload(), 2000);
            } else {
                responseDiv.innerHTML = `<div class="alert alert-danger">${data.message || '{{ __("payment.payment_failed_try_again") }}'}</div>`;
            }
        } catch(err) {
            console.error(err);
            responseDiv.innerHTML = '<div class="alert alert-danger">{{ __("payment.payment_initiation_failed") }}</div>';
        }
    }

    async function initiateStkPush(plan, phone) {
        // Prevent if user has active subscription
        if (hasActiveSubscription) {
            responseDiv.innerHTML = '<div class="alert alert-warning">{{ __("payment.cannot_pay_active_subscription") }}</div>';
            return;
        }

        responseDiv.innerHTML = '<div class="alert alert-info">{{ __("payment.initiating_payment") }}</div>';
        if(stkBtn) stkBtn.disabled = true;
        if(renewBtn) renewBtn.disabled = true;
        if(createBtn) createBtn.disabled = true;

        try {
            const res = await fetch("{{ route('subscription.stkPush') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    phone,
                    plan_key: plan.key,
                    first_name: "{{ $user->first_name ?? $userName }}",
                    last_name: "{{ $user->last_name ?? '' }}"
                })
            });
            const data = await res.json();
            
            if (data.success) {
                checkoutRequestId = data.checkout_request_id;
                responseDiv.innerHTML = '<div class="alert alert-success">{{ __("payment.stk_sent") }}</div>';
                
                // Store in session for later retrieval
                sessionStorage.setItem('checkout_request_id', checkoutRequestId);
                sessionStorage.setItem('subscription_id', data.subscription_id);
                
                // Show payment pending UI
                showPaymentPendingUI();
                
                // Start 5-second payment check interval
                startPaymentCheckInterval(checkoutRequestId);
            } else {
                responseDiv.innerHTML = '<div class="alert alert-danger">{{ __("payment.payment_initiation_failed") }}</div>';
                if(stkBtn) stkBtn.disabled = false;
                if(renewBtn) renewBtn.disabled = false;
                if(createBtn) createBtn.disabled = false;
            }
        } catch(err) {
            console.error(err);
            responseDiv.innerHTML = '<div class="alert alert-danger">{{ __("payment.error_confirming_payment") }}</div>';
            if(stkBtn) stkBtn.disabled = false;
            if(renewBtn) renewBtn.disabled = false;
            if(createBtn) createBtn.disabled = false;
        }
    }

    async function renewSubscription(plan, phone) {
        responseDiv.innerHTML = '<div class="alert alert-info">{{ __("payment.initiating_renewal") }}</div>';
        if(stkBtn) stkBtn.disabled = true;
        if(renewBtn) renewBtn.disabled = true;
        if(createBtn) createBtn.disabled = true;

        try {
            const res = await fetch("{{ route('subscription.renew') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    billing_cycle: plan.key,
                    phone: phone
                })
            });
            const data = await res.json();
            
            if (data.success) {
                checkoutRequestId = data.checkout_request_id;
                responseDiv.innerHTML = '<div class="alert alert-success">{{ __("payment.renewal_initiated") }}</div>';
                
                // Store in session for later retrieval
                sessionStorage.setItem('checkout_request_id', checkoutRequestId);
                sessionStorage.setItem('subscription_id', data.subscription_id);
                
                // Show payment pending UI
                showPaymentPendingUI();
                
                // Start 5-second payment check interval
                startPaymentCheckInterval(checkoutRequestId);
            } else {
                responseDiv.innerHTML = `<div class="alert alert-danger">${data.message || '{{ __("payment.renewal_failed") }}'}</div>`;
                if(stkBtn) stkBtn.disabled = false;
                if(renewBtn) renewBtn.disabled = false;
                if(createBtn) createBtn.disabled = false;
            }
        } catch(err) {
            console.error(err);
            responseDiv.innerHTML = '<div class="alert alert-danger">{{ __("payment.error_initiating_renewal") }}</div>';
            if(stkBtn) stkBtn.disabled = false;
            if(renewBtn) renewBtn.disabled = false;
            if(createBtn) createBtn.disabled = false;
        }
    }

    // Function to start 5-second payment check interval
    function startPaymentCheckInterval(checkoutRequestId) {
        // Clear any existing interval
        if (paymentCheckInterval) {
            clearInterval(paymentCheckInterval);
        }
        
        // Start checking every 5 seconds
        paymentCheckInterval = setInterval(async () => {
            try {
                // include subscription_id and phone to improve matching when checkout id is missing
                const payload = {
                    checkout_request_id: checkoutRequestId || null,
                    check_only: true,
                    subscription_id: sessionStorage.getItem('subscription_id') || '{{ $userSubscription?->id ?? '' }}',
                    phone: (document.getElementById('phone') ? document.getElementById('phone').value.trim() : '')
                };

                const res = await fetch("{{ route('subscription.manualStatusCheck') }}", {
                    method: 'POST',
                    headers: { 
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                
                if (data.success && (data.transaction_status === 'success' || data.transaction_status === 'paid')) {
                    // Payment successful - clear interval and update UI without reload
                    clearInterval(paymentCheckInterval);
                    completeProgressBar();

                    // Try to extract receipt and dates from response (fallback to session)
                    const receipt = data.receipt_number || data.mpesa_receipt_number || null;
                    const startDate = data.start_date || null;
                    const endDate = data.end_date || null;

                    applyActivationUI(receipt, startDate, endDate);
                    // Give the UI a brief moment to update then reload to show full updated page
                    setTimeout(() => { location.reload(); }, 1500);
                    // No full reload required; UI reflects active subscription
                } else if (data.success && data.transaction_status === 'failed') {
                    // Payment failed - clear interval and show message
                    clearInterval(paymentCheckInterval);
                    responseDiv.innerHTML = '<div class="alert alert-danger">{{ __("payment.payment_failed_try_again") }}</div>';
                    paymentPendingSection.style.display = 'none';
                    if(stkBtn) stkBtn.disabled = false;
                    if(renewBtn) renewBtn.disabled = false;
                    if(createBtn) createBtn.disabled = false;
                }
                // If still pending, the interval will continue checking
            } catch(err) {
                console.error('Error in payment check interval:', err);
            }
        }, 5000);
    }

    async function checkPaymentStatusManually() {
        // Allow manual checks even when checkout ID isn't set. The backend will
        // fall back to the user's latest payment if no checkout_request_id is provided.
        const checkoutId = sessionStorage.getItem('checkout_request_id') || '{{ $latestPayment->checkout_request_id ?? '' }}';

        const payload = {
            checkout_request_id: checkoutId || null,
            check_only: true,
            subscription_id: sessionStorage.getItem('subscription_id') || '{{ $userSubscription?->id ?? '' }}',
            phone: (document.getElementById('phone') ? document.getElementById('phone').value.trim() : '')
        };

        responseDiv.innerHTML = '<div class="alert alert-info">{{ __("payment.checking_payment") }}</div>';
        
        try {
            const res = await fetch("{{ route('subscription.manualStatusCheck') }}", {
                method: 'POST',
                headers: { 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            
            if (data.success) {
                // If successful and paid, update UI in-place
                if (data.transaction_status === 'success' || data.transaction_status === 'paid') {
                    const receipt = data.receipt_number || data.mpesa_receipt_number || null;
                    const startDate = data.start_date || null;
                    const endDate = data.end_date || null;
                    applyActivationUI(receipt, startDate, endDate);
                    return;
                }

                // Otherwise show a status message
                let statusMessage = '';
                let alertClass = '';

                switch(data.transaction_status) {
                    case 'failed':
                        statusMessage = '{{ __("payment.payment_failed") }}';
                        alertClass = 'danger';
                        break;
                    case 'pending':
                    default:
                        statusMessage = '{{ __("payment.payment_pending") }}';
                        alertClass = 'warning';
                        break;
                }

                responseDiv.innerHTML = `<div class="alert alert-${alertClass}">${statusMessage}</div>`;
            } else {
                responseDiv.innerHTML = `<div class="alert alert-warning">${data.message || '{{ __("payment.status_check_failed") }}'}</div>`;
            }
        } catch(err) {
            console.error(err);
            responseDiv.innerHTML = '<div class="alert alert-danger">{{ __("payment.payment_status_unknown") }}</div>';
        }
    }

    // Button bindings
    if(createBtn){
        createBtn.addEventListener('click', function() {
            const phone = phoneInput.value.trim();
            if (!/^2547\d{8}$/.test(phone)) {
                responseDiv.innerHTML = '<div class="alert alert-danger">{{ __("payment.invalid_phone_format") }}</div>';
                return;
            }
            
            // Check if this is a renewal or new subscription
            @if($userSubscription && ($userSubscription->status === 'pending' || $userSubscription->status === 'expired'))
                renewSubscription(getSelectedPlan(), phone);
            @else
                createSubscription(getSelectedPlan(), phone);
            @endif
        });
    }

    if(stkBtn){
        stkBtn.addEventListener('click', function() {
            const phone = phoneInput.value.trim();
            if (!/^2547\d{8}$/.test(phone)) {
                responseDiv.innerHTML = '<div class="alert alert-danger">{{ __("payment.invalid_phone_format") }}</div>';
                return;
            }
            
            // Check if this is a renewal or new subscription
            @if($userSubscription && ($userSubscription->status === 'pending' || $userSubscription->status === 'expired'))
                renewSubscription(getSelectedPlan(), phone);
            @else
                initiateStkPush(getSelectedPlan(), phone);
            @endif
        });
    }

    if(renewBtn){
        renewBtn.addEventListener('click', function() {
            // Show the subscription action panel when renew button is clicked
            subscriptionActionPanel.classList.remove('d-none');
        });
    }

    if(manualCheckBtn) {
        manualCheckBtn.addEventListener('click', checkPaymentStatusManually);
    }
    
    if(checkPaymentBtn) {
        checkPaymentBtn.addEventListener('click', checkPaymentStatusManually);
    }

    // Check for existing pending payment on page load
    const pendingPayment = '{{ $latestPayment && $latestPayment->transaction_status === "pending" ? "true" : "false" }}';
    if (pendingPayment === 'true') {
        responseDiv.innerHTML = '<div class="alert alert-info">{{ __("payment.payment_pending") }}</div>';
        const checkoutId = sessionStorage.getItem('checkout_request_id') || '{{ $latestPayment->checkout_request_id ?? '' }}';
        
        // Show payment pending UI
        showPaymentPendingUI();
        
        // Start the 5-second check interval for existing pending payments
        if (checkoutId) {
            startPaymentCheckInterval(checkoutId);
        }
    }

    // If subscription is active, hide the action panel and disable buttons
    if (hasActiveSubscription) {
        subscriptionActionPanel.classList.add('d-none');
        if(createBtn) createBtn.disabled = true;
        if(stkBtn) stkBtn.disabled = true;
        if(manualCheckBtn) manualCheckBtn.style.display = 'none';
    }

});
</script>
@endif

<style>
.subscription-details p {
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
}

.progress {
    background-color: #e9ecef;
}

.list-group-item {
    border-left: none;
    border-right: none;
}
.list-group-item:first-child {
    border-top: none;
}
</style>
@endsection

@push('styles')
<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea, #764ba2) !important;
    }
    
    .bg-opacity-20 {
        background-color: rgba(255, 255, 255, 0.2) !important;
    }
    
    .card {
        transition: all 0.3s ease;
        border: none !important;
    }
    
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15) !important;
    }
    
    .card-footer {
        border-top: none !important;
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
    
    .alert {
        border-radius: 0.75rem;
        border: none;
    }
    
    .badge {
        font-size: 0.75rem;
        padding: 0.5em 0.75em;
        border-radius: 0.5rem;
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
    
    .input-group-text {
        border-radius: 0.5rem 0 0 0.5rem;
        border: 1px solid #e3e6f0;
        background-color: #f8f9fc;
    }
    
    .table th {
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
        border-top: none;
    }
    
    .table td {
        vertical-align: middle;
        border-top: 1px solid #f3f4f6;
    }
    
    .subscription-status.active {
        background-color: #10b981 !important;
        color: white !important;
    }
    
    .subscription-status.pending {
        background-color: #f59e0b !important;
        color: white !important;
    }
    
    .subscription-status.expired {
        background-color: #6b7280 !important;
        color: white !important;
    }
    
    .subscription-status.cancelled {
        background-color: #ef4444 !important;
        color: white !important;
    }
    
    .progress {
        height: 0.5rem;
        background-color: rgba(78, 115, 223, 0.1);
    }
    
    .progress-bar {
        background: linear-gradient(90deg, #4e73df, #667eea);
    }
    
    .toast-container {
        z-index: 9999;
    }
    
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }
    
    .loading-spinner {
        background-color: white;
        padding: 2rem;
        border-radius: 1rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }
    
    .plan-card {
        position: relative;
        overflow: hidden;
    }
    
    .plan-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #4e73df, #667eea);
    }
    
    .plan-card.featured {
        border: 2px solid #4e73df !important;
        box-shadow: 0 0.75rem 1.5rem rgba(78, 115, 223, 0.2) !important;
    }
    
    .plan-card.featured::before {
        height: 6px;
        background: linear-gradient(90deg, #667eea, #764ba2);
    }
    
    .payment-history-card {
        max-height: 500px;
        overflow-y: auto;
    }
    
    .empty-state {
        padding: 3rem 1rem;
        text-align: center;
        color: #6c757d;
    }
    
    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
    
    .status-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 0.5rem;
    }
    
    .status-indicator.active {
        background-color: #10b981;
        box-shadow: 0 0 10px rgba(16, 185, 129, 0.5);
    }
    
    .status-indicator.pending {
        background-color: #f59e0b;
        box-shadow: 0 0 10px rgba(245, 158, 11, 0.5);
    }
    
    .status-indicator.expired {
        background-color: #6b7280;
    }
    
    .status-indicator.cancelled {
        background-color: #ef4444;
    }
    
    @media (max-width: 768px) {
        .card-body {
            padding: 1.5rem !important;
        }
        
        .container-fluid {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
        }
        
        .col-lg-3, .col-md-6 {
            margin-bottom: 1rem;
        }
    }
    
    .animate-fade-in {
        animation: fadeIn 0.5s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .animate-pulse {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
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
            }, index * 100);
        });
        
        // Auto-dismiss alerts after 5 seconds
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
        
        // Add loading overlay
        const loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'loading-overlay';
        loadingOverlay.innerHTML = `
            <div class="loading-spinner text-center">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mb-0 fw-medium">Processing payment...</p>
            </div>
        `;
        document.body.appendChild(loadingOverlay);
        
        // Show loading overlay when payments are being processed
        window.showLoadingOverlay = function() {
            loadingOverlay.style.display = 'flex';
        };
        
        window.hideLoadingOverlay = function() {
            loadingOverlay.style.display = 'none';
        };
        
        // Enhanced toast notifications
        window.showToast = function(type, message, title = '') {
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                        ${title ? `<strong>${title}</strong><br>` : ''}${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            // Add to toast container or create one
            let toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
                document.body.appendChild(toastContainer);
            }
            
            toastContainer.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast, { delay: 4000 });
            bsToast.show();
            
            // Remove from DOM after hiding
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        };
    });
</script>
@endpush