@extends('layouts.app')

@section('title', 'Subscription Success')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Subscription Activated Successfully!</h4>
                </div>
                <div class="card-body">
                    @if($subscription)
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> Your subscription has been activated successfully.
                        </div>
                        
                        <div class="subscription-details">
                            <h5>Subscription Details:</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Plan Name:</th>
                                    <td>{{ $subscription->plan_name }}</td>
                                </tr>
                                <tr>
                                    <th>Billing Cycle:</th>
                                    <td>{{ ucfirst($subscription->billing_cycle) }}</td>
                                </tr>
                                <tr>
                                    <th>Amount Paid:</th>
                                    <td>Ksh {{ number_format($subscription->amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>M-Pesa Receipt:</th>
                                    <td>{{ $subscription->mpesa_receipt ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Start Date:</th>
                                    <td>{{ \Carbon\Carbon::parse($subscription->start_date)->format('d M Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Expiry Date:</th>
                                    <td>{{ \Carbon\Carbon::parse($subscription->end_date)->format('d M Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td><span class="badge bg-success">Active</span></td>
                                </tr>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Subscription activated successfully, but details are not available.
                        </div>
                    @endif

                    <div class="mt-4">
                        <a href="{{ route('dashboard') }}" class="btn btn-primary">
                            <i class="fas fa-home"></i> Go to Dashboard
                        </a>
                        <a href="{{ route('subscription.history') }}" class="btn btn-outline-primary ms-2">
                            <i class="fas fa-history"></i> View Subscription History
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection