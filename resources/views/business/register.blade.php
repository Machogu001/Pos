@extends('layouts.auth2')

@section('title', __('lang_v1.register'))

@section('content')
@php
use App\MpesaPayment;
    $phone = session('payment_phone');
    $sessionRegistrationPaymentId = session('registration_payment_id');
    $sessionAccountReference = session('account_reference') ?? session('account_ref');
    $sessionCheckoutRequestId = session('checkout_request_id');
    $normalizedPhone = $phone ? preg_replace('/[^0-9]/', '', $phone) : null;
    $latestPayment = null;

    if (! empty($sessionRegistrationPaymentId) || ! empty($sessionCheckoutRequestId) || ! empty($sessionAccountReference)) {
        $paymentQuery = MpesaPayment::query()
            ->whereIn('transaction_status', ['success', 'paid'])
            ->where(function ($query) {
                $query->whereNull('payment_type')
                    ->orWhere('payment_type', 'registration');
            });

        if (! empty($sessionRegistrationPaymentId)) {
            $paymentQuery->where('id', $sessionRegistrationPaymentId);
        } else {
            $paymentQuery->where(function ($query) use ($sessionCheckoutRequestId, $sessionAccountReference) {
                if (! empty($sessionCheckoutRequestId)) {
                    $query->orWhere('checkout_request_id', $sessionCheckoutRequestId);
                }

                if (! empty($sessionAccountReference)) {
                    $query->orWhere('account_reference', $sessionAccountReference);
                }
            });
        }

        if (! empty($phone)) {
            $paymentQuery->where(function ($query) use ($phone, $normalizedPhone) {
                $query->where('phone_number', $phone);

                if (! empty($normalizedPhone)) {
                    $query->orWhere('phone_number', $normalizedPhone)
                        ->orWhere('phone_number', ltrim($normalizedPhone, '+'));
                }
            });
        }

        $latestPayment = $paymentQuery->latest()->first();
    }

    $currentAccountReference = $latestPayment->account_reference ?? $sessionAccountReference;

    $isPaid = !is_null($latestPayment);
    $showResumeSection = $errors->has('resume_payment') || ! empty(old('payment_reference')) || ! empty(old('phone'));

    // Load registration price from admin settings
    $settings = \App\AdminSetting::first();
    $registrationPrice = ! empty($settings) && ! is_null($settings->registration_price)
        ? $settings->registration_price
        : 5;
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

.payment-section-card {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 18px;
    background: #ffffff;
}

.payment-section-card + .payment-section-card {
    margin-top: 18px;
}

.payment-section-title {
    font-size: 16px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 6px;
}

.payment-section-copy {
    font-size: 13px;
    color: #6b7280;
    margin-bottom: 14px;
}

.payment-section-divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 20px 0;
    color: #6b7280;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}

.payment-section-divider::before,
.payment-section-divider::after {
    content: "";
    flex: 1;
    height: 1px;
    background: #e5e7eb;
}

.payment-toggle-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #2563eb;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
}

