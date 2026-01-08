@extends('layouts.app')

@section('title', 'Subscription History')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Subscription History</h4>
                </div>
                <div class="card-body">
                    @if($subscriptions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Plan Name</th>
                                        <th>Billing Cycle</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>M-Pesa Receipt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subscriptions as $subscription)
                                    <tr>
                                        <td>{{ $subscription->plan_name }}</td>
                                        <td>{{ ucfirst($subscription->billing_cycle) }}</td>
                                        <td>Ksh {{ number_format($subscription->amount, 2) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $subscription->status === 'active' ? 'success' : ($subscription->status === 'pending' ? 'warning' : ($subscription->status === 'expired' ? 'danger' : 'secondary')) }}">
                                                {{ ucfirst($subscription->status) }}
                                            </span>
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($subscription->start_date)->format('d M Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($subscription->end_date)->format('d M Y') }}</td>
                                        <td>{{ $subscription->mpesa_receipt ?? 'N/A' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-center">
                            {{ $subscriptions->links() }}
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No subscription history found.
                        </div>
                    @endif

                    <div class="mt-3">
                        <a href="{{ route('dashboard') }}" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection