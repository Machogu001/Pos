@extends('layouts.auth2')
@section('title', __('lang_v1.login'))

@section('content')
    <div class="row">
        <div class="col-md-4"></div>
        <div class="col-md-4">
            <div class="tw-p-5 md:tw-p-6 tw-mb-4 tw-rounded-2xl tw-transition-all tw-duration-200 tw-bg-white tw-shadow-sm tw-ring-1 tw-ring-gray-200">
                <div class="tw-flex tw-flex-col tw-gap-4">
                    <div class="tw-flex tw-flex-col tw-gap-1 tw-text-center">
                        <h1 class="tw-text-lg md:tw-text-xl tw-font-semibold tw-text-[#1e1e1e]">{{ __('ui.verify_your_login') }}</h1>
                        <p class="tw-text-sm tw-font-medium tw-text-gray-500">
                            {{ __('ui.enter_the_6_digit_code_sent_via') }} {{ $deliveryMethodLabel }} {{ __('ui.to') }} {{ $deliveryTargetMask }}.
                        </p>
                    </div>

                    @if(session('status'))
                        @if(session('status.success'))
                            <div class="tw-bg-green-50 tw-border tw-border-green-200 tw-text-green-700 tw-px-4 tw-py-3 tw-rounded-lg tw-text-sm tw-font-medium">
                                {{ session('status.msg') }}
                            </div>
                        @else
                            <div class="tw-bg-red-50 tw-border tw-border-red-200 tw-text-red-700 tw-px-4 tw-py-3 tw-rounded-lg tw-text-sm tw-font-medium">
                                {{ session('status.msg') }}
                            </div>
                        @endif
                    @endif

                    @if ($errors->any())
                        <div class="tw-bg-red-50 tw-border tw-border-red-200 tw-text-red-700 tw-px-4 tw-py-3 tw-rounded-lg tw-text-sm tw-font-medium">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.otp.verify') }}">
                        @csrf
                        <div class="form-group {{ $errors->has('otp') ? ' has-error' : '' }}">
                            <label class="tw-dw-form-control">
                                <div class="tw-dw-label">
                                    <span class="tw-text-xs md:tw-text-sm tw-font-medium tw-text-black">{{ __('ui.otp_code') }}</span>
                                </div>
                                <input
                                    id="otp"
                                    class="tw-border tw-border-[#D1D5DA] tw-outline-none tw-h-12 tw-bg-transparent tw-rounded-lg tw-px-3 tw-font-medium tw-text-black placeholder:tw-text-gray-500 placeholder:tw-font-medium"
                                    name="otp" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6"
                                    pattern="[0-9]{6}" placeholder="{{ __('ui.enter_otp') }}" required autofocus>
                            </label>
                            @if ($errors->has('otp'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('otp') }}</strong>
                                </span>
                            @endif
                        </div>

                        <button type="submit"
                            class="tw-bg-gradient-to-r tw-from-indigo-500 tw-to-blue-500 tw-h-12 tw-rounded-xl tw-text-sm md:tw-text-base tw-text-white tw-font-semibold tw-w-full tw-max-w-full mt-2 hover:tw-from-indigo-600 hover:tw-to-blue-600 focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-500 focus:tw-ring-offset-2 active:tw-from-indigo-700 active:tw-to-blue-700">
                            {{ __('ui.verify_and_continue') }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('login.otp.resend') }}" class="tw-mt-1">
                        @csrf
                        <div class="form-group tw-mb-2">
                            <label class="tw-dw-form-control">
                                <div class="tw-dw-label">
                                    <span class="tw-text-xs md:tw-text-sm tw-font-medium tw-text-black">{{ __('ui.resend_via') }}</span>
                                </div>
                                <select name="otp_delivery_method" id="otp-delivery-method" class="tw-border tw-border-[#D1D5DA] tw-outline-none tw-h-12 tw-bg-transparent tw-rounded-lg tw-px-3 tw-font-medium tw-text-black">
                                    @foreach($availableDeliveryMethods as $methodValue => $methodLabel)
                                        <option value="{{ $methodValue }}" {{ $deliveryMethod === $methodValue ? 'selected' : '' }}>{{ $methodLabel }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <p class="tw-text-xs tw-text-gray-500 tw-mt-1">{{ __('ui.choose_how_you_want_the_next_otp_sent') }}</p>
                        </div>
                        <button type="submit"
                            id="otp-resend-button"
                            data-resend-available-at="{{ $resendAvailableAt ?? 0 }}"
                            data-default-text="{{ __('ui.resend_otp') }}"
                            data-countdown-text="{{ __('ui.resend_via') }} {{ $deliveryMethodLabel }} {{ __('ui.available_in') }}"
                            {{ !empty($resendAvailableAt) && now()->timestamp < $resendAvailableAt ? 'disabled' : '' }}
                            class="tw-w-full tw-border tw-border-gray-300 tw-text-gray-700 tw-font-semibold tw-rounded-xl tw-h-12 tw-bg-white hover:tw-bg-gray-50">
                            {{ __('ui.resend_via') }} {{ $deliveryMethodLabel }}
                        </button>
                        <div id="otp-resend-countdown" class="tw-mt-2 tw-text-center tw-text-xs tw-font-medium tw-text-gray-500"></div>
                    </form>

                    <div class="tw-text-center">
                        <a href="{{ route('login') }}" class="tw-text-sm tw-font-medium tw-text-gray-500 hover:tw-text-gray-700">
                            {{ __('ui.back_to_login') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4"></div>
    </div>
@endsection

@section('javascript')
<script>
    $(function () {
        var otpInput = document.getElementById('otp');
        var otpForm = otpInput ? otpInput.closest('form') : null;
        var resendButton = document.getElementById('otp-resend-button');
        var resendCountdown = document.getElementById('otp-resend-countdown');
        var resendTimer = null;

        function submitWhenComplete() {
            if (!otpInput || !otpForm) {
                return;
            }

            var digitsOnly = otpInput.value.replace(/\D/g, '');
            if (digitsOnly !== otpInput.value) {
                otpInput.value = digitsOnly;
            }

            if (digitsOnly.length === 6) {
                otpForm.submit();
            }
        }

        if (otpInput) {
            otpInput.addEventListener('input', submitWhenComplete);
            otpInput.addEventListener('paste', function () {
                window.setTimeout(submitWhenComplete, 0);
            });
            otpInput.focus();
        }

        function updateResendState() {
            if (!resendButton || !resendCountdown) {
                return;
            }

            var deliveryMethodSelect = document.getElementById('otp-delivery-method');
            var deliveryMethodLabel = deliveryMethodSelect && deliveryMethodSelect.options[deliveryMethodSelect.selectedIndex]
                ? deliveryMethodSelect.options[deliveryMethodSelect.selectedIndex].text
                : "{{ __('ui.otp') }}";

            var availableAt = parseInt(resendButton.getAttribute('data-resend-available-at') || '0', 10);
            if (!availableAt) {
                resendButton.disabled = false;
                resendButton.textContent = "{{ __('ui.resend_via') }} " + deliveryMethodLabel;
                resendCountdown.textContent = '';

                if (resendTimer) {
                    window.clearInterval(resendTimer);
                }

                return;
            }

            var secondsLeft = Math.max(availableAt - Math.floor(Date.now() / 1000), 0);

            if (secondsLeft > 0) {
                resendButton.disabled = true;
                resendButton.textContent = ("{{ __('ui.resend_via') }} " + deliveryMethodLabel + " {{ __('ui.available_in') }}").replace(':seconds', secondsLeft);
                resendCountdown.textContent = "{{ __('ui.you_can_request_a_new_otp_in') }} " + secondsLeft + ' ' + "{{ __('ui.seconds') }}" + '.';
                return;
            }

            resendButton.disabled = false;
            resendButton.textContent = "{{ __('ui.resend_via') }} " + deliveryMethodLabel;
            resendCountdown.textContent = '';

            if (resendTimer) {
                window.clearInterval(resendTimer);
            }
        }

        if (resendButton && resendCountdown) {
            updateResendState();
            resendTimer = window.setInterval(updateResendState, 1000);
        }

        var otpDeliveryMethod = document.getElementById('otp-delivery-method');
        if (otpDeliveryMethod) {
            otpDeliveryMethod.addEventListener('change', updateResendState);
        }

        if ('OTPCredential' in window && navigator.credentials && window.isSecureContext && otpInput) {
            var otpOptions = {
                otp: { transport: ['sms'] }
            };

            if (typeof AbortSignal !== 'undefined' && typeof AbortSignal.timeout === 'function') {
                otpOptions.signal = AbortSignal.timeout(60000);
            }

            navigator.credentials.get(otpOptions).then(function (credential) {
                if (credential && credential.code) {
                    otpInput.value = credential.code;
                    submitWhenComplete();
                }
            }).catch(function () {
                return null;
            });
        }
    });
</script>
@endsection