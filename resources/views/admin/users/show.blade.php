@extends('layouts.app')

@section('title', __('payment.user_details'))

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Page Header -->
    <div class="tw-bg-white tw-shadow-sm tw-rounded-xl tw-mb-5">
        <div class="tw-p-4 sm:tw-p-6">
            <div class="tw-flex tw-flex-col sm:tw-flex-row tw-justify-between tw-items-start sm:tw-items-center tw-gap-4">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-14 tw-h-14 tw-rounded-full tw-bg-blue-100 tw-text-blue-600 tw-font-semibold tw-text-xl tw-shrink-0">
                        {{ substr($user->name ?? 'U', 0, 1) }}
                    </div>
                    <div>
                        <h4 class="tw-text-2xl tw-font-bold tw-text-gray-900 tw-mb-1">{{ $user->name }}</h4>
                        <p class="tw-text-sm tw-text-gray-500 tw-mb-0">{{ __('payment.user_details') }}</p>
                    </div>
                </div>
                <div class="tw-flex tw-gap-2">
                    <a href="{{ route('admin.subscriptions.manual') }}?user_id={{ $user->id }}" class="btn btn-sm btn-success">
                        <i class="fas fa-plus me-1"></i> {{ __('payment.create_subscription') }}
                    </a>
                    <a href="{{ route('admin.users') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-arrow-left me-1"></i> {{ __('payment.back_to_users') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- User & Business Information -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-transition-all tw-duration-200 tw-h-full">
                <div class="tw-p-4 sm:tw-p-5 tw-border-b tw-border-gray-100">
                    <div class="tw-flex tw-justify-between tw-items-center">
                        <div class="tw-flex tw-items-center tw-gap-3">
                            <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full tw-bg-purple-100 tw-text-purple-600">
                                <i class="fas fa-user"></i>
                            </div>
                            <h6 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-0">{{ __('payment.user_information') }}</h6>
                        </div>
                        <span class="badge bg-{{ $user->status == 'active' ? 'success' : ($user->status == 'inactive' ? 'warning' : 'danger') }}">
                            {{ ucfirst($user->status) }}
                        </span>
                    </div>
                </div>
                <div class="tw-p-4 sm:tw-p-5">
                    <div class="tw-space-y-3">
                        <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                            <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.id') }}</span>
                            <span class="tw-text-sm tw-text-gray-900 tw-font-semibold">#{{ $user->id }}</span>
                        </div>
                        <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                            <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.name') }}</span>
                            <span class="tw-text-sm tw-text-gray-900">{{ $user->name }}</span>
                        </div>
                        <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                            <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.email') }}</span>
                            <span class="tw-text-sm tw-text-gray-900">{{ $user->email }}</span>
                        </div>
                        <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                            <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.username') }}</span>
                            <span class="tw-text-sm tw-text-gray-900">{{ $user->username ?? '—' }}</span>
                        </div>
                        <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                            <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.phone') }}</span>
                            <span class="tw-text-sm tw-text-gray-900">{{ $user->phone ?? '—' }}</span>
                        </div>
                        <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                            <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.subscription_status') }}</span>
                            <span class="badge {{ $user->has_active_subscription ? 'bg-success' : 'bg-secondary' }}">
                                {{ $user->has_active_subscription ? __('payment.active') : __('payment.inactive') }}
                            </span>
                        </div>
                        @if($user->has_active_subscription)
                        <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                            <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.subscription_expires') }}</span>
                            <span class="tw-text-sm tw-text-gray-900">{{ $user->subscription_expires_at ? $user->subscription_expires_at->format('M d, Y H:i') : '—' }}</span>
                        </div>
                        @endif
                        <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                            <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.created_at') }}</span>
                            <span class="tw-text-sm tw-text-gray-900">{{ $user->created_at->format('M d, Y H:i') }}</span>
                        </div>
                        <div class="tw-flex tw-justify-between tw-py-2">
                            <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.last_login') }}</span>
                            <span class="tw-text-sm tw-text-gray-900">{{ $user->last_login_at ? $user->last_login_at->format('M d, Y H:i') : 'Never' }}</span>
                        </div>
                    </div>
                    
                    <!-- Status Update Form -->
                    <form action="{{ route('admin.users.update-status', $user->id) }}" method="POST" class="ajax-form mt-4 pt-4 tw-border-t tw-border-gray-100">
                        @csrf
                        @method('PATCH')
                        <label class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-2 tw-block">Update Status</label>
                        <div class="tw-flex tw-gap-2">
                            <select name="status" class="form-select form-select-sm tw-flex-1">
                                <option value="active" {{ $user->status === 'active' ? 'selected' : '' }}>{{ __('payment.active') }}</option>
                                <option value="inactive" {{ $user->status === 'inactive' ? 'selected' : '' }}>{{ __('payment.inactive') }}</option>
                                <option value="terminated" {{ $user->status === 'terminated' ? 'selected' : '' }}>{{ __('payment.terminated') }}</option>
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary">
                                <span class="btn-text">{{ __('payment.update_status') }}</span>
                                <span class="btn-loading d-none">
                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Business Information (if available) -->
        <div class="col-md-6">
            <div class="tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-transition-all tw-duration-200 tw-h-full">
                <div class="tw-p-4 sm:tw-p-5 tw-border-b tw-border-gray-100">
                    <div class="tw-flex tw-justify-between tw-items-center">
                        <div class="tw-flex tw-items-center tw-gap-3">
                            <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full tw-bg-green-100 tw-text-green-600">
                                <i class="fas fa-building"></i>
                            </div>
                            <h6 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-0">{{ __('payment.business_information') }}</h6>
                        </div>
                        @if($user->business)
                            <span class="badge {{ $user->business->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $user->business->is_active ? __('payment.active') : __('payment.inactive') }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="tw-p-4 sm:tw-p-5">
                    @if($user->business)
                        <div class="tw-space-y-3">
                            <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                                <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.business_name') }}</span>
                                <span class="tw-text-sm tw-text-gray-900 tw-font-semibold">{{ $user->business->name }}</span>
                            </div>
                            <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                                <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.business_email') }}</span>
                                <span class="tw-text-sm tw-text-gray-900">{{ $user->business->email ?? '—' }}</span>
                            </div>
                            <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                                <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.business_phone') }}</span>
                                <span class="tw-text-sm tw-text-gray-900">{{ $user->business->phone ?? '—' }}</span>
                            </div>
                            <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                                <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.business_address') }}</span>
                                <span class="tw-text-sm tw-text-gray-900 tw-text-end">{{ $user->business->address ?? '—' }}</span>
                            </div>
                            <div class="tw-flex tw-justify-between tw-py-2">
                                <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.registration_date') }}</span>
                                <span class="tw-text-sm tw-text-gray-900">{{ $user->business->created_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                        
                        <!-- Business Status Update Form -->
                        <form action="{{ route('admin.business.update-status', $user->business->id) }}" method="POST" class="ajax-form mt-4 pt-4 tw-border-t tw-border-gray-100">
                            @csrf
                            @method('PATCH')
                            <label class="tw-text-sm tw-font-medium tw-text-gray-600 tw-mb-2 tw-block">Update Business Status</label>
                            <div class="tw-flex tw-gap-2">
                                <select name="is_active" class="form-select form-select-sm tw-flex-1">
                                    <option value="1" {{ $user->business->is_active ? 'selected' : '' }}>{{ __('payment.active') }}</option>
                                    <option value="0" {{ !$user->business->is_active ? 'selected' : '' }}>{{ __('payment.inactive') }}</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <span class="btn-text">{{ __('payment.update_status') }}</span>
                                    <span class="btn-loading d-none">
                                        <span class="spinner-border spinner-border-sm" role="status"></span>
                                    </span>
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="tw-text-center tw-py-12">
                            <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-16 tw-h-16 tw-rounded-full tw-bg-gray-100 tw-text-gray-400 tw-mb-3">
                                <i class="fas fa-building fa-2x"></i>
                            </div>
                            <p class="tw-text-gray-500 tw-mb-0">{{ __('payment.no_business_associated') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Payment History Section -->
    <div class="tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-transition-all tw-duration-200 tw-mb-4">
        <div class="tw-p-4 sm:tw-p-5 tw-border-b tw-border-gray-100">
            <div class="tw-flex tw-justify-between tw-items-center">
                <div class="tw-flex tw-items-center tw-gap-3">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full tw-bg-indigo-100 tw-text-indigo-600">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h6 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-0">{{ __('payment.payment_history') }}</h6>
                </div>
                <span class="tw-inline-flex tw-items-center tw-justify-center tw-px-3 tw-py-1 tw-rounded-full tw-bg-blue-100 tw-text-blue-700 tw-text-sm tw-font-semibold">
                    {{ $user->mpesaPayments->count() }}
                </span>
            </div>
        </div>
        <div class="tw-p-0">
            @if($user->mpesaPayments && $user->mpesaPayments->count())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">{{ __('payment.id') }}</th>
                                <th>{{ __('payment.amount') }}</th>
                                <th>{{ __('payment.phone') }}</th>
                                <th>{{ __('payment.receipt_number') }}</th>
                                <th>{{ __('payment.status') }}</th>
                                <th>{{ __('payment.date') }}</th>
                                <th class="pe-4">{{ __('payment.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($user->mpesaPayments as $payment)
                            <tr>
                                <td class="ps-4">
                                    <code class="tw-text-gray-600 tw-bg-gray-50 tw-px-2 tw-py-1 tw-rounded">#{{ $payment->id }}</code>
                                </td>
                                <td><span class="tw-font-semibold tw-text-gray-900">Ksh {{ number_format($payment->amount, 2) }}</span></td>
                                <td><span class="tw-text-gray-600">{{ $payment->phone_number }}</span></td>
                                <td>
                                    @if($payment->mpesa_receipt_number)
                                        <code class="tw-text-green-700 tw-bg-green-50 tw-px-2 tw-py-1 tw-rounded tw-text-xs">{{ $payment->mpesa_receipt_number }}</code>
                                    @else
                                        <span class="tw-text-gray-400">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge 
                                        @if($payment->transaction_status == 'paid') bg-success
                                        @elseif($payment->transaction_status == 'pending') bg-warning
                                        @else bg-danger @endif">
                                        {{ ucfirst($payment->transaction_status) }}
                                    </span>
                                </td>
                                <td><span class="tw-text-sm tw-text-gray-600">{{ $payment->created_at->format('M d, Y H:i') }}</span></td>
                                <td class="pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.mpesa_payments.show', $payment->id) }}" class="btn btn-info" title="{{ __('payment.view_details') }}">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($payment->transaction_status === 'pending')
                                        <form action="{{ route('admin.payments.manual-status-check') }}" method="POST" class="ajax-form d-inline">
                                            @csrf
                                            <input type="hidden" name="checkout_request_id" value="{{ $payment->checkout_request_id }}">
                                            <button type="submit" class="btn btn-warning" title="{{ __('payment.check_status') }}">
                                                <span class="btn-text">
                                                    <i class="fas fa-sync-alt"></i>
                                                </span>
                                                <span class="btn-loading d-none">
                                                    <span class="spinner-border spinner-border-sm" role="status"></span>
                                                </span>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="tw-text-center tw-py-12">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-16 tw-h-16 tw-rounded-full tw-bg-gray-100 tw-text-gray-400 tw-mb-3">
                        <i class="fas fa-credit-card fa-2x"></i>
                    </div>
                    <p class="tw-text-gray-500 tw-mb-0">{{ __('payment.no_payments_found') }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Subscriptions Section -->
    <div class="tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-transition-all tw-duration-200">
        <div class="tw-p-4 sm:tw-p-5 tw-border-b tw-border-gray-100">
            <div class="tw-flex tw-justify-between tw-items-center">
                <div class="tw-flex tw-items-center tw-gap-3">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full tw-bg-orange-100 tw-text-orange-600">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <h6 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-0">{{ __('payment.subscriptions') }}</h6>
                </div>
                <span class="tw-inline-flex tw-items-center tw-justify-center tw-px-3 tw-py-1 tw-rounded-full tw-bg-blue-100 tw-text-blue-700 tw-text-sm tw-font-semibold">
                    {{ $user->subscriptions->count() }}
                </span>
            </div>
        </div>
        <div class="tw-p-0">
            @if($user->subscriptions && $user->subscriptions->count())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">{{ __('payment.id') }}</th>
                                <th>{{ __('payment.plan') }}</th>
                                <th>{{ __('payment.billing_cycle') }}</th>
                                <th>{{ __('payment.amount') }}</th>
                                <th>{{ __('payment.status') }}</th>
                                <th>{{ __('payment.start_date') }}</th>
                                <th>{{ __('payment.end_date') }}</th>
                                <th>{{ __('payment.receipt') }}</th>
                                <th class="pe-4">{{ __('payment.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($user->subscriptions as $sub)
                            <tr>
                                <td class="ps-4">
                                    <code class="tw-text-gray-600 tw-bg-gray-50 tw-px-2 tw-py-1 tw-rounded">#{{ $sub->id }}</code>
                                </td>
                                <td><span class="tw-font-medium tw-text-gray-900">{{ $sub->plan_name ?? '—' }}</span></td>
                                <td>
                                    <span class="badge bg-info">{{ ucfirst($sub->billing_cycle) }}</span>
                                </td>
                                <td><span class="tw-font-semibold tw-text-gray-900">Ksh {{ number_format($sub->amount, 2) }}</span></td>
                                <td>
                                    <span class="badge 
                                        @if($sub->status == 'active') bg-success
                                        @elseif($sub->status == 'pending') bg-warning
                                        @elseif($sub->status == 'expired') bg-secondary
                                        @else bg-danger @endif">
                                        {{ ucfirst($sub->status) }}
                                    </span>
                                </td>
                                <td><span class="tw-text-sm tw-text-gray-600">{{ $sub->start_date ? $sub->start_date->format('M d, Y') : '—' }}</span></td>
                                <td><span class="tw-text-sm tw-text-gray-600">{{ $sub->end_date ? $sub->end_date->format('M d, Y') : '—' }}</span></td>
                                <td>
                                    @if($sub->mpesa_receipt)
                                        <code class="tw-text-green-700 tw-bg-green-50 tw-px-2 tw-py-1 tw-rounded tw-text-xs">{{ $sub->mpesa_receipt }}</code>
                                    @else
                                        <span class="tw-text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="pe-4">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.subscriptions.show', $sub->id) }}" class="btn btn-info" title="{{ __('payment.view_details') }}">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($sub->status === 'active' || $sub->status === 'expired')
                                            <form action="{{ route('admin.subscriptions.renew', $sub) }}" method="POST" class="ajax-form d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-success" title="{{ __('payment.renew') }}">
                                                    <span class="btn-text">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </span>
                                                    <span class="btn-loading d-none">
                                                        <span class="spinner-border spinner-border-sm" role="status"></span>
                                                    </span>
                                                </button>
                                            </form>
                                        @endif
                                        @if($sub->status === 'active')
                                            <form action="{{ route('admin.subscriptions.cancel', $sub->id) }}" method="POST" class="ajax-form d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger" title="{{ __('payment.cancel') }}" onclick="return confirm('Are you sure you want to cancel this subscription?')">
                                                    <span class="btn-text">
                                                        <i class="fas fa-times"></i>
                                                    </span>
                                                    <span class="btn-loading d-none">
                                                        <span class="spinner-border spinner-border-sm" role="status"></span>
                                                    </span>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="tw-text-center tw-py-12">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-16 tw-h-16 tw-rounded-full tw-bg-gray-100 tw-text-gray-400 tw-mb-3">
                        <i class="fas fa-receipt fa-2x"></i>
                    </div>
                    <p class="tw-text-gray-500 tw-mb-3">{{ __('payment.no_subscriptions_found') }}</p>
                    <a href="{{ route('admin.subscriptions.manual') }}?user_id={{ $user->id }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i> {{ __('payment.create_subscription') }}
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Payment Details Modal -->
<div class="modal fade" id="paymentDetailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('payment.payment_details') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="paymentDetailsContent">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('payment.close') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- Success/Error Messages Container -->
<div id="ajaxAlerts" class="position-fixed top-0 end-0 p-3" style="z-index: 1050; max-width: 350px;"></div>
@endsection

@push('styles')
<style>
.btn-loading .spinner-border-sm {
    width: 1rem;
    height: 1rem;
}
.ajax-form {
    display: inline-block;
}
.table-borderless th {
    font-weight: 500;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    // AJAX forms are handled centrally in /public/js/ajax-forms.js
    console.log('Using centralized AJAX form handler for .ajax-form');

    // Payment details modal
    document.querySelectorAll('.view-payment-details').forEach(button => {
        button.addEventListener('click', function() {
            const paymentId = this.getAttribute('data-payment-id');
            const modal = new bootstrap.Modal(document.getElementById('paymentDetailsModal'));
            
            // Show loading in modal
            document.getElementById('paymentDetailsContent').innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2">Loading payment details...</p>
                </div>
            `;
            
            modal.show();
            
            // Fetch payment details
            fetch(`/admin/payments/${paymentId}/details`, {
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
                    "Accept": "application/json",
                    "X-Requested-With": "XMLHttpRequest"
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('paymentDetailsContent').innerHTML = data.html;
                } else {
                    document.getElementById('paymentDetailsContent').innerHTML = `
                        <div class="alert alert-danger">
                            Failed to load payment details: ${data.message}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading payment details:', error);
                document.getElementById('paymentDetailsContent').innerHTML = `
                    <div class="alert alert-danger">
                        Error loading payment details. Please try again.
                    </div>
                `;
            });
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
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
});
</script>
@endpush