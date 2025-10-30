@extends('layouts.app')

@section('title', __('payment.subscription_details'))

@section('content')
<div class="container-fluid py-4">
    <div class="card shadow-sm rounded-3">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-file-invoice me-2 text-primary"></i>
                    {{ __('payment.subscription_details') }}
                </h5>
                <a href="{{ route('admin.subscriptions') }}" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> {{ __('payment.back_to_subscriptions') }}
                </a>
            </div>
        </div>
        
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">{{ __('payment.subscription_info') }}</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">{{ __('payment.id') }}:</th>
                                    <td>{{ $subscription->id }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('payment.plan_name') }}:</th>
                                    <td>{{ $subscription->plan_name }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('payment.billing_cycle') }}:</th>
                                    <td>{{ ucfirst($subscription->billing_cycle) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('payment.amount') }}:</th>
                                    <td>Ksh {{ number_format($subscription->amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('payment.status') }}:</th>
                                    <td>
                                        <span class="badge bg-{{ $subscription->status === 'active' ? 'success' : ($subscription->status === 'pending' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($subscription->status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>{{ __('payment.start_date') }}:</th>
                                    <td>{{ $subscription->start_date->format('M d, Y') }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('payment.end_date') }}:</th>
                                    <td>{{ $subscription->end_date->format('M d, Y') }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('payment.days_remaining') }}:</th>
                                    <td>{{ now()->diffInDays($subscription->end_date) }} days</td>
                                </tr>
                                <tr>
                                    <th>{{ __('payment.created_at') }}:</th>
                                    <td>{{ $subscription->created_at->format('M d, Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">{{ __('payment.user_info') }}</h6>
                        </div>
                        <div class="card-body">
                            @if($subscription->user)
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">{{ __('payment.user') }}:</th>
                                    <td>{{ $subscription->user->name }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('payment.email') }}:</th>
                                    <td>{{ $subscription->user->email }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('payment.phone') }}:</th>
                                    <td>{{ $subscription->user->phone ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>{{ __('payment.status') }}:</th>
                                    <td>
                                        <span class="badge bg-{{ $subscription->user->status === 'active' ? 'success' : ($subscription->user->status === 'inactive' ? 'warning' : 'danger') }}">
                                            {{ ucfirst($subscription->user->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @if($subscription->user->business)
                                <tr>
                                    <th>{{ __('payment.business') }}:</th>
                                    <td>{{ $subscription->user->business->name }}</td>
                                </tr>
                                @endif
                            </table>
                            @else
                            <p class="text-muted">{{ __('payment.user_not_found') }}</p>
                            @endif
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">{{ __('payment.actions') }}</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                @if($subscription->status === 'pending')
                                <form action="{{ route('admin.subscriptions.activate', $subscription) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="fas fa-check me-1"></i> {{ __('payment.activate_subscription') }}
                                    </button>
                                </form>
                                @endif
                                
                                <a href="#" class="btn btn-outline-primary w-100">
                                    <i class="fas fa-sync me-1"></i> {{ __('payment.renew_subscription') }}
                                </a>
                                
                                <button class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                    <i class="fas fa-trash me-1"></i> {{ __('payment.delete_subscription') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('payment.confirm_delete') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>{{ __('payment.confirm_delete_subscription') }}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('payment.cancel') }}</button>
                <form action="{{ route('admin.subscriptions.destroy', $subscription) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">{{ __('payment.delete') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection