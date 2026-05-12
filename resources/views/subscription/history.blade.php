@extends('layouts.app')

@section('title', __('ui.subscription_history'))

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">{{ __('ui.subscription_history') }}</h4>
                </div>
                <div class="card-body">
                    @if($subscriptions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>{{ __('ui.plan_name') }}</th>
                                        <th>{{ __('ui.billing_cycle') }}</th>
                                        <th>{{ __('ui.amount') }}</th>
                                        <th>{{ __('ui.status') }}</th>
                                        <th>{{ __('ui.start_date') }}</th>
                                        <th>{{ __('ui.end_date') }}</th>
                                        <th>{{ __('ui.m_pesa_receipt') }}</th>
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
                                        <td>{{ $subscription->mpesa_receipt ?? __('ui.n_a') }}</td>
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
                            <i class="fas fa-info-circle"></i> {{ __('ui.no_subscription_history_found') }}
                        </div>
                    @endif

                    <div class="mt-3">
                        <a href="{{ route('dashboard') }}" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> {{ __('ui.back_to_dashboard') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection