<div class="modal fade" tabindex="-1" role="dialog" id="modal_payment">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('messages.close') }}"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">@lang('lang_v1.payment')</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 mb-12">
                        <strong>@lang('lang_v1.advance_balance'):</strong> <span id="advance_balance_text"></span>
                        {!! Form::hidden('advance_balance', null, [
                            'id' => 'advance_balance',
                            'data-error-msg' => __('lang_v1.required_advance_balance_not_available'),
                        ]) !!}
                    </div>
                    <div class="col-md-9">
                        <div class="row">
                            <div id="payment_rows_div">
                                @php
                                    $pos_settings = !empty(session()->get('business.pos_settings')) ? json_decode(session()->get('business.pos_settings'), true) : [];
                                    $show_in_pos = '';
                                    if ($pos_settings['enable_cash_denomination_on'] == 'all_screens' || $pos_settings['enable_cash_denomination_on'] == 'pos_screen') {
                                        $show_in_pos = true;
                                    }
                                @endphp
                                @foreach ($payment_lines as $payment_line)
                                    @if ($payment_line['is_return'] == 1)
                                        @php
                                            $change_return = $payment_line;
                                        @endphp

                                        @continue
                                    @endif

                                    @include('sale_pos.partials.payment_row', [
                                        'removable' => !$loop->first,
                                        'row_index' => $loop->index,
                                        'payment_line' => $payment_line,
                                        'show_denomination' => true,
                                        'show_in_pos' => $show_in_pos,
                                    ])
                                @endforeach
                            </div>
                            <input type="hidden" id="payment_row_index" value="{{ count($payment_lines) }}">
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white tw-dw-btn-sm tw-w-full"
                                    id="add-payment-row">@lang('sale.add_payment_row')</button>
                            </div>
                        </div>
                        <br>
                        <div class="row @if ($change_return['amount'] == 0) hide @endif payment_row"
                            id="change_return_payment_data">
                            <div class="col-md-12">
                                <div class="box box-solid payment_row bg-lightgray">
                                    <div class="box-body">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                {!! Form::label('change_return_method', __('lang_v1.change_return_payment_method') . ':*') !!}
                                                <div class="input-group">
                                                    <span class="input-group-addon">
                                                        <i class="fas fa-money-bill-alt"></i>
                                                    </span>
                                                    @php
                                                        $_payment_method = empty($change_return['method']) && array_key_exists('cash', $payment_types) ? 'cash' : $change_return['method'];

                                                        $_payment_types = $payment_types;
                                                        if (isset($_payment_types['advance'])) {
                                                            unset($_payment_types['advance']);
                                                        }
                                                    @endphp
                                                    {!! Form::select('payment[change_return][method]', $_payment_types, $_payment_method, [
                                                        'class' => 'form-control col-md-12 payment_types_dropdown',
                                                        'id' => 'change_return_method',
                                                        'style' => 'width:100%;',
                                                    ]) !!}
                                                </div>
                                            </div>
                                        </div>
                                        @if (!empty($accounts))
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    {!! Form::label('change_return_account', __('lang_v1.change_return_payment_account') . ':') !!}
                                                    <div class="input-group">
                                                        <span class="input-group-addon">
                                                            <i class="fas fa-money-bill-alt"></i>
                                                        </span>
                                                        {!! Form::select(
                                                            'payment[change_return][account_id]',
                                                            $accounts,
                                                            !empty($change_return['account_id']) ? $change_return['account_id'] : '',
                                                            ['class' => 'form-control select2', 'id' => 'change_return_account', 'style' => 'width:100%;'],
                                                        ) !!}
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="clearfix"></div>
                                        @include('sale_pos.partials.payment_type_details', [
                                            'payment_line' => $change_return,
                                            'row_index' => 'change_return',
                                        ])
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! Form::label('sale_note', __('sale.sell_note') . ':') !!}
                                    {!! Form::textarea('sale_note', !empty($transaction) ? $transaction->additional_notes : null, [
                                        'class' => 'form-control',
                                        'rows' => 3,
                                        'placeholder' => __('sale.sell_note'),
                                    ]) !!}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    {!! Form::label('staff_note', __('sale.staff_note') . ':') !!}
                                    {!! Form::textarea('staff_note', !empty($transaction) ? $transaction->staff_note : null, [
                                        'class' => 'form-control',
                                        'rows' => 3,
                                        'placeholder' => __('sale.staff_note'),
                                    ]) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="box box-solid bg-orange">
                            <div class="box-body">
                                <div class="col-md-12">
                                    <strong>
                                        @lang('lang_v1.total_items'):
                                    </strong>
                                    <br />
                                    <span class="lead text-bold total_quantity">0</span>
                                </div>

                                <div class="col-md-12">
                                    <hr>
                                    <strong>
                                        @lang('sale.total_payable'):
                                    </strong>
                                    <br />
                                    <span class="lead text-bold total_payable_span">0</span>
                                </div>

                                <div class="col-md-12">
                                    <hr>
                                    <strong>
                                        @lang('lang_v1.total_paying'):
                                    </strong>
                                    <br />
                                    <span class="lead text-bold total_paying">0</span>
                                    <input type="hidden" id="total_paying_input">
                                </div>

                                <div class="col-md-12">
                                    <hr>
                                    <strong>
                                        @lang('lang_v1.change_return'):
                                    </strong>
                                    <br />
                                    <span class="lead text-bold change_return_span">0</span>
                                    {!! Form::hidden('change_return', $change_return['amount'], [
                                        'class' => 'form-control change_return input_number',
                                        'required',
                                        'id' => 'change_return',
                                    ]) !!}
                                    <!-- <span class="lead text-bold total_quantity">0</span> -->
                                    @if (!empty($change_return['id']))
                                        <input type="hidden" name="change_return_id"
                                            value="{{ $change_return['id'] }}">
                                    @endif
                                </div>

                                <div class="col-md-12">
                                    <hr>
                                    <strong>
                                        @lang('lang_v1.balance'):
                                    </strong>
                                    <br />
                                    <span class="lead text-bold balance_due">0</span>
                                    <input type="hidden" id="in_balance_due" value=0>
                                </div>



                            </div>
                            <!-- /.box-body -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
                <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white" id="pos-save">@lang('sale.finalize_payment')</button>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

        <!-- MPESA Log Modal -->
        <div class="modal fade" id="mpesa_log_modal" tabindex="-1" role="dialog" aria-labelledby="mpesaLogModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-sm" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="mpesaLogModalLabel">@lang('payment.mpesa_log')</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('messages.close') }}">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body mpesa-log-body">
                        <div class="text-center">@lang('messages.loading')</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">@lang('messages.close')</button>
                    </div>
                </div>
            </div>
        </div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mpesaI18n = {
        requestFailed: @json(__('payment.request_failed')),
        unexpectedServerResponse: @json(__('payment.unexpected_server_response')),
        paymentFailed: @json(__('payment.payment_failed_plain')),
        noLinkedCheckoutYet: @json(__('payment.no_linked_checkout_yet')),
        confirmationCheckoutMismatch: @json(__('payment.confirmation_checkout_mismatch')),
        noRelatedCheckout: @json(__('payment.no_related_checkout')),
        logsUnavailable: @json(__('payment.logs_unavailable')),
        logsResponseUnreadable: @json(__('payment.logs_response_unreadable')),
        noLogsFound: @json(__('payment.no_logs_found')),
        paymentCheckoutLogs: @json(__('payment.payment_checkout_logs')),
        recentPhoneLogs: @json(__('payment.recent_phone_logs')),
        unableFetchLogs: @json(__('payment.unable_fetch_logs')),
        notAvailable: @json(__('payment.not_available')),
        invalidMpesaPhone: @json(__('payment.invalid_mpesa_phone')),
        sendingStk: @json(__('payment.sending_stk')),
        stkSent: @json(__('payment.stk_sent')),
        paymentConfirmed: @json(__('payment.payment_confirmed_plain')),
        pendingConfirmation: @json(__('payment.pending_confirmation')),
        stkInitiationError: @json(__('payment.stk_initiation_error')),
        statusLookupRequiresPhoneOrCheckout: @json(__('payment.status_lookup_requires_phone_or_checkout')),
        checking: @json(__('payment.checking')),
        pending: @json(__('payment.pending_plain')),
        notFound: @json(__('payment.not_found')),
        statusCheckError: @json(__('payment.status_check_error')),
        statusCheckTimeout: @json(__('payment.status_check_timeout')),
        pollingError: @json(__('payment.polling_error'))
    };

    window.normalizeKenyanPhone = window.normalizeKenyanPhone || function(rawPhone) {
        const digits = String(rawPhone || '').trim().replace(/[^\d+]/g, '');

        if (/^(07|01)\d{8}$/.test(digits)) {
            return `254${digits.slice(1)}`;
        }

        if (/^\+?254[17]\d{8}$/.test(digits)) {
            return digits.replace(/^\+/, '');
        }

        return null;
    };

    window.readMpesaJsonResponse = window.readMpesaJsonResponse || async function(response, contextLabel) {
        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            const responseJson = await response.json();
            if (!response.ok) {
                const error = new Error(responseJson.message || mpesaI18n.requestFailed);
                error.payload = responseJson;
                error.status = response.status;
                throw error;
            }

            return responseJson;
        }

        const responseText = await response.text();
        console.error(contextLabel + ' unexpected response:', responseText);
        throw new Error(mpesaI18n.unexpectedServerResponse);
    };

    function setMpesaFailedState(statusBadge, statusInput, message) {
        if (statusInput) {
            statusInput.value = 'failed';
        }
        window.setMpesaStatusBadge(statusBadge, 'danger', message || mpesaI18n.paymentFailed);
    }

    window.resolveMpesaStatusMessage = window.resolveMpesaStatusMessage || function(data, fallback) {
        if (data && typeof data.result_desc === 'string' && data.result_desc.trim() !== '') {
            return data.result_desc.trim();
        }

        if (data && typeof data.message === 'string' && data.message.trim() !== '') {
            return data.message.trim();
        }

        return fallback;
    };

    window.isMpesaFinalResponse = window.isMpesaFinalResponse || function(data) {
        if (!data || typeof data !== 'object') {
            return false;
        }

        const status = String(data.status || data.transaction_status || '').trim().toLowerCase();
        const resultCode = data.result_code === null || typeof data.result_code === 'undefined'
            ? ''
            : String(data.result_code).trim();

        if (['paid', 'success', 'failed', 'error', 'not_found'].includes(status)) {
            return true;
        }

        if (status === 'pending') {
            return false;
        }

        if (resultCode !== '') {
            return resultCode !== '4999';
        }

        return false;
    };

    window.setMpesaStatusBadge = window.setMpesaStatusBadge || function(statusBadge, tone, message) {
        if (!statusBadge) {
            return;
        }

        const badge = document.createElement('span');
        const toneClasses = {
            success: 'badge bg-success',
            warning: 'badge bg-warning',
            danger: 'badge bg-danger',
            info: 'badge bg-info'
        };

        badge.className = toneClasses[tone] || toneClasses.info;
        badge.textContent = message;

        statusBadge.innerHTML = '';
        statusBadge.appendChild(badge);
    };

    window.startMpesaRetryCountdown = window.startMpesaRetryCountdown || function(button, seconds) {
        if (!button) {
            return;
        }

        const retrySeconds = Math.max(1, parseInt(seconds, 10) || 59);
        const baseLabel = button.dataset.baseLabel || button.textContent.trim() || 'Send STK';
        const retryUntil = Date.now() + (retrySeconds * 1000);

        button.dataset.baseLabel = baseLabel;
        button.dataset.retryUntil = String(retryUntil);

        if (button._mpesaRetryCountdownId) {
            window.clearInterval(button._mpesaRetryCountdownId);
        }

        const renderCountdown = () => {
            const remainingSeconds = Math.max(0, Math.ceil((retryUntil - Date.now()) / 1000));

            if (remainingSeconds > 0) {
                button.disabled = true;
                button.textContent = `${baseLabel} (${remainingSeconds}s)`;
                return;
            }

            button.disabled = false;
            button.textContent = baseLabel;
            if (button._mpesaRetryCountdownId) {
                window.clearInterval(button._mpesaRetryCountdownId);
                button._mpesaRetryCountdownId = null;
            }
            delete button.dataset.retryUntil;
        };

        renderCountdown();
        button._mpesaRetryCountdownId = window.setInterval(renderCountdown, 250);
    };

    window.stopMpesaRetryCountdown = window.stopMpesaRetryCountdown || function(button) {
        if (!button) {
            return;
        }

        const baseLabel = button.dataset.baseLabel || 'Send STK';

        if (button._mpesaRetryCountdownId) {
            window.clearInterval(button._mpesaRetryCountdownId);
            button._mpesaRetryCountdownId = null;
        }

        delete button.dataset.retryUntil;
        button.disabled = false;
        button.textContent = baseLabel;
    };

    window.setMpesaRetryButton = window.setMpesaRetryButton || function(button) {
        if (!button) {
            return;
        }

        const retryLabel = '{{ __('payment.retry_stk') }}';

        stopMpesaRetryCountdown(button);
        button.dataset.baseLabel = retryLabel;
        button.disabled = false;
        button.textContent = retryLabel;
    };

    function notifyMpesaStkSent(message) {
        const successMessage = message || 'STK push sent. Enter PIN on your phone.';

        if (window.showToast) {
            window.showToast('success', successMessage);
        } else if (window.toastr) {
            toastr.success(successMessage);
        }

        if (window.playSuccess) {
            window.playSuccess();
        }
    }

    function notifyMpesaWarning(message) {
        if (window.showToast) {
            window.showToast('warning', message);
        } else if (window.toastr) {
            toastr.warning(message);
        }

        if (window.playError) {
            window.playError();
        }
    }

    function notifyMpesaError(message) {
        if (window.showToast) {
            window.showToast('error', message);
        } else if (window.toastr) {
            toastr.error(message);
        }

        if (window.playError) {
            window.playError();
        }
    }

    window.resetMpesaRowState = window.resetMpesaRowState || function(row, options) {
        if (!row) {
            return;
        }

        const settings = options || {};
        const checkoutInput = row.querySelector('.checkout_request_id');
        const receiptInput = row.querySelector('.mpesa_receipt_number');
        const statusInput = row.querySelector('.mpesa_status');
        const statusBadge = row.querySelector('.mpesa-status-badge');
        const sendBtn = row.querySelector('.send-mpesa-stk');
        const phoneInput = row.querySelector('.mpesa-phone');

        if (checkoutInput) {
            checkoutInput.value = '';
        }
        if (receiptInput) {
            receiptInput.value = '';
        }
        if (statusInput) {
            statusInput.value = '';
        }
        if (statusBadge) {
            statusBadge.innerHTML = '';
        }
        if (sendBtn && typeof stopMpesaRetryCountdown === 'function') {
            stopMpesaRetryCountdown(sendBtn);
            sendBtn.dataset.baseLabel = '{{ __('payment.send_stk') }}';
            sendBtn.textContent = '{{ __('payment.send_stk') }}';
        }
        if (phoneInput && settings.clearPhone === true) {
            phoneInput.value = '';
        }
    };

    function getSellCheckoutMismatchMessage(expectedCheckout) {
        if (!expectedCheckout) {
            return mpesaI18n.noLinkedCheckoutYet;
        }

        return mpesaI18n.confirmationCheckoutMismatch;
    }

    function sellStatusMatchesCheckout(expectedCheckout, data) {
        if (!expectedCheckout) {
            return false;
        }

        const responseCheckout = data && (data.checkout_request_id || data.checkoutRequestId || '');
        return responseCheckout === expectedCheckout;
    }

    // Delegate click for sending STK and checking status
    document.body.addEventListener('click', async function(e) {
        const viewLogBtn = e.target.closest('.mpesa-view-log');
        if (viewLogBtn) {
            const row = viewLogBtn.closest('.payment_row');
            const checkoutInput = row ? row.querySelector('.checkout_request_id') : null;
            const phoneInput = row ? row.querySelector('.mpesa-phone') : null;
            const checkout = checkoutInput ? checkoutInput.value : '';
            const phone = phoneInput ? phoneInput.value.trim() : '';
            // Normalize phone for view log (allow 0-prefixed local format)
            const normalizedPhoneView = normalizeKenyanPhone(phone);

            const modal = document.getElementById('mpesa_log_modal');
            const body = modal.querySelector('.mpesa-log-body');
            body.innerHTML = '<div class="text-center">@lang('messages.loading')</div>';
            $('#mpesa_log_modal').modal('show');

            if (!checkout && !normalizedPhoneView) {
                body.innerHTML = `<div class="text-warning">${mpesaI18n.noRelatedCheckout}</div>`;
                return;
            }

            try {
                const url = new URL("{{ route('mpesa.logs') }}", window.location.origin);
                if (checkout) {
                    url.searchParams.set('checkout_request_id', checkout);
                }
                if (normalizedPhoneView) {
                    url.searchParams.set('phone', normalizedPhoneView);
                }

                const res = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                let data;
                try {
                    const ct = res.headers.get('content-type') || '';
                    if (ct.includes('application/json')) {
                        data = await res.json();
                    } else {
                        const text = await res.text();
                        console.error('Non-JSON response for mpesa.logs:', text);
                        body.innerHTML = `<div class="text-danger">${mpesaI18n.logsUnavailable}</div>`;
                        return;
                    }
                } catch (err) {
                    console.error('Error parsing JSON response for mpesa.logs', err);
                    body.innerHTML = `<div class="text-danger">${mpesaI18n.logsResponseUnreadable}</div>`;
                    return;
                }
                const renderPaymentsTable = (payments, title, highlightTarget) => {
                    if (!payments || payments.length === 0) {
                        return '';
                    }

                    let html = `<div class="mpesa-log-section"><h5>${title}</h5>`;
                    html += '<table class="table table-sm"><thead><tr><th>@lang('payment.status')</th><th>@lang('payment.failure_reason')</th><th>@lang('payment.receipt')</th><th>@lang('messages.date')</th></tr></thead><tbody>';
                    payments.forEach(p => {
                        const rowClass = highlightTarget && p.is_target ? ' class="bg-light-green"' : '';
                        html += `<tr${rowClass}><td>${p.transaction_status || mpesaI18n.notAvailable}</td><td>${p.result_desc || mpesaI18n.notAvailable}</td><td>${p.mpesa_receipt_number || mpesaI18n.notAvailable}</td><td>${p.created_at || mpesaI18n.notAvailable}</td></tr>`;
                    });
                    html += '</tbody></table></div>';

                    return html;
                };

                const exactPayments = (data && data.payments) || [];
                const relatedPayments = (data && data.related_payments) || [];

                if (res.ok && data && data.success) {
                    if (exactPayments.length === 0 && relatedPayments.length === 0) {
                        body.innerHTML = `<div class="text-center">${mpesaI18n.noLogsFound}</div>`;
                    } else {
                        let html = renderPaymentsTable(exactPayments, mpesaI18n.paymentCheckoutLogs, true);
                        html += renderPaymentsTable(relatedPayments, mpesaI18n.recentPhoneLogs, false);
                        body.innerHTML = html;
                    }
                } else {
                    let html = '<div class="text-warning">' + ((data && data.message) || mpesaI18n.unableFetchLogs) + '</div>';
                    if (data && data.related_payments && data.related_payments.length) {
                        html += renderPaymentsTable(data.related_payments, mpesaI18n.recentPhoneLogs, false);
                    }
                    body.innerHTML = html;
                }
            } catch (err) {
                console.error(err);
                body.innerHTML = `<div class="text-danger">${mpesaI18n.logsUnavailable}</div>`;
            }
            return;
        }
        const sendBtn = e.target.closest('.send-mpesa-stk');
        const checkBtn = e.target.closest('.check-mpesa-status');

        if (sendBtn) {
            const row = sendBtn.closest('.payment_row');
            const phoneInput = row.querySelector('.mpesa-phone');
            const amountInput = row.querySelector('.payment-amount');
            const checkoutInput = row.querySelector('.checkout_request_id');
            const receiptInput = row.querySelector('.mpesa_receipt_number');
            const statusInput = row.querySelector('.mpesa_status');
            const statusBadge = row.querySelector('.mpesa-status-badge');

            const phone = phoneInput ? phoneInput.value.trim() : '';
            const amount = amountInput ? amountInput.value.trim() : '';
            let normalizedPhone = normalizeKenyanPhone(phone);

            if (!normalizedPhone) {
                notifyMpesaError(mpesaI18n.invalidMpesaPhone);
                return;
            }

            resetMpesaRowState(row, { clearPhone: false });
            if (phoneInput) {
                phoneInput.value = phone;
            }

            if (sendBtn.dataset.retryUntil && Number(sendBtn.dataset.retryUntil) > Date.now()) {
                return;
            }

            let retryCountdownStarted = false;

            try {
                sendBtn.disabled = true;
                setMpesaStatusBadge(statusBadge, 'info', mpesaI18n.sendingStk);

                const res = await fetch("{{ route('mpesa.initiate') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    // Explicitly mark this initiation as a sell so server records it correctly
                    body: JSON.stringify({ phone: normalizedPhone, amount: amount, payment_type: 'sell' })
                });
                const data = await readMpesaJsonResponse(res, 'mpesa.initiate sell');
                if (data && data.checkout_request_id) {
                    checkoutInput.value = data.checkout_request_id;
                    statusInput.value = 'pending';
                    setMpesaStatusBadge(statusBadge, 'warning', resolveMpesaStatusMessage(data, mpesaI18n.stkSent));
                    notifyMpesaStkSent(data.message || mpesaI18n.stkSent);
                    retryCountdownStarted = true;
                    startMpesaRetryCountdown(sendBtn, 59);

                    // Clear any leftover checkout ids in other rows (prevent re-use across sells)
                    document.querySelectorAll('.checkout_request_id').forEach(function(ci){ if(ci !== checkoutInput) ci.value = ''; });
                    // Reset used flag for this row
                    row.dataset.mpesaUsed = '0';
                    const existingUsedInput = row.querySelector('.mpesa_used');
                    if (existingUsedInput) existingUsedInput.value = '0';

                    // start polling
                    startMpesaPolling(checkoutInput.value, normalizedPhone, row);
                } else if (data.transaction_status && data.transaction_status === 'success') {
                    // Only mark as paid automatically if the server returned a definite mpesa receipt number.
                    // If we don't have a receipt number yet, treat as pending and wait for callback/polling to confirm.
                    const mpesaReceipt = data.mpesa_receipt_number || data.receipt_number || '';
                    if (mpesaReceipt) {
                        receiptInput.value = mpesaReceipt;
                        statusInput.value = 'paid';
                        setMpesaStatusBadge(statusBadge, 'success', resolveMpesaStatusMessage(data, mpesaI18n.paymentConfirmed) + ' - ' + mpesaReceipt);
                        // Recalculate and auto-finalize when paid
                        try {
                            if (typeof calculate_balance_due === 'function') {
                                calculate_balance_due();
                                var bal = typeof __read_number === 'function' ? __read_number($('#in_balance_due')) : parseFloat(document.querySelector('#in_balance_due').value || 0);
                                // Guard: ensure there are product rows and this payment row hasn't been used yet
                                var productRowsExist = document.querySelectorAll('table#pos_table tbody .product_row').length > 0;
                                var alreadyUsed = row.dataset.mpesaUsed && row.dataset.mpesaUsed === '1';
                                if (!isNaN(bal) && bal <= 0.001 && productRowsExist && !alreadyUsed) {
                                    // mark as used to prevent reuse
                                    row.dataset.mpesaUsed = '1';
                                    let usedInput = row.querySelector('.mpesa_used');
                                    if (!usedInput) {
                                        usedInput = document.createElement('input');
                                        usedInput.type = 'hidden';
                                        usedInput.className = 'mpesa_used';
                                        usedInput.name = 'payment_mpesa_used[]';
                                        row.appendChild(usedInput);
                                    }
                                    usedInput.value = '1';
                                    setTimeout(function() { document.getElementById('pos-save').click(); }, 200);
                                }
                            }
                        } catch (err) {
                            console.error('Error during post-paid auto-finalize check', err);
                        }
                    } else {
                        // No receipt provided yet — treat as pending and show message to user
                        statusInput.value = 'pending';
                        setMpesaStatusBadge(statusBadge, 'warning', resolveMpesaStatusMessage(data, mpesaI18n.pendingConfirmation));
                    }
                } else {
                    setMpesaFailedState(statusBadge, statusInput, resolveMpesaStatusMessage(data, mpesaI18n.stkInitiationError));
                    setMpesaRetryButton(sendBtn);
                }
            } catch (err) {
                console.error(err);
                setMpesaFailedState(statusBadge, statusInput, err.message || mpesaI18n.stkInitiationError);
                setMpesaRetryButton(sendBtn);
            } finally {
                if (!retryCountdownStarted && !sendBtn.dataset.retryUntil) {
                    sendBtn.disabled = false;
                }
            }
        }

        if (checkBtn) {
            const row = checkBtn.closest('.payment_row');
            const sendBtn = row.querySelector('.send-mpesa-stk');
            const checkoutInput = row.querySelector('.checkout_request_id');
            const phoneInput = row.querySelector('.mpesa-phone');
            const statusBadge = row.querySelector('.mpesa-status-badge');
            const statusInput = row.querySelector('.mpesa_status');
            const receiptInput = row.querySelector('.mpesa_receipt_number');

            const checkout = checkoutInput ? checkoutInput.value : '';
            const phone = phoneInput ? phoneInput.value.trim() : '';
            // Normalize phone for status check (allow 0-prefixed local numbers)
            const normalizedPhoneCheck = normalizeKenyanPhone(phone);

            if (!checkout && !normalizedPhoneCheck) {
                const missingStatusMessage = mpesaI18n.statusLookupRequiresPhoneOrCheckout;
                notifyMpesaWarning(missingStatusMessage);
                return;
            }

            try {
                checkBtn.disabled = true;
                setMpesaStatusBadge(statusBadge, 'info', mpesaI18n.checking);

                const res = await fetch("{{ route('mpesa.queryStatus') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ checkout_request_id: checkout, phone: normalizedPhoneCheck })
                });
                const data = await readMpesaJsonResponse(res, 'mpesa.queryStatus sell');

                if (isMpesaFinalResponse(data)) {
                    stopMpesaRetryCountdown(sendBtn);
                }

                if ((data && data.success && (data.transaction_status === 'success' || data.transaction_status === 'paid')) && !sellStatusMatchesCheckout(checkout, data)) {
                    const mismatchMessage = getSellCheckoutMismatchMessage(checkout);
                    statusInput.value = 'pending';
                    setMpesaStatusBadge(statusBadge, 'warning', mismatchMessage);
                    notifyMpesaWarning(mismatchMessage);
                    setMpesaRetryButton(sendBtn);
                    return;
                }

                if (data && data.success && (data.transaction_status === 'success' || data.transaction_status === 'paid')) {
                    // Only mark paid if we have a MPESA receipt number from the query; otherwise treat as pending
                    const mpesaReceipt = data.mpesa_receipt_number || data.receipt_number || '';
                    if (mpesaReceipt) {
                        statusInput.value = 'paid';
                        receiptInput.value = mpesaReceipt;
                        setMpesaStatusBadge(statusBadge, 'success', resolveMpesaStatusMessage(data, mpesaI18n.paymentConfirmed) + ' - ' + mpesaReceipt);
                    } else {
                        statusInput.value = 'pending';
                        setMpesaStatusBadge(statusBadge, 'warning', resolveMpesaStatusMessage(data, mpesaI18n.pending));
                    }
                    try {
                        if (typeof calculate_balance_due === 'function') {
                            calculate_balance_due();
                            var bal = typeof __read_number === 'function' ? __read_number($('#in_balance_due')) : parseFloat(document.querySelector('#in_balance_due').value || 0);
                            // Guard: ensure there are product rows and this payment row hasn't been used yet
                            var productRowsExist = document.querySelectorAll('table#pos_table tbody .product_row').length > 0;
                            var alreadyUsed = row.dataset.mpesaUsed && row.dataset.mpesaUsed === '1';
                            if (!isNaN(bal) && bal <= 0.001 && productRowsExist && !alreadyUsed) {
                                row.dataset.mpesaUsed = '1';
                                let usedInput = row.querySelector('.mpesa_used');
                                if (!usedInput) {
                                    usedInput = document.createElement('input');
                                    usedInput.type = 'hidden';
                                    usedInput.className = 'mpesa_used';
                                    usedInput.name = 'payment_mpesa_used[]';
                                    row.appendChild(usedInput);
                                }
                                usedInput.value = '1';
                                setTimeout(function() { document.getElementById('pos-save').click(); }, 200);
                            }
                        }
                    } catch (err) {
                        console.error('Error during post-paid auto-finalize check', err);
                    }
                } else if (data.success && data.transaction_status === 'pending') {
                    statusInput.value = 'pending';
                    setMpesaStatusBadge(statusBadge, 'warning', resolveMpesaStatusMessage(data, mpesaI18n.pending));
                } else {
                    setMpesaFailedState(statusBadge, statusInput, resolveMpesaStatusMessage(data, data.status || mpesaI18n.notFound));
                    setMpesaRetryButton(sendBtn);
                }
            } catch (err) {
                console.error(err);
                setMpesaFailedState(statusBadge, statusInput, err.message || mpesaI18n.statusCheckError);
                setMpesaRetryButton(sendBtn);
            } finally {
                checkBtn.disabled = false;
            }
        }
    });

    // polling helper
    function startMpesaPolling(checkoutRequestId, phone, row) {
        const sendBtn = row.querySelector('.send-mpesa-stk');
        const statusBadge = row.querySelector('.mpesa-status-badge');
    let attempts = 0;
    // Poll more frequently so successful payments are detected quickly.
    // Use 1s interval and allow up to 60 attempts (~60s total) so success is detected almost immediately.
    const maxAttempts = 60;
    const interval = setInterval(async () => {
            attempts++;
            try {
                const res = await fetch("{{ route('mpesa.queryStatus') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ checkout_request_id: checkoutRequestId, phone: phone })
                });
                const data = await readMpesaJsonResponse(res, 'mpesa.queryStatus sell poll');

                if (isMpesaFinalResponse(data)) {
                    stopMpesaRetryCountdown(sendBtn);
                }

                if ((data && data.success && (data.transaction_status === 'success' || data.transaction_status === 'paid')) && !sellStatusMatchesCheckout(checkoutRequestId, data)) {
                    const mismatchMessage = getSellCheckoutMismatchMessage(checkoutRequestId);
                    row.querySelector('.mpesa_status').value = 'pending';
                    setMpesaStatusBadge(statusBadge, 'warning', mismatchMessage);
                    notifyMpesaWarning(mismatchMessage);
                    setMpesaRetryButton(sendBtn);
                    clearInterval(interval);
                    return;
                }

                if (data && data.success && (data.transaction_status === 'success' || data.transaction_status === 'paid')) {
                    row.querySelector('.mpesa_status').value = 'paid';
                    row.querySelector('.mpesa_receipt_number').value = data.mpesa_receipt_number || data.receipt_number || '';
                    const paidReceipt = data.mpesa_receipt_number || data.receipt_number || '';
                    const paidMessage = resolveMpesaStatusMessage(data, mpesaI18n.paymentConfirmed);
                    setMpesaStatusBadge(statusBadge, 'success', paidReceipt ? `${paidMessage} - ${paidReceipt}` : paidMessage);
                    // Recalculate balance and auto-finalize if balance is zero
                    try {
                        if (typeof calculate_balance_due === 'function') {
                            calculate_balance_due();
                            // small tolerance for floating point
                            var bal = typeof __read_number === 'function' ? __read_number($('#in_balance_due')) : parseFloat(document.querySelector('#in_balance_due').value || 0);
                            // Guard: ensure there are product rows and this payment row hasn't been used yet
                            var productRowsExist = document.querySelectorAll('table#pos_table tbody .product_row').length > 0;
                            var alreadyUsed = row.dataset.mpesaUsed && row.dataset.mpesaUsed === '1';
                            if (!isNaN(bal) && bal <= 0.001 && productRowsExist && !alreadyUsed) {
                                // trigger finalize just like other payment methods
                                // mark as used to prevent reuse
                                row.dataset.mpesaUsed = '1';
                                let usedInput = row.querySelector('.mpesa_used');
                                if (!usedInput) {
                                    usedInput = document.createElement('input');
                                    usedInput.type = 'hidden';
                                    usedInput.className = 'mpesa_used';
                                    usedInput.name = 'payment_mpesa_used[]';
                                    row.appendChild(usedInput);
                                }
                                usedInput.value = '1';
                                var finalizeBtn = document.getElementById('pos-save');
                                if (finalizeBtn) {
                                    // give a tiny delay to let ui update
                                    setTimeout(function() { finalizeBtn.click(); }, 200);
                                } else if (window.pos_form_obj && typeof window.pos_form_obj.submit === 'function') {
                                    setTimeout(function() { window.pos_form_obj.submit(); }, 200);
                                }
                            }
                        }
                    } catch (err) {
                        console.error('Error during post-paid auto-finalize check', err);
                    }
                    clearInterval(interval);
                    return;
                }
                if (data && data.transaction_status === 'failed') {
                    setMpesaFailedState(statusBadge, row.querySelector('.mpesa_status'), resolveMpesaStatusMessage(data, mpesaI18n.paymentFailed));
                    setMpesaRetryButton(sendBtn);
                    clearInterval(interval);
                    return;
                }
                if (data && data.transaction_status === 'pending') {
                    setMpesaStatusBadge(statusBadge, 'warning', resolveMpesaStatusMessage(data, mpesaI18n.pending));
                }
                if (attempts >= maxAttempts) {
                    setMpesaStatusBadge(statusBadge, 'warning', mpesaI18n.statusCheckTimeout);
                    clearInterval(interval);
                }
            } catch (err) {
                console.error(err);
                setMpesaFailedState(statusBadge, row.querySelector('.mpesa_status'), err.message || mpesaI18n.pollingError);
                setMpesaRetryButton(sendBtn);
                clearInterval(interval);
            }
    }, 1000);
    }
});
</script>
@endpush

