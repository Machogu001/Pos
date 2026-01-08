@extends('layouts.app')

@section('title', __('payment.my_subscriptions'))

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="fw-bold">{{ __('payment.my_subscriptions') }}</h3>
        <a href="{{ route('user.dashboard') }}" class="btn btn-outline-secondary">{{ __('payment.back_to_dashboard') }}</a>
    </div>

    <div class="row">
        @forelse($subscriptions as $subscription)
            <div class="col-md-6 mb-3">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="mb-1">{{ $subscription->plan_name }}</h5>
                                <p class="mb-1 text-muted small">{{ ucfirst($subscription->billing_cycle) }} • Ksh {{ number_format($subscription->amount, 2) }}</p>
                                <p class="mb-0 small text-muted">{{ __('payment.period') }}: <strong>{{ optional($subscription->start_date)->format('Y-m-d') ?? '-' }}</strong> — <strong>{{ optional($subscription->end_date)->format('Y-m-d') ?? '-' }}</strong></p>
                            </div>
                            <div class="text-end">
                                <span class="badge @if($subscription->status === 'active') bg-success @elseif($subscription->status === 'pending') bg-warning @elseif($subscription->status === 'expired') bg-secondary @else bg-danger @endif text-capitalize">{{ $subscription->status }}</span>
                            </div>
                        </div>

                        <div class="mt-3 d-flex gap-2">
                            <a href="{{ route('subscription.invoice.download', $subscription->id) }}" class="btn btn-sm btn-primary" target="_blank"><i class="fas fa-file-invoice me-1"></i> {{ __('payment.download_invoice') }}</a>
                            <a href="{{ route('subscription.statement.download', $subscription->id) }}" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="fas fa-file-alt me-1"></i> {{ __('payment.download_statement') }}</a>
                            <!-- Inline small date-range form for statements -->
                            <form method="GET" action="{{ route('subscription.statement.download', $subscription->id) }}" class="d-inline-flex align-items-center">
                                <input type="date" name="start" class="form-control form-control-sm me-1" />
                                <input type="date" name="end" class="form-control form-control-sm me-1" />
                                <button class="btn btn-sm btn-outline-primary" type="submit">Download</button>
                            </form>
                            @if($subscription->pending_invoice_transaction_id)
                                <a href="{{ action([\App\Http\Controllers\SellController::class, 'show'], [$subscription->pending_invoice_transaction_id]) }}" class="btn btn-sm btn-outline-info" target="_blank"><i class="fas fa-receipt me-1"></i> View Pending Invoice</a>
                            @endif
                            @if($subscription->pending_mpesa_payment_id)
                                @php $mp = $subscription->pending_mpesa ?? null; @endphp
                                @if($mp && $mp->checkout_request_id)
                                    <a href="{{ route('mpesa.logs', ['checkout_request_id' => $mp->checkout_request_id]) }}" target="_blank" class="btn btn-sm btn-outline-warning"><i class="fas fa-mobile-alt me-1"></i> View Payment</a>
                                @else
                                    <span class="badge bg-light text-muted">Mpesa #{{ $subscription->pending_mpesa_payment_id }}</span>
                                @endif
                            @endif
                        </div>

                        <div class="mt-3 mt-auto d-flex justify-content-between align-items-center">
                            <small class="text-muted">Created: {{ optional($subscription->created_at)->format('Y-m-d') }}</small>
                            <small class="text-muted">ID: {{ $subscription->id }}</small>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card text-center py-5">
                    <div class="card-body">
                        <i class="fas fa-database fa-2x mb-2 text-muted"></i>
                        <p class="mb-0 text-muted">{{ __('payment.no_subscriptions_found') }}</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <div class="mt-3">
        {{ $subscriptions->links() }}
    </div>
</div>
@endsection
