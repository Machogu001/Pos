@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Page Header -->
    <div class="tw-bg-white tw-shadow-sm tw-rounded-xl tw-mb-5">
        <div class="tw-p-4 sm:tw-p-6">
            <div class="tw-flex tw-flex-col sm:tw-flex-row tw-justify-between tw-items-start sm:tw-items-center tw-gap-4">
                <div class="tw-flex tw-items-center tw-gap-4">
                    <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-14 tw-h-14 tw-rounded-full tw-bg-green-100 tw-text-green-600 tw-shrink-0">
                        <i class="fas fa-mobile-alt fa-lg"></i>
                    </div>
                    <div>
                        <h4 class="tw-text-2xl tw-font-bold tw-text-gray-900 tw-mb-1">M-Pesa Payment Details</h4>
                        <p class="tw-text-sm tw-text-gray-500 tw-mb-0">Payment #{{ $mpesa->id }}</p>
                    </div>
                </div>
                <a href="{{ route('admin.subscriptions') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="row">
                <!-- Payment Information -->
                <div class="col-md-6">
                    <div class="tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-transition-all tw-duration-200 tw-mb-4">
                        <div class="tw-p-4 sm:tw-p-5 tw-border-b tw-border-gray-100">
                            <div class="tw-flex tw-items-center tw-gap-3">
                                <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full tw-bg-blue-100 tw-text-blue-600">
                                    <i class="fas fa-credit-card"></i>
                                </div>
                                <h6 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-0">Payment Information</h6>
                            </div>
                        </div>
                        <div class="tw-p-4 sm:tw-p-5">
                            <div class="tw-space-y-3">
                                <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                                    <span class="tw-text-sm tw-font-medium tw-text-gray-600">ID</span>
                                    <span class="tw-text-sm tw-font-semibold tw-text-gray-900">#{{ $mpesa->id }}</span>
                                </div>
                                <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                                    <span class="tw-text-sm tw-font-medium tw-text-gray-600">Amount</span>
                                    <span class="tw-text-sm tw-font-bold tw-text-gray-900">Ksh {{ number_format($mpesa->amount, 2) }}</span>
                                </div>
                                <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                                    <span class="tw-text-sm tw-font-medium tw-text-gray-600">Phone Number</span>
                                    <span class="tw-text-sm tw-text-gray-900">{{ $mpesa->phone_number }}</span>
                                </div>
                                <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                                    <span class="tw-text-sm tw-font-medium tw-text-gray-600">Receipt Number</span>
                                    @if($mpesa->mpesa_receipt_number)
                                        <code class="tw-text-green-700 tw-bg-green-50 tw-px-2 tw-py-1 tw-rounded tw-text-xs">{{ $mpesa->mpesa_receipt_number }}</code>
                                    @else
                                        <span class="tw-text-gray-400">—</span>
                                    @endif
                                </div>
                                <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                                    <span class="tw-text-sm tw-font-medium tw-text-gray-600">Transaction Status</span>
                                    <span class="badge bg-{{ $mpesa->transaction_status === 'paid' ? 'success' : ($mpesa->transaction_status === 'pending' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($mpesa->transaction_status) }}
                                    </span>
                                </div>
                                <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                                    <span class="tw-text-sm tw-font-medium tw-text-gray-600">Account Reference</span>
                                    <span class="tw-text-sm tw-text-gray-900">{{ $mpesa->account_reference ?? '—' }}</span>
                                </div>
                                <div class="tw-flex tw-justify-between tw-py-2">
                                    <span class="tw-text-sm tw-font-medium tw-text-gray-600">Paid At</span>
                                    <span class="tw-text-sm tw-text-gray-900">{{ $mpesa->paid_at ?? '—' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User & Relationship Information -->
                <div class="col-md-6">
                    <div class="tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-transition-all tw-duration-200 tw-mb-4">
                        <div class="tw-p-4 sm:tw-p-5 tw-border-b tw-border-gray-100">
                            <div class="tw-flex tw-items-center tw-gap-3">
                                <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full tw-bg-purple-100 tw-text-purple-600">
                                    <i class="fas fa-user"></i>
                                </div>
                                <h6 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-0">User Information</h6>
                            </div>
                        </div>
                        <div class="tw-p-4 sm:tw-p-5">
                            <div class="tw-space-y-3">
                                <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                                    <span class="tw-text-sm tw-font-medium tw-text-gray-600">User</span>
                                    <span class="tw-text-sm tw-text-gray-900">{{ optional($mpesa->user)->name ?? '—' }} <span class="tw-text-xs tw-text-gray-400">(ID: {{ $mpesa->user_id }})</span></span>
                                </div>
                                <div class="tw-flex tw-justify-between tw-py-2 tw-border-b tw-border-gray-50">
                                    <span class="tw-text-sm tw-font-medium tw-text-gray-600">Subscription</span>
                                    @if($mpesa->subscription)
                                        <a href="{{ route('admin.subscriptions.show', $mpesa->subscription->id) }}" class="tw-text-sm tw-text-blue-600 hover:tw-text-blue-800 tw-font-medium">
                                            Subscription #{{ $mpesa->subscription->id }}
                                        </a>
                                    @else
                                        <span class="tw-text-gray-400">—</span>
                                    @endif
                                </div>
                                <div class="tw-flex tw-justify-between tw-py-2">
                                    <span class="tw-text-sm tw-font-medium tw-text-gray-600">Consumed By Transaction</span>
                                    @if($mpesa->consumed_by_transaction_id)
                                        <span class="tw-text-sm tw-font-medium tw-text-blue-600">
                                            Tx #{{ $mpesa->consumed_by_transaction_id }}
                                        </span>
                                    @else
                                        <span class="tw-text-gray-400">—</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Technical Details -->
                <div class="col-md-12">
                    <div class="tw-bg-white tw-shadow-sm hover:tw-shadow-md tw-rounded-xl tw-transition-all tw-duration-200">
                        <div class="tw-p-4 sm:tw-p-5 tw-border-b tw-border-gray-100">
                            <div class="tw-flex tw-items-center tw-gap-3">
                                <div class="tw-inline-flex tw-items-center tw-justify-center tw-w-10 tw-h-10 tw-rounded-full tw-bg-orange-100 tw-text-orange-600">
                                    <i class="fas fa-code"></i>
                                </div>
                                <h6 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-0">Technical Details</h6>
                            </div>
                        </div>
                        <div class="tw-p-4 sm:tw-p-5">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="tw-space-y-3">
                                        <div class="tw-py-2">
                                            <span class="tw-text-sm tw-font-medium tw-text-gray-600 tw-block tw-mb-1">Checkout Request ID</span>
                                            <code class="tw-text-xs tw-text-gray-700 tw-bg-gray-50 tw-px-3 tw-py-2 tw-rounded tw-block tw-break-all">{{ $mpesa->checkout_request_id ?? '—' }}</code>
                                        </div>
                                        <div class="tw-py-2">
                                            <span class="tw-text-sm tw-font-medium tw-text-gray-600 tw-block tw-mb-1">Merchant Request ID</span>
                                            <code class="tw-text-xs tw-text-gray-700 tw-bg-gray-50 tw-px-3 tw-py-2 tw-rounded tw-block tw-break-all">{{ $mpesa->merchant_request_id ?? '—' }}</code>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="tw-space-y-3">
                                        <div class="tw-py-2">
                                            <span class="tw-text-sm tw-font-medium tw-text-gray-600 tw-block tw-mb-1">Result Code</span>
                                            <code class="tw-text-xs tw-text-gray-700 tw-bg-gray-50 tw-px-3 tw-py-2 tw-rounded tw-block">{{ $mpesa->result_code ?? '—' }}</code>
                                        </div>
                                        <div class="tw-py-2">
                                            <span class="tw-text-sm tw-font-medium tw-text-gray-600 tw-block tw-mb-1">Result Description</span>
                                            <code class="tw-text-xs tw-text-gray-700 tw-bg-gray-50 tw-px-3 tw-py-2 tw-rounded tw-block tw-break-all">{{ $mpesa->result_desc ?? '—' }}</code>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