.payment-toggle-link:hover {
    color: #1d4ed8;
    text-decoration: none;
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
                <h5 class="mb-2 text-center fw-semibold">@lang('payment.complete_payment')</h5>
                <p class="text-center mb-4">Registration Fee: <strong>Ksh {{ number_format($registrationPrice, 2) }}</strong></p>

                        <div class="text-center mb-3">
                            <button type="button" id="resume_section_toggle" class="btn btn-link payment-toggle-link p-0" aria-expanded="{{ $showResumeSection ? 'true' : 'false' }}">
                                <i class="fas fa-history"></i>
                                <span id="resume_section_toggle_text">{{ $showResumeSection ? __('payment.hide_resume_form') : __('payment.show_resume_form') }}</span>
                            </button>
                        </div>

                        <div id="resume_section_card" class="payment-section-card" style="{{ $showResumeSection ? '' : 'display:none;' }}">
                            <div class="payment-section-title">@lang('payment.resume_title')</div>
                            <div class="payment-section-copy">@lang('payment.resume_help')</div>
                            <form method="POST" action="{{ route('business.registration.resume') }}" id="resume_registration_form">
                                @csrf
                                <div class="form-group">
                                    <label for="resume_phone">@lang('payment.phone_number')</label>
                                    <input type="text" name="phone" id="resume_phone" class="form-control" value="{{ old('phone', $phone) }}">
                                </div>
                                <div class="form-group mb-3">
                                    <label for="payment_reference">@lang('payment.payment_reference')</label>
                                    <input type="text" name="payment_reference" id="payment_reference" class="form-control" value="{{ old('payment_reference', $currentAccountReference) }}">
                                    <div class="phone-hint">@lang('payment.payment_reference_help')</div>
                                </div>
                                <button type="submit" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-unlock-alt me-1"></i>@lang('payment.resume_registration')
                                </button>
                            </form>
                        </div>

                        <div class="payment-section-divider">@lang('payment.or_pay_now')</div>
                        
                        <!-- M-Pesa Payment Form -->
                        <div id="mpesa_form_container" class="payment-section-card">
                            <div class="payment-section-title">@lang('payment.new_payment_title')</div>
                            <div class="payment-section-copy">@lang('payment.new_payment_help')</div>
                            <form method="POST" action="{{ route('mpesa.initiate') }}" id="mpesa_payment_form">
                                @csrf
                                <input type="hidden" name="registration_payment_id" id="registration_payment_id" value="{{ session('registration_payment_id') }}">
                                <input type="hidden" name="account_reference" id="payment_account_reference" value="{{ $currentAccountReference ?? '' }}">

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

                        <div id="account_ref_display" class="alert alert-success mt-2 {{ $currentAccountReference ? '' : 'd-none' }}">
                            <strong>@lang('payment.account_reference'):</strong>
                            <span id="account_ref">{{ $currentAccountReference ?? '' }}</span>
                        </div>

                        {{-- Manual Continue Section --}}
                        <div id="manual_continue_section" class="manual-continue-section">
                            <div class="text-center">
                                <i class="fas fa-clock text-warning mb-2" style="font-size: 24px;"></i>
                                <h5>@lang('payment.payment_timeout_title')</h5>
                                <p class="text-muted small">@lang('payment.payment_timeout')</p>
                                <p class="text-muted small">@lang('payment.contact_support_if_paid')</p>
                                <div id="manual_account_ref_display" class="alert alert-light mt-3 {{ $currentAccountReference ? '' : 'd-none' }}">
                                    <strong>@lang('payment.account_reference'):</strong>
                                    <span id="manual_account_ref">{{ $currentAccountReference ?? '' }}</span>
                                </div>
                                <div id="payment_failure_reason" class="alert alert-danger mt-3 d-none">
                                    <strong>@lang('payment.failure_reason'):</strong>
                                    <span id="payment_failure_reason_text"></span>
                                </div>
                                
                                <button type="button" id="check_payment_status" class="btn btn-warning btn-sm">
                                    <i class="fas fa-sync-alt me-1"></i> @lang('payment.check_payment_status')
                                </button>
                                <button type="button" id="retry_payment_btn" class="btn btn-primary btn-sm ms-2 d-none">
                                    <i class="fas fa-redo me-1"></i> @lang('payment.retry_payment')
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
                        {!! Form::hidden('checkout_request_id', $sessionCheckoutRequestId, ['id' => 'checkout_request_id']) !!}
                        {!! Form::hidden('account_reference', $currentAccountReference, ['id' => 'account_reference']) !!}
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
    let postTimeoutPollInterval = null;
    let pollCount = 0;
    const maxPolls = 6;
    let postTimeoutPollCount = 0;
    const maxPostTimeoutPolls = 6;

    const $mpesaForm = $('#mpesa_payment_form');
    const $mpesaSubmitBtn = $('#mpesa_submit_btn');
    const $phoneInput = $('#mpesa_phone');
    const $feedback = $('#payment_feedback');
    const $resumeSectionToggle = $('#resume_section_toggle');
    const $resumeSectionToggleText = $('#resume_section_toggle_text');
    const $resumeSectionCard = $('#resume_section_card');
    const $paymentSection = $('#payment_section');
    const $registrationSection = $('#registration_form_section');
    const $accountRefDisplay = $('#account_ref_display');
    const $accountRef = $('#account_ref');
    const $checkoutRequestId = $('#checkout_request_id');
    const $accountReferenceInput = $('#account_reference');
    const $paymentAccountReference = $('#payment_account_reference');
    const $registrationPaymentId = $('#registration_payment_id');
    const $manualContinueSection = $('#manual_continue_section');
    const $manualAccountRefDisplay = $('#manual_account_ref_display');
    const $manualAccountRef = $('#manual_account_ref');
    const $failureReason = $('#payment_failure_reason');
    const $failureReasonText = $('#payment_failure_reason_text');
    const $checkPaymentBtn = $('#check_payment_status');
    const $retryPaymentBtn = $('#retry_payment_btn');

    // Set up CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $resumeSectionToggle.on('click', function () {
        const shouldShow = $resumeSectionCard.is(':hidden');

        $resumeSectionCard.toggle(shouldShow);
        $resumeSectionToggle.attr('aria-expanded', shouldShow ? 'true' : 'false');
        $resumeSectionToggleText.text(shouldShow
            ? "{{ __('payment.hide_resume_form') }}"
            : "{{ __('payment.show_resume_form') }}");
    });

    // Handle M-Pesa form submission
    $mpesaForm.on('submit', function (e) {
        e.preventDefault();
        
        if (isPaymentInitiated || $mpesaSubmitBtn.prop('disabled')) return;

        const phone = normalizeKenyanPhone($phoneInput.val());
        if (!phone) {
            showFeedback('danger', "{{ __('payment.invalid_phone_format') }}");
            return;
        }

        $phoneInput.val(phone);
        stopAllPolling();

        isPaymentInitiated = true;
        disableButton(true, "{{ __('payment.initiating_payment') }}");
        showFeedback('info', "{{ __('payment.initiating_payment') }}");
        $manualContinueSection.hide();
        $failureReason.addClass('d-none');
        $failureReasonText.text('');
        $retryPaymentBtn.addClass('d-none');

        $accountRefDisplay.addClass('d-none');
        $accountRef.text('');

        // Submit form via AJAX
        $.post("{{ route('mpesa.initiate') }}", $mpesaForm.serialize())
        .done(function (response) {
            if (response.transaction_status === 'success') {
                $accountRef.text(response.account_ref);
                $accountRefDisplay.removeClass('d-none');
                $accountReferenceInput.val(response.account_ref || '');
                $paymentAccountReference.val(response.account_ref || '');
                $checkoutRequestId.val(response.checkout_request_id || '');
                $registrationPaymentId.val(response.payment_id || '');
                $manualAccountRef.text(response.account_ref || '');
                $manualAccountRefDisplay.toggleClass('d-none', !(response.account_ref || '').length);
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
        stopAllPolling();
        
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

    function startPostTimeoutPolling() {
        postTimeoutPollCount = 0;
        clearInterval(postTimeoutPollInterval);

        postTimeoutPollInterval = setInterval(() => {
            postTimeoutPollCount++;

            if (postTimeoutPollCount >= maxPostTimeoutPolls) {
                clearInterval(postTimeoutPollInterval);
                return;
            }

            checkPaymentStatus(true);
        }, 5000);
    }

    function stopAllPolling() {
        clearInterval(pollInterval);
        clearInterval(postTimeoutPollInterval);
    }

    function normalizeKenyanPhone(rawPhone) {
        const digits = String(rawPhone || '').trim().replace(/[^\d+]/g, '');

        if (/^(07|01)\d{8}$/.test(digits)) {
            return `254${digits.slice(1)}`;
        }

        if (/^\+?254[17]\d{8}$/.test(digits)) {
            return digits.replace(/^\+/, '');
        }

        return null;
    }

    function checkPaymentStatus(silentPending = false) {
        $.post("{{ route('business.payment.confirm') }}", buildConfirmPayload())
        .done(function (data) {
            console.log("Payment check response:", data);
            
            if (data.success && (data.transaction_status === 'success' || data.transaction_status === 'paid' || data.transaction_status === 'SUCCESS')) {
                if (data.account_ref) {
                    $accountRef.text(data.account_ref);
                    $accountReferenceInput.val(data.account_ref);
                    $paymentAccountReference.val(data.account_ref);
                    $accountRefDisplay.removeClass('d-none');
                    $manualAccountRef.text(data.account_ref);
                    $manualAccountRefDisplay.removeClass('d-none');
                }

                if (data.checkout_request_id) {
                    $checkoutRequestId.val(data.checkout_request_id);
                }

                if (data.payment_id) {
                    $registrationPaymentId.val(data.payment_id);
                }

                stopAllPolling();
                handlePaymentSuccess();
            } else if (data.transaction_status === 'pending') {
                // Payment still pending, continue polling
                console.log("Payment still pending...");
                if (!silentPending) {
                    const remaining = maxPolls - pollCount;
                    showFeedback('info', `{{ __('payment.payment_pending') }} (${remaining * 5}s {{ __('payment.remaining') }})`);
                }
            } else if (data.transaction_status === 'failed') {
                stopAllPolling();
                showFailureState(data);
                isPaymentInitiated = false;
            } else {
                // Other error or unknown status
                console.log("Payment status unknown:", data.transaction_status);
                if (!silentPending) {
                    showFeedback('warning', data.message || "{{ __('payment.payment_status_unknown') }}");
                }
            }
        })
        .fail(function (xhr, status, error) {
            console.error("Payment check failed:", status, error, xhr.responseText);
            const message = xhr.responseJSON?.result_desc || xhr.responseJSON?.message;

            if (message && !silentPending) {
                showFeedback('warning', message);
            }

            // Continue polling on temporary errors
            console.log("Payment check failed, will retry...");
        });
    }

    function handlePaymentSuccess() {
        stopAllPolling();
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
        $failureReason.addClass('d-none');
        $failureReasonText.text('');
        
        showFeedback('warning', `<i class="fas fa-clock me-1"></i> {{ __('payment.payment_timeout_title') }}`);
        
        $manualContinueSection.show();
        $retryPaymentBtn.toggleClass('d-none', !$paymentAccountReference.val());
        $manualAccountRef.text($paymentAccountReference.val() || $accountReferenceInput.val() || '');
        $manualAccountRefDisplay.toggleClass('d-none', !$manualAccountRef.text().trim().length);
        startPostTimeoutPolling();
    }

    function buildConfirmPayload() {
        return {
            registration_payment_id: $registrationPaymentId.val() || '',
            checkout_request_id: $checkoutRequestId.val() || '',
            account_reference: $paymentAccountReference.val() || $accountReferenceInput.val() || '',
            phone_number: normalizeKenyanPhone($phoneInput.val()) || ''
        };
    }

    // Check payment status button (only shows registration if payment was successful)
    $checkPaymentBtn.on('click', function() {
        disableButton(true, "{{ __('payment.checking_payment') }}");
        showFeedback('info', "{{ __('payment.checking_payment') }}");
        
        $.post("{{ route('business.payment.confirm') }}", buildConfirmPayload())
        .done(function(data) {
            console.log("Manual check response:", data);
            
            if (data.success && (data.transaction_status === 'success' || data.transaction_status === 'paid' || data.transaction_status === 'SUCCESS')) {
                if (data.account_ref) {
                    $accountRef.text(data.account_ref);
                    $accountReferenceInput.val(data.account_ref);
                    $paymentAccountReference.val(data.account_ref);
                    $accountRefDisplay.removeClass('d-none');
                    $manualAccountRef.text(data.account_ref);
                    $manualAccountRefDisplay.removeClass('d-none');
                }

                if (data.checkout_request_id) {
                    $checkoutRequestId.val(data.checkout_request_id);
                }

                if (data.payment_id) {
                    $registrationPaymentId.val(data.payment_id);
                }

                handlePaymentSuccess();
            } else if (data.transaction_status === 'pending') {
                showFeedback('info', "{{ __('payment.payment_still_pending') }}");
                disableButton(false);
            } else if (data.transaction_status === 'failed') {
                showFailureState(data);
                disableButton(false);
            } else {
                showFeedback('warning', data.message || "{{ __('payment.payment_status_unknown') }}");
                disableButton(false);
            }
        })
        .fail(function(xhr, status, error) {
            console.error("Manual check failed:", status, error, xhr.responseText);
            const message = xhr.responseJSON?.result_desc || xhr.responseJSON?.message || "{{ __('payment.error_confirming_payment') }}";
            showFeedback('danger', message);
            disableButton(false);
        });
    });

    function showFeedback(type, message) {
        $feedback.removeClass().addClass(`alert alert-${type}`).html(message).removeClass('d-none');
    }

    function showFailureState(data) {
        const reason = (data.result_desc || data.message || '').trim();

        showFeedback('danger', reason
            ? `{{ __('payment.payment_failed') }}<br><small>${reason}</small>`
            : `{{ __('payment.payment_failed') }}`);
        disableButton(false, "{{ __('payment.retry_payment') }}");

        if (reason) {
            $failureReasonText.text(reason);
            $failureReason.removeClass('d-none');
        } else {
            $failureReason.addClass('d-none');
            $failureReasonText.text('');
        }

        $manualContinueSection.show();
        $retryPaymentBtn.toggleClass('d-none', !$paymentAccountReference.val());
        $manualAccountRef.text($paymentAccountReference.val() || $accountReferenceInput.val() || '');
        $manualAccountRefDisplay.toggleClass('d-none', !$manualAccountRef.text().trim().length);
    }

    function disableButton(disable, text = null) {
        $mpesaSubmitBtn.prop('disabled', disable);
        if (disable) {
            $mpesaSubmitBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + (text || "{{ __('payment.processing') }}"));
        } else {
            $mpesaSubmitBtn.html('<i class="fas fa-mobile-alt me-2"></i>' + (text || "{{ __('payment.pay_via_mpesa') }}"));
        }
    }

    $retryPaymentBtn.on('click', function () {
        if ($mpesaSubmitBtn.prop('disabled')) {
            return;
        }

        stopAllPolling();
        showFeedback('info', '{{ __('payment.retrying_same_reference') }}');
        $manualContinueSection.hide();
        $mpesaForm.trigger('submit');
    });

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