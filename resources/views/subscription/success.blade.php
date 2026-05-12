@extends('layouts.app')

@section('title', __('ui.subscription_success'))

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">{{ __('ui.subscription_activated_successfully') }}</h4>
                </div>
                <div class="card-body">
                    @if($subscription)
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> {{ __('ui.your_subscription_has_been_activated_successfully') }}
                        </div>
                        
                        <div class="subscription-details">
                            <h5>{{ __('ui.subscription_details') }}</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <th>{{ __('ui.plan_name_2') }}</th>
                                    <td>{{ $subscription->plan_name }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('ui.billing_cycle_2') }}</th>
                                    <td>{{ ucfirst($subscription->billing_cycle) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('ui.amount_paid') }}</th>
                                    <td>Ksh {{ number_format($subscription->amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('ui.m_pesa_receipt_2') }}</th>
                                    <td>{{ $subscription->mpesa_receipt ?? __('ui.n_a') }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('ui.start_date_2') }}</th>
                                    <td>{{ \Carbon\Carbon::parse($subscription->start_date)->format('d M Y') }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('ui.expiry_date') }}</th>
                                    <td>{{ \Carbon\Carbon::parse($subscription->end_date)->format('d M Y') }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('ui.status_2') }}</th>
                                    <td><span class="badge bg-success">{{ __('ui.active') }}</span></td>
                                </tr>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> {{ __('ui.subscription_activated_successfully_but_details_are_not_available') }}
                        </div>
                    @endif

                    <div class="mt-4">
                        <a href="{{ route('dashboard') }}" class="btn btn-primary">
                            <i class="fas fa-home"></i> {{ __('ui.go_to_dashboard') }}
                        </a>
                        <a href="{{ route('subscription.history') }}" class="btn btn-outline-primary ms-2">
                            <i class="fas fa-history"></i> {{ __('ui.view_subscription_history') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection