@extends('layouts.auth2')

@section('title', __('lang_v1.register'))

@section('content')
@php
use App\MpesaPayment;
    $phone = session('payment_phone');
    $latestPayment = $phone
        ? MpesaPayment::where('phone_number', $phone)
            ->whereIn('transaction_status', ['success', 'paid'])
            ->latest()
            ->first()
        : null;

    $isPaid = !is_null($latestPayment);

    // Load registration price from admin settings
    $settings = \App\AdminSetting::first();
    $registrationPrice = $settings->registration_price ?? 5;
@endphp

{{-- Floating Payment Status Alert for previous payment --}}
@if ($latestPayment)
    <div class="alert alert-info payment-status-alert">
        <strong>@lang('payment.payment_status'):</strong>
        {{ __('payment.status_success') }}
    </div>
@endif

<style>
.payment-status-alert {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    padding: 15px 20px;
    border-radius: 6px;
    box-shadow: 0 2px 10px rgba(31, 236, 12, 0.87);
}
.manual-continue-section {
    margin-top: 15px;
    padding: 15px;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    background-color: #fff9e6;
    display: none;
}

/* Modernization styles - maintaining original sizes */
.registration-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 6px 25px rgba(0, 0, 0, 0.08);
    border: 1px solid #eaeaea;
}

.header-title {
    color: #2c3e50;
    font-weight: 700;
    margin-bottom: 8px;
}

.header-subtitle {
    color: #7f8c8d;
    font-size: 16px;
}

