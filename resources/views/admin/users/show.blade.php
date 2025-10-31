@extends('layouts.app')

@section('title', __('payment.user_details'))

@section('content')
<div class="container-fluid py-4">
    <div class="card shadow-sm rounded-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('payment.user_details') }} - {{ $user->name }}</h5>
            <div>
                <a href="{{ route('admin.subscriptions.manual') }}?user_id={{ $user->id }}" class="btn btn-sm btn-success me-2">
                    <i class="fas fa-plus me-1"></i> {{ __('payment.create_subscription') }}
                </a>
                <a href="{{ route('admin.users') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-arrow-left me-1"></i> {{ __('payment.back_to_users') }}
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- User Information -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">{{ __('payment.user_information') }}</h6>
                            <span class="badge bg-{{ $user->status == 'active' ? 'success' : ($user->status == 'inactive' ? 'warning' : 'danger') }}">
                                {{ ucfirst($user->status) }}
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-borderless">
                                    <tr>
                                        <th width="120">{{ __('payment.id') }}:</th>
                                        <td>{{ $user->id }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('payment.name') }}:</th>
                                        <td>{{ $user->name }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('payment.email') }}:</th>
                                        <td>{{ $user->email }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('payment.username') }}:</th>
                                        <td>{{ $user->username ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('payment.phone') }}:</th>
                                        <td>{{ $user->phone ?? 'N/A' }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('payment.subscription_status') }}:</th>
                                        <td>
                                            <span class="badge {{ $user->has_active_subscription ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $user->has_active_subscription ? __('payment.active') : __('payment.inactive') }}
                                            </span>
                                        </td>
                                    </tr>
                                    @if($user->has_active_subscription)
                                    <tr>
                                        <th>{{ __('payment.subscription_expires') }}:</th>
                                        <td>{{ $user->subscription_expires_at ? $user->subscription_expires_at->format('M d, Y H:i') : 'N/A' }}</td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <th>{{ __('payment.created_at') }}:</th>
                                        <td>{{ $user->created_at->format('M d, Y H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <th>{{ __('payment.last_login') }}:</th>
                                        <td>{{ $user->last_login_at ? $user->last_login_at->format('M d, Y H:i') : 'Never' }}</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <!-- Status Update Form -->
                            <form action="{{ route('admin.users.update-status', $user->id) }}" method="POST" class="ajax-form mt-3">
                                @csrf
                                @method('PATCH')
                                <div class="d-flex align-items-center">
                                    <select name="status" class="form-select form-select-sm me-2" style="width: auto;">
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
                    <div class="card h-100">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">{{ __('payment.business_information') }}</h6>
                            @if($user->business)
                                <span class="badge {{ $user->business->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $user->business->is_active ? __('payment.active') : __('payment.inactive') }}
                                </span>
                            @endif
                        </div>
                        <div class="card-body">
                            @if($user->business)
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <th width="120">{{ __('payment.business_name') }}:</th>
                                            <td>{{ $user->business->name }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('payment.business_email') }}:</th>
                                            <td>{{ $user->business->email ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('payment.business_phone') }}:</th>
                                            <td>{{ $user->business->phone ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('payment.business_address') }}:</th>
                                            <td>{{ $user->business->address ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{ __('payment.registration_date') }}:</th>
                                            <td>{{ $user->business->created_at->format('M d, Y') }}</td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <!-- Business Status Update Form -->
                                <form action="{{ route('admin.business.update-status', $user->business->id) }}" method="POST" class="ajax-form mt-3">
                                    @csrf
                                    @method('PATCH')
                                    <div class="d-flex align-items-center">
                                        <select name="is_active" class="form-select form-select-sm me-2" style="width: auto;">
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
                                <div class="text-center py-4">
                                    <i class="fas fa-building fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">{{ __('payment.no_business_associated') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment History Section -->
            <div class="card mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">{{ __('payment.payment_history') }}</h6>
                    <span class="badge bg-primary">{{ $user->mpesaPayments->count() }}</span>
                </div>
                <div class="card-body">
                    @if($user->mpesaPayments && $user->mpesaPayments->count())
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('payment.id') }}</th>
                                        <th>{{ __('payment.amount') }}</th>
                                        <th>{{ __('payment.phone') }}</th>
                                        <th>{{ __('payment.receipt_number') }}</th>
                                        <th>{{ __('payment.status') }}</th>
                                        <th>{{ __('payment.date') }}</th>
                                        <th>{{ __('payment.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($user->mpesaPayments as $payment)
                                    <tr>
                                        <td>{{ $payment->id }}</td>
                                        <td>Ksh {{ number_format($payment->amount, 2) }}</td>
                                        <td>{{ $payment->phone_number }}</td>
                                        <td>{{ $payment->mpesa_receipt_number ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge 
                                                @if($payment->transaction_status == 'paid') bg-success
                                                @elseif($payment->transaction_status == 'pending') bg-warning
                                                @else bg-danger @endif">
                                                {{ ucfirst($payment->transaction_status) }}
                                            </span>
                                        </td>
                                        <td>{{ $payment->created_at->format('M d, Y H:i') }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-info view-payment-details" data-payment-id="{{ $payment->id }}" title="{{ __('payment.view_details') }}">
                                                    <i class="fas fa-eye"></i>
                                                </button>
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
                        <div class="text-center py-4">
                            <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                            <p class="text-muted">{{ __('payment.no_payments_found') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Subscriptions Section -->
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">{{ __('payment.subscriptions') }}</h6>
                    <span class="badge bg-primary">{{ $user->subscriptions->count() }}</span>
                </div>
                <div class="card-body">
                    @if($user->subscriptions && $user->subscriptions->count())
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('payment.id') }}</th>
                                        <th>{{ __('payment.plan') }}</th>
                                        <th>{{ __('payment.billing_cycle') }}</th>
                                        <th>{{ __('payment.amount') }}</th>
                                        <th>{{ __('payment.status') }}</th>
                                        <th>{{ __('payment.start_date') }}</th>
                                        <th>{{ __('payment.end_date') }}</th>
                                        <th>{{ __('payment.receipt') }}</th>
                                        <th>{{ __('payment.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($user->subscriptions as $sub)
                                    <tr>
                                        <td>{{ $sub->id }}</td>
                                        <td>{{ $sub->plan_name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="badge bg-info">{{ ucfirst($sub->billing_cycle) }}</span>
                                        </td>
                                        <td>Ksh {{ number_format($sub->amount, 2) }}</td>
                                        <td>
                                            <span class="badge 
                                                @if($sub->status == 'active') bg-success
                                                @elseif($sub->status == 'pending') bg-warning
                                                @elseif($sub->status == 'expired') bg-secondary
                                                @else bg-danger @endif">
                                                {{ ucfirst($sub->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $sub->start_date ? $sub->start_date->format('M d, Y') : 'N/A' }}</td>
                                        <td>{{ $sub->end_date ? $sub->end_date->format('M d, Y') : '—' }}</td>
                                        <td>{{ $sub->mpesa_receipt ?? 'N/A' }}</td>
                                        <td>
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
                        <div class="text-center py-4">
                            <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                            <p class="text-muted">{{ __('payment.no_subscriptions_found') }}</p>
                            <a href="{{ route('admin.subscriptions.manual') }}?user_id={{ $user->id }}" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i> {{ __('payment.create_subscription') }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
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