<!-- Used for express checkout card transaction -->
<div class="modal fade" tabindex="-1" role="dialog" id="card_details_modal">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">@lang('lang_v1.card_transaction_details')</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">

                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('card_number', __('lang_v1.card_no')) !!}
                                {!! Form::text('', null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('lang_v1.card_no'),
                                    'id' => 'card_number',
                                    'autofocus',
                                ]) !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('card_holder_name', __('lang_v1.card_holder_name')) !!}
                                {!! Form::text('', null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('lang_v1.card_holder_name'),
                                    'id' => 'card_holder_name',
                                ]) !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('card_transaction_number', __('lang_v1.card_transaction_no')) !!}
                                {!! Form::text('', null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('lang_v1.card_transaction_no'),
                                    'id' => 'card_transaction_number',
                                ]) !!}
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('card_type', __('lang_v1.card_type')) !!}
                                {!! Form::select('', ['visa' => __('payment.visa'), 'master' => __('payment.mastercard')], 'visa', [
                                    'class' => 'form-control select2',
                                    'id' => 'card_type',
                                ]) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('card_month', __('lang_v1.month')) !!}
                                {!! Form::text('', null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('lang_v1.month'),
                                    'id' => 'card_month',
                                ]) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('card_year', __('lang_v1.year')) !!}
                                {!! Form::text('', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.year'), 'id' => 'card_year']) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('card_security', __('lang_v1.security_code')) !!}
                                {!! Form::text('', null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('lang_v1.security_code'),
                                    'id' => 'card_security',
                                ]) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white" id="pos-save-card">@lang('sale.finalize_payment')</button>
            </div>
        </div>
    </div>
</div>