.login-prompt {
    background-color: #f8f9fa;
    padding: 12px 16px;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-control {
    border: 1px solid #dce1e6;
    border-radius: 6px;
    padding: 10px 12px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #4a6cf7;
    box-shadow: 0 0 0 3px rgba(74, 108, 247, 0.1);
}

.btn-primary {
    background: linear-gradient(to right, #4a6cf7, #3b5bdb);
    border: none;
    border-radius: 6px;
    padding: 10px 16px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(74, 108, 247, 0.3);
}

.alert {
    border-radius: 8px;
    padding: 12px 16px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border-left: 4px solid #28a745;
}

.required-field::after {
    content: "*";
    color: #e53e3e;
    margin-left: 4px;
}

.phone-hint {
    font-size: 12px;
    color: #6c757d;
    margin-top: 4px;
}

.payment-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
}

</style>

<script>
setTimeout(() => {
    const alert = document.querySelector('.payment-status-alert');
    if (alert) alert.style.display = 'none';
}, 5000);

// Function to create temporary floating alerts dynamically
function showFloatingAlert(message, type = 'success', duration = 5000) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} payment-status-alert`;
    alertDiv.innerHTML = message;
    document.body.appendChild(alertDiv);

    setTimeout(() => {
        alertDiv.remove();
    }, duration);
}
</script>

<div class="col-md-8 col-xs-12 col-md-offset-2 tw-mt-6">
    <div class="registration-container">
        <div class="tw-p-2 sm:tw-p-3 tw-mb-4">
            <div class="tw-flex tw-flex-col tw-gap-4 tw-p-6">

                {{-- Header --}}
                <div class="text-center">
                    <h1 class="header-title">{{ config('app.name', 'ultimatePOS') }}</h1>
                    <h2 class="header-subtitle">
                        @lang('business.register_and_get_started_in_minutes')
                    </h2>
                </div>

                {{-- Login Prompt --}}
                <div class="login-prompt text-center">
                    @lang('business.already_registered')
                    <a href="{{ route('login') }}" class="text-primary fw-semibold ms-1 text-decoration-none">
                        @lang('business.login_here')
                    </a>
                </div>

                {{-- Payment Section --}}
                <div id="payment_section" style="{{ $isPaid ? 'display:none;' : '' }}">
                    <div class="payment-card">
                <h5 class="mb-4 text-center fw-semibold">@lang('payment.complete_payment')</h5>
                <p class="text-center mb-3">Registration Fee: <strong>Ksh {{ number_format($registrationPrice, 2) }}</strong></p>
                        
                        <!-- M-Pesa Payment Form -->
                        <div id="mpesa_form_container">
                            <form method="POST" action="{{ route('mpesa.initiate') }}" id="mpesa_payment_form">
                                @csrf

                                <div class="form-group">
                                    <label for="first_name" class="required-field">@lang('payment.first_name')</label>
                                    <input type="text" name="first_name" required class="form-control" value="{{ old('first_name') }}">
                                </div>

                                <div class="form-group">
                                    <label for="middle_name">@lang('payment.middle_name')</label>
                                    <input type="text" name="middle_name" class="form-control" value="{{ old('middle_name') }}">
                                </div>

                                <div class="form-group">
                                    <label for="last_name" class="required-field">@lang('payment.last_name')</label>
                                    <input type="text" name="last_name" required class="form-control" value="{{ old('last_name') }}">
                                </div>

                                <div class="form-group">
                                    <label for="phone" class="required-field">@lang('payment.phone_number')</label>
                                    <input type="text" name="phone" required class="form-control" value="{{ old('phone', $phone) }}" id="mpesa_phone">
                                    <div class="phone-hint">@lang('payment.phone_format')</div>
                                </div>
                                
                                
                                <button type="submit" class="btn btn-primary w-100" id="mpesa_submit_btn">
                                    <i class="fas fa-mobile-alt me-2"></i>@lang('payment.pay_via_mpesa')
                                </button>
                            </form>
                        </div>

                        <div id="payment_feedback" class="alert mt-2 d-none" aria-live="polite"></div>

                        <div id="account_ref_display" class="alert alert-success mt-2 {{ $latestPayment?->account_ref ? '' : 'd-none' }}">
                            <strong>@lang('payment.account_reference'):</strong>
                            <span id="account_ref">{{ $latestPayment->account_ref ?? '' }}</span>
                        </div>

                        {{-- Manual Continue Section --}}
                        <div id="manual_continue_section" class="manual-continue-section">
                            <div class="text-center">
                                <i class="fas fa-clock text-warning mb-2" style="font-size: 24px;"></i>
                                <h5>@lang('payment.payment_timeout')</h5>
                                <p class="text-muted small">@lang('payment.contact_support_if_paid')</p>
                                
                                <button type="button" id="check_payment_status" class="btn btn-warning btn-sm">
                                    <i class="fas fa-sync-alt me-1"></i> @lang('payment.check_payment_status')
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Registration Form --}}
                <div id="registration_form_section" class="mt-4" style="{{ $isPaid ? '' : 'display:none;' }}">
                    {!! Form::open([
                        'url' => route('business.postRegister'),
                        'method' => 'post',
                        'id' => 'business_register_form',
                        'files' => true,
                    ]) !!}
                        @include('business.partials.register_form')
                        {!! Form::hidden('package_id', $package_id) !!}
                        {!! Form::hidden('payment_verified', $isPaid ? '1' : '0', ['id' => 'payment_verified']) !!}
                    {!! Form::close() !!}
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
@if($isPaid)
<script>
$(function () {
    $('#payment_section').hide();
    $('#registration_form_section').show();
    document.getElementById('business_register_form').scrollIntoView({ behavior: 'smooth' });
});
</script>
@endif

<script type="text/javascript">
$(document).ready(function () {
    let isPaymentInitiated = false;
    let pollInterval = null;
    let pollCount = 0;
    const maxPolls = 6;

    const $mpesaForm = $('#mpesa_payment_form');
    const $mpesaSubmitBtn = $('#mpesa_submit_btn');
    const $phoneInput = $('#mpesa_phone');
    const $feedback = $('#payment_feedback');
    const $paymentSection = $('#payment_section');
    const $registrationSection = $('#registration_form_section');
    const $accountRefDisplay = $('#account_ref_display');
    const $accountRef = $('#account_ref');
    const $manualContinueSection = $('#manual_continue_section');
    const $checkPaymentBtn = $('#check_payment_status');

    // Set up CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Handle M-Pesa form submission
    $mpesaForm.on('submit', function (e) {
        e.preventDefault();
        
        if (isPaymentInitiated || $mpesaSubmitBtn.prop('disabled')) return;

        const phone = $phoneInput.val().trim();
        if (!/^2547\d{8}$/.test(phone)) {
            showFeedback('danger', "{{ __('payment.invalid_phone_format') }}");
            return;
        }

        isPaymentInitiated = true;
        disableButton(true, "{{ __('payment.initiating_payment') }}");
        showFeedback('info', "{{ __('payment.initiating_payment') }}");
        $manualContinueSection.hide();

        $accountRefDisplay.addClass('d-none');
        $accountRef.text('');

        // Submit form via AJAX
        $.post("{{ route('mpesa.initiate') }}", $mpesaForm.serialize())
        .done(function (response) {
            if (response.transaction_status === 'success') {
                $accountRef.text(response.account_ref);
                $accountRefDisplay.removeClass('d-none');
                showFeedback('success', response.message || "{{ __('payment.stk_sent') }}");
                startPolling();
            } else {
                showFeedback('danger', response.message || "{{ __('payment.payment_initiation_failed') }}");
                disableButton(false);
                isPaymentInitiated = false;
            }
        })
        .fail(function (xhr) {
            let msg = "{{ __('payment.payment_initiation_failed') }}";
            if (xhr.status === 419) {
                msg = "{{ __('payment.session_expired') }}";
                location.reload();
            } else if (xhr.responseJSON?.message) {
                msg = xhr.responseJSON.message;
            }
            showFeedback('danger', msg);
            disableButton(false);
            isPaymentInitiated = false;
        });
    });

    function startPolling() {
        pollCount = 0;
        clearInterval(pollInterval);
        
        pollInterval = setInterval(() => {
            pollCount++;

            if (pollCount >= maxPolls) {
                clearInterval(pollInterval);
                handlePaymentTimeout();
                return;
            }

            // Show countdown in feedback
            const remaining = maxPolls - pollCount;
            showFeedback('info', `{{ __('payment.checking_payment') }} (${remaining * 5}s {{ __('payment.remaining') }})`);

            checkPaymentStatus();

        }, 5000);

        // Initial check
        checkPaymentStatus();
    }

    function checkPaymentStatus() {
        $.post("{{ route('business.payment.confirm') }}")
        .done(function (data) {
            console.log("Payment check response:", data);
            
            if (data.success && (data.transaction_status === 'success' || data.transaction_status === 'paid' || data.transaction_status === 'SUCCESS')) {
                clearInterval(pollInterval);
                handlePaymentSuccess();
            } else if (data.transaction_status === 'pending') {
                // Payment still pending, continue polling
                console.log("Payment still pending...");
                const remaining = maxPolls - pollCount;
                showFeedback('info', `{{ __('payment.payment_pending') }} (${remaining * 5}s {{ __('payment.remaining') }})`);
            } else if (data.transaction_status === 'failed') {
                clearInterval(pollInterval);
                showFeedback('danger', "{{ __('payment.payment_failed') }}");
                disableButton(false);
                isPaymentInitiated = false;
                $manualContinueSection.hide();
            } else {
                // Other error or unknown status
                console.log("Payment status unknown:", data.transaction_status);
                showFeedback('warning', data.message || "{{ __('payment.payment_status_unknown') }}");
            }
        })
        .fail(function (xhr, status, error) {
            console.error("Payment check failed:", status, error, xhr.responseText);
            // Continue polling on temporary errors
            console.log("Payment check failed, will retry...");
        });
    }

    function handlePaymentSuccess() {
        showFloatingAlert("{{ __('payment.payment_confirmed') }}", 'success');
        $('#payment_verified').val('1');
        
        setTimeout(() => {
            $paymentSection.hide();
            $registrationSection.show();
            $manualContinueSection.hide();
            document.getElementById('business_register_form').scrollIntoView({ behavior: 'smooth' });
        }, 500);
    }

    function handlePaymentTimeout() {
        isPaymentInitiated = false;
        disableButton(false);
        
        showFeedback('warning', `
            <i class="fas fa-clock me-1"></i> {{ __('payment.payment_timeout') }}<br>
            <small>{{ __('payment.contact_support_if_paid') }}</small>
        `);
        
        $manualContinueSection.show();
    }

    // Check payment status button (only shows registration if payment was successful)
    $checkPaymentBtn.on('click', function() {
        disableButton(true, "{{ __('payment.checking_payment') }}");
        showFeedback('info', "{{ __('payment.checking_payment') }}");
        
        $.post("{{ route('business.payment.confirm') }}")
        .done(function(data) {
            console.log("Manual check response:", data);
            
            if (data.success && (data.transaction_status === 'success' || data.transaction_status === 'paid' || data.transaction_status === 'SUCCESS')) {
                handlePaymentSuccess();
            } else if (data.transaction_status === 'pending') {
                showFeedback('info', "{{ __('payment.payment_still_pending') }}");
                disableButton(false);
            } else if (data.transaction_status === 'failed') {
                showFeedback('danger', "{{ __('payment.payment_failed_contact_support') }}");
                disableButton(false);
            } else {
                showFeedback('warning', data.message || "{{ __('payment.payment_status_unknown') }}");
                disableButton(false);
            }
        })
        .fail(function(xhr, status, error) {
            console.error("Manual check failed:", status, error, xhr.responseText);
            showFeedback('danger', "{{ __('payment.error_confirming_payment') }}");
            disableButton(false);
        });
    });

    function showFeedback(type, message) {
        $feedback.removeClass().addClass(`alert alert-${type}`).html(message).removeClass('d-none');
    }

    function disableButton(disable, text = null) {
        $mpesaSubmitBtn.prop('disabled', disable);
        if (disable) {
            $mpesaSubmitBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + (text || "{{ __('payment.processing') }}"));
        } else {
            $mpesaSubmitBtn.html('<i class="fas fa-mobile-alt me-2"></i>' + (text || "{{ __('payment.pay_via_mpesa') }}"));
        }
    }

    // Prevent form submission if payment not verified
    $('#business_register_form').on('submit', function(e) {
        if ($('#payment_verified').val() !== '1') {
            e.preventDefault();
            showFloatingAlert("{{ __('payment.please_complete_payment_first') }}", 'danger');
            $paymentSection.show();
            $registrationSection.hide();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
});
</script>
@endsection