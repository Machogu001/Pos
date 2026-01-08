@extends('layouts.app')

@section('title', __('payment.subscription_details'))

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Page Header -->
    <div class="tw-bg-white tw-shadow-sm tw-rounded-xl tw-mb-5">
        <div class="tw-p-4 sm:tw-p-6">
            <div class="tw-flex tw-flex-col sm:tw-flex-row tw-justify-between tw-items-start sm:tw-items-center tw-gap-4">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-14 tw-h-14 tw-rounded-full tw-bg-blue-100 tw-text-blue-600 tw-shrink-0">
                        <i class="fas fa-file-invoice fa-lg"></i>
                    </div>
                    <div>
                        <h4 class="tw-text-2xl tw-font-bold tw-text-gray-900 tw-mb-1">{{ __('payment.subscription_details') }}</h4>
                        <p class="tw-text-sm tw-text-gray-500 tw-mb-0">Subscription #{{ $subscription->id }}</p>
                    </div>
                </div>
                <a href="{{ route('admin.subscriptions') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-arrow-left me-1"></i> {{ __('payment.back_to_subscriptions') }}
                </a>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <!-- Subscription Information -->
            <div class="tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-transition-all tw-duration-200 tw-mb-4">
                <div class="tw-p-4 sm:tw-p-5 tw-border-b tw-border-gray-100">
                    <div class="tw-flex tw-items-center tw-gap-3">
                        <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full tw-bg-purple-100 tw-text-purple-600">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <h6 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-0">{{ __('payment.subscription_info') }}</h6>
                    </div>
                </div>
                <div class="tw-p-4 sm:tw-p-5">
                    <div class="tw-space-y-3">
                        <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                            <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.id') }}</span>
                            <span class="tw-text-sm tw-font-semibold tw-text-gray-900">#{{ $subscription->id }}</span>
                        </div>
                        <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                            <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.plan_name') }}</span>
                            <span class="tw-text-sm tw-font-semibold tw-text-gray-900">{{ $subscription->plan_name }}</span>
                        </div>
                        <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                            <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.billing_cycle') }}</span>
                            <span class="badge bg-info">{{ ucfirst($subscription->billing_cycle) }}</span>
                        </div>
                        <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                            <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.amount') }}</span>
                            <span class="tw-text-sm tw-font-bold tw-text-gray-900">Ksh {{ number_format($subscription->amount, 2) }}</span>
                        </div>
                        <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                            <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.status') }}</span>
                            <span class="badge bg-{{ $subscription->status === 'active' ? 'success' : ($subscription->status === 'pending' ? 'warning' : 'danger') }}">
                                {{ ucfirst($subscription->status) }}
                            </span>
                        </div>
                        <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                            <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.start_date') }}</span>
                            <span class="tw-text-sm tw-text-gray-900">{{ $subscription->start_date->format('M d, Y') }}</span>
                        </div>
                        <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                            <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.end_date') }}</span>
                            <span class="tw-text-sm tw-text-gray-900">{{ $subscription->end_date->format('M d, Y') }}</span>
                        </div>
                        <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                            <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.days_remaining') }}</span>
                            <span class="tw-text-sm tw-font-semibold tw-text-blue-600">{{ now()->diffInDays($subscription->end_date) }} days</span>
                        </div>
                        <div class="tw-flex tw-justify-between tw-py-2">
                            <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.created_at') }}</span>
                            <span class="tw-text-sm tw-text-gray-900">{{ $subscription->created_at->format('M d, Y H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <!-- User Information -->
            <div class="tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-transition-all tw-duration-200 tw-mb-4">
                <div class="tw-p-4 sm:tw-p-5 tw-border-b tw-border-gray-100">
                    <div class="tw-flex tw-items-center tw-gap-3">
                        <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full tw-bg-green-100 tw-text-green-600">
                            <i class="fas fa-user"></i>
                        </div>
                        <h6 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-0">{{ __('payment.user_info') }}</h6>
                    </div>
                </div>
                <div class="tw-p-4 sm:tw-p-5">
                    @if($subscription->user)
                        <div class="tw-space-y-3">
                            <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                                <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.user') }}</span>
                                <span class="tw-text-sm tw-font-semibold tw-text-gray-900">{{ $subscription->user->name }}</span>
                            </div>
                            <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                                <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.email') }}</span>
                                <span class="tw-text-sm tw-text-gray-900">{{ $subscription->user->email }}</span>
                            </div>
                            <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                                <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.phone') }}</span>
                                <span class="tw-text-sm tw-text-gray-900">{{ $subscription->user->phone ?? '—' }}</span>
                            </div>
                            <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                                <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.status') }}</span>
                                <span class="badge bg-{{ $subscription->user->status === 'active' ? 'success' : ($subscription->user->status === 'inactive' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($subscription->user->status) }}
                                </span>
                            </div>
                            @if($subscription->user->business)
                            <div class="tw-flex tw-justify-between tw-py-2">
                                <span class="tw-text-sm tw-font-medium tw-text-gray-600">{{ __('payment.business') }}</span>
                                <span class="tw-text-sm tw-font-semibold tw-text-gray-900">{{ $subscription->user->business->name }}</span>
                            </div>
                            @endif
                        </div>
                    @else
                        <div class="tw-text-center tw-py-8">
                            <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-16 tw-h-16 tw-rounded-full tw-bg-gray-100 tw-text-gray-400 tw-mb-3">
                                <i class="fas fa-user-slash fa-2x"></i>
                            </div>
                            <p class="tw-text-gray-500 tw-mb-0">{{ __('payment.user_not_found') }}</p>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Actions -->
            <div class="tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-transition-all tw-duration-200">
                <div class="tw-p-4 sm:tw-p-5 tw-border-b tw-border-gray-100">
                    <div class="tw-flex tw-items-center tw-gap-3">
                        <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full tw-bg-orange-100 tw-text-orange-600">
                            <i class="fas fa-cog"></i>
                        </div>
                        <h6 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-0">{{ __('payment.actions') }}</h6>
                    </div>
                </div>
                <div class="tw-p-4 sm:tw-p-5">
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