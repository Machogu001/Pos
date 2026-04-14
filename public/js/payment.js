$(document).ready(function() {
    $(document).on('click', '.add_payment_modal', function(e) {
        e.preventDefault();
        var container = $('.payment_modal');

        $.ajax({
            url: $(this).attr('href'),
            dataType: 'json',
            success: function(result) {
                if (result.status == 'due') {
                    container.html(result.view).modal('show');
                    __currency_convert_recursively(container);
                    $('#paid_on').datetimepicker({
                        format: moment_date_format + ' ' + moment_time_format,
                        ignoreReadonly: true,
                    });
                    container.find('form#transaction_payment_add_form').validate();
                    set_default_payment_account();

                    $('.payment_modal')
                        .find('input[type="checkbox"].input-icheck')
                        .each(function() {
                            $(this).iCheck({
                                checkboxClass: 'icheckbox_square-blue',
                                radioClass: 'iradio_square-blue',
                            });
                        });
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });
    $(document).on('click', '.edit_payment', function(e) {
        e.preventDefault();
        var container = $('.edit_payment_modal');

        $.ajax({
            url: $(this).data('href'),
            dataType: 'html',
            success: function(result) {
                container.html(result).modal('show');
                __currency_convert_recursively(container);
                $('#paid_on').datetimepicker({
                    format: moment_date_format + ' ' + moment_time_format,
                    ignoreReadonly: true,
                });
                container.find('form#transaction_payment_add_form').validate();
            },
        });
    });

    $(document).on('click', '.view_payment_modal', function(e) {
        e.preventDefault();
        var container = $('.payment_modal');

        $.ajax({
            url: $(this).attr('href'),
            dataType: 'html',
            success: function(result) {
                $(container)
                    .html(result)
                    .modal('show');
                __currency_convert_recursively(container);
            },
        });
    });
    $(document).on('click', '.delete_payment', function(e) {
        swal({
            title: LANG.sure,
            text: LANG.confirm_delete_payment,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                $.ajax({
                    url: $(this).data('href'),
                    method: 'delete',
                    dataType: 'json',
                    success: function(result) {
                        if (result.success === true) {
                            $('div.payment_modal').modal('hide');
                            $('div.edit_payment_modal').modal('hide');
                            toastr.success(result.msg);
                            if (typeof purchase_table != 'undefined') {
                                purchase_table.ajax.reload();
                            }
                            if (typeof sell_table != 'undefined') {
                                sell_table.ajax.reload();
                            }
                            if (typeof expense_table != 'undefined') {
                                expense_table.ajax.reload();
                            }
                            if (typeof ob_payment_table != 'undefined') {
                                ob_payment_table.ajax.reload();
                            }
                            // project Module
                            if (typeof project_invoice_datatable != 'undefined') {
                                project_invoice_datatable.ajax.reload();
                            }
                            
                            if ($('#contact_payments_table').length) {
                                get_contact_payments();
                            }
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });

    //view single payment
    $(document).on('click', '.view_payment', function() {
        var url = $(this).data('href');
        var container = $('.view_modal');
        $.ajax({
            method: 'GET',
            url: url,
            dataType: 'html',
            success: function(result) {
                $(container)
                    .html(result)
                    .modal('show');
                __currency_convert_recursively(container);
            },
        });
    });
});

$(document).on('change', '#transaction_payment_add_form .payment_types_dropdown', function(e) {
    set_default_payment_account();
});

function set_default_payment_account() {
    var default_accounts = {};

    if (!_.isUndefined($('#transaction_payment_add_form #default_payment_accounts').val())) {
        default_accounts = JSON.parse($('#transaction_payment_add_form #default_payment_accounts').val());
    }

    var payment_type = $('#transaction_payment_add_form .payment_types_dropdown').val();
    if (payment_type && payment_type != 'advance') {
        var default_account = !_.isEmpty(default_accounts) && default_accounts[payment_type]['account'] ? 
            default_accounts[payment_type]['account'] : '';
        $('#transaction_payment_add_form #account_id').val(default_account);
        $('#transaction_payment_add_form #account_id').change();
    }
}

$(document).on('change', '.payment_types_dropdown', function(e) {
    var payment_type = $('#transaction_payment_add_form .payment_types_dropdown').val();
    account_dropdown = $('#transaction_payment_add_form #account_id');
    if (payment_type == 'advance') {
        if (account_dropdown) {
            account_dropdown.prop('disabled', true);
            account_dropdown.closest('.form-group').addClass('hide');
        }
    } else {
        if (account_dropdown) {
            account_dropdown.prop('disabled', false); 
            account_dropdown.closest('.form-group').removeClass('hide');
        }    
    }
});

$(document).on('submit', 'form#transaction_payment_add_form', function(e){
    var is_valid = true;
    var payment_type = $('#transaction_payment_add_form .payment_types_dropdown').val();
    var denomination_for_payment_types = JSON.parse($('#transaction_payment_add_form .enable_cash_denomination_for_payment_methods').val());
    if (denomination_for_payment_types.includes(payment_type) && $('#transaction_payment_add_form .is_strict').length && $('#transaction_payment_add_form .is_strict').val() === '1' ) {
        var payment_amount = __read_number($('#transaction_payment_add_form .payment_amount'));
        var total_denomination = $('#transaction_payment_add_form').find('input.denomination_total_amount').val();
        if (payment_amount != total_denomination ) {
            is_valid = false;
        }
    }

    $('#transaction_payment_add_form').find('button[type="submit"]')
            .attr('disabled', false);

    if (!is_valid) {
        $('#transaction_payment_add_form').find('.cash_denomination_error').removeClass('hide');
        e.preventDefault();
        return false;
    } else {
        $('#transaction_payment_add_form').find('.cash_denomination_error').addClass('hide');
    }
    
})

function normalizeKenyanPhone(rawPhone) {
    const digits = String(rawPhone || '').trim().replace(/[^\d+]/g, '');

    if (/^(07|01)\d{8}$/.test(digits)) {
        return '254' + digits.slice(1);
    }

    if (/^\+?254[17]\d{8}$/.test(digits)) {
        return digits.replace(/^\+/, '');
    }

    return null;
}

function translatePaymentText(key, fallback) {
    try {
        if (typeof __translate === 'function') {
            const translated = __translate(key);
            if (translated && translated !== key) {
                return translated;
            }
        }
    } catch (error) {
        console.warn('Payment translation lookup failed for', key, error);
    }

    return fallback;
}

function notifyMpesaStkSent(message) {
    const successMessage = message || translatePaymentText('payment.stk_sent', 'STK push sent. Enter PIN on your phone.');

    if (window.showToast) {
        window.showToast('success', successMessage);
    } else if (window.toastr) {
        toastr.success(successMessage);
    }

    if (window.playSuccess) {
        window.playSuccess();
    }
}

function getMpesaStatusMessage(data, fallback) {
    if (data && typeof data.result_desc === 'string' && data.result_desc.trim() !== '') {
        return data.result_desc.trim();
    }

    if (data && typeof data.message === 'string' && data.message.trim() !== '') {
        return data.message.trim();
    }

    return fallback;
}

function isMpesaFinalResponse(data) {
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
}

function setMpesaStatusBadge(statusBadge, tone, message) {
    const badgeClasses = {
        success: 'badge bg-success',
        warning: 'badge bg-warning',
        danger: 'badge bg-danger',
        info: 'badge bg-info'
    };

    const badgeClass = badgeClasses[tone] || badgeClasses.info;
    statusBadge.html('<span class="' + badgeClass + '"></span>');
    statusBadge.find('span').text(message);
}

function startMpesaRetryCountdown(button, seconds) {
    const retrySeconds = Math.max(1, parseInt(seconds, 10) || 59);
    const baseLabel = button.data('baseLabel') || button.text().trim() || translatePaymentText('payment.send_stk', 'Send STK');
    const retryUntil = Date.now() + (retrySeconds * 1000);
    const existingTimer = button.data('retryTimerId');

    button.data('baseLabel', baseLabel);
    button.data('retryUntil', retryUntil);

    if (existingTimer) {
        window.clearInterval(existingTimer);
    }

    const render = () => {
        const remainingSeconds = Math.max(0, Math.ceil((retryUntil - Date.now()) / 1000));

        if (remainingSeconds > 0) {
            button.prop('disabled', true).text(baseLabel + ' (' + remainingSeconds + 's)');
            return;
        }

        button.prop('disabled', false).text(baseLabel);
        window.clearInterval(button.data('retryTimerId'));
        button.removeData('retryTimerId');
        button.removeData('retryUntil');
    };

    render();
    button.data('retryTimerId', window.setInterval(render, 250));
}

function stopMpesaRetryCountdown(button) {
    if (!button || !button.length) {
        return;
    }

    const timerId = button.data('retryTimerId');
    const baseLabel = button.data('baseLabel') || translatePaymentText('payment.send_stk', 'Send STK');

    if (timerId) {
        window.clearInterval(timerId);
    }

    button.prop('disabled', false).text(baseLabel);
    button.removeData('retryTimerId');
    button.removeData('retryUntil');
}

function setMpesaRetryButton(button) {
    if (!button || !button.length) {
        return;
    }

    stopMpesaRetryCountdown(button);
    button.data('baseLabel', translatePaymentText('payment.retry_stk', 'Retry STK'));
    button.prop('disabled', false).text(translatePaymentText('payment.retry_stk', 'Retry STK'));
}

async function readJsonResponse(response, contextLabel) {
    const contentType = response.headers.get('content-type') || '';
    if (contentType.includes('application/json')) {
        const responseJson = await response.json();
        if (!response.ok) {
            const error = new Error(responseJson.message || 'Request failed');
            error.payload = responseJson;
            error.status = response.status;
            throw error;
        }

        return responseJson;
    }

    if (!response.ok || !contentType.includes('application/json')) {
        const responseText = await response.text();
        console.error(contextLabel + ' unexpected response:', responseText);
        throw new Error('Unexpected server response');
    }
}

// M-Pesa STK Push for Purchase Payments
$(document).on('click', '.send-mpesa-stk-purchase', async function(e) {
    e.preventDefault();
    const btn = $(this);
    const form = $('#transaction_payment_add_form');
    const phoneInput = form.find('.mpesa-phone');
    const amountInput = form.find('.payment_amount');
    const checkoutInput = form.find('.checkout_request_id');
    const receiptInput = form.find('.mpesa_receipt_number');
    const statusInput = form.find('.mpesa_status');
    const statusBadge = form.find('.mpesa-status-badge');

    const retryUntil = Number(btn.data('retryUntil') || 0);
    if (retryUntil > Date.now()) {
        return;
    }

    const phone = phoneInput.val().trim();
    const amount = __read_number(amountInput);

    if (!phone) {
        toastr.error(translatePaymentText('payment.mpesa_phone_required', 'M-Pesa phone number is required.'));
        return;
    }

    if (!amount || amount <= 0) {
        toastr.error('Please enter a valid payment amount');
        return;
    }

    const normalizedPhone = normalizeKenyanPhone(phone);
    if (!normalizedPhone) {
        toastr.error(translatePaymentText('payment.invalid_phone_format', 'Invalid phone number format. Use 254712345678, 0712345678, or 0112345678.'));
        return;
    }

    let retryCountdownStarted = false;

    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ' + translatePaymentText('payment.initiating_payment', 'Initiating payment... please wait.'));
    setMpesaStatusBadge(statusBadge, 'info', translatePaymentText('payment.initiating_payment', 'Initiating payment... please wait.'));

    try {
        const response = await fetch('/mpesa/initiate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            body: JSON.stringify({
                phone: normalizedPhone,
                amount: amount,
                payment_type: 'purchase'
            })
        });

        const data = await readJsonResponse(response, 'mpesa.initiate purchase');
        const checkoutRequestId = data.checkout_request_id || data.checkoutRequestId || '';

        if (data.success || checkoutRequestId) {
            checkoutInput.val(checkoutRequestId);
            notifyMpesaStkSent(data.message || translatePaymentText('payment.stk_sent', 'STK push sent. Enter PIN on your phone.'));
            setMpesaStatusBadge(statusBadge, 'warning', getMpesaStatusMessage(data, 'STK push sent. Enter PIN on your phone.'));
            retryCountdownStarted = true;
            startMpesaRetryCountdown(btn, 59);
            
            // Start polling for status
            pollMpesaStatusPurchase(checkoutRequestId, normalizedPhone, statusBadge, receiptInput, statusInput);
        } else {
            toastr.error(data.message || __translate('payment.payment_initiation_failed'));
            setMpesaStatusBadge(statusBadge, 'danger', getMpesaStatusMessage(data, 'Payment initiation failed'));
            setMpesaRetryButton(btn);
        }
    } catch (error) {
        console.error('Error:', error);
        toastr.error(error.message || translatePaymentText('payment.payment_initiation_failed', 'Payment initiation failed. Please try again.'));
        setMpesaStatusBadge(statusBadge, 'danger', error.message || 'Payment initiation failed');
        setMpesaRetryButton(btn);
    } finally {
        if (!retryCountdownStarted) {
            if (btn.text().trim() === translatePaymentText('payment.initiating_payment', 'Initiating payment... please wait.')) {
                btn.prop('disabled', false).text(translatePaymentText('payment.send_stk', 'Send STK'));
            }
        }
    }
});

// Check M-Pesa Status for Purchase Payments
$(document).on('click', '.check-mpesa-status-purchase', async function(e) {
    e.preventDefault();
    const btn = $(this);
    const form = $('#transaction_payment_add_form');
    const sendBtn = form.find('.send-mpesa-stk-purchase');
    const checkoutInput = form.find('.checkout_request_id');
    const phoneInput = form.find('.mpesa-phone');
    const statusBadge = form.find('.mpesa-status-badge');
    const receiptInput = form.find('.mpesa_receipt_number');
    const statusInput = form.find('.mpesa_status');

    const checkoutRequestId = checkoutInput.val();
    const normalizedPhone = normalizeKenyanPhone(phoneInput.val().trim());

    if (!checkoutRequestId && !normalizedPhone) {
        toastr.error('No M-Pesa transaction to check');
        return;
    }

    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Checking...');
    setMpesaStatusBadge(statusBadge, 'info', 'Checking...');

    try {
        const response = await fetch('/payment/check-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            body: JSON.stringify({ checkout_request_id: checkoutRequestId, phone: normalizedPhone })
        });

        const data = await readJsonResponse(response, 'payment.checkStatus purchase');

        if (isMpesaFinalResponse(data)) {
            stopMpesaRetryCountdown(sendBtn);
        }

        if (data.status === 'paid' || data.status === 'success') {
            receiptInput.val(data.mpesa_receipt_number);
            statusInput.val('paid');
            const paidMessage = getMpesaStatusMessage(data, 'Payment confirmed') + (data.mpesa_receipt_number ? ' - ' + data.mpesa_receipt_number : '');
            setMpesaStatusBadge(statusBadge, 'success', paidMessage);
            toastr.success(paidMessage);
        } else if (data.status === 'pending') {
            const pendingMessage = getMpesaStatusMessage(data, 'Payment is still pending');
            setMpesaStatusBadge(statusBadge, 'warning', pendingMessage);
            toastr.info(pendingMessage);
        } else {
            const failedMessage = getMpesaStatusMessage(data, 'Payment failed');
            setMpesaStatusBadge(statusBadge, 'danger', failedMessage);
            toastr.error(failedMessage);
            setMpesaRetryButton(sendBtn);
        }
    } catch (error) {
        console.error('Error:', error);
        toastr.error(error.message || 'Error checking payment status');
        setMpesaStatusBadge(statusBadge, 'danger', error.message || 'Error checking payment status');
        setMpesaRetryButton(sendBtn);
    } finally {
        btn.prop('disabled', false).html(translatePaymentText('payment.check_status', 'Check Status'));
    }
});

// Poll M-Pesa status for purchase payments
function pollMpesaStatusPurchase(checkoutRequestId, phone, statusBadge, receiptInput, statusInput, attempts = 0) {
    if (attempts >= 20) { // Stop after 20 attempts (60 seconds)
        setMpesaStatusBadge(statusBadge, 'warning', 'Timeout - Check Status');
        return;
    }

    const sendBtn = $('#transaction_payment_add_form').find('.send-mpesa-stk-purchase');

    setTimeout(async () => {
        try {
            const response = await fetch('/payment/check-status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify({ checkout_request_id: checkoutRequestId, phone: phone })
            });

            const data = await readJsonResponse(response, 'payment.checkStatus purchase poll');

            if (isMpesaFinalResponse(data)) {
                stopMpesaRetryCountdown(sendBtn);
            }

            if (data.status === 'paid' || data.status === 'success') {
                receiptInput.val(data.mpesa_receipt_number);
                statusInput.val('paid');
                const paidMessage = getMpesaStatusMessage(data, 'Payment confirmed') + (data.mpesa_receipt_number ? ' - ' + data.mpesa_receipt_number : '');
                setMpesaStatusBadge(statusBadge, 'success', paidMessage);
                toastr.success(paidMessage);
            } else if (data.status === 'pending') {
                setMpesaStatusBadge(statusBadge, 'warning', getMpesaStatusMessage(data, 'Payment is still pending'));
                // Continue polling
                pollMpesaStatusPurchase(checkoutRequestId, phone, statusBadge, receiptInput, statusInput, attempts + 1);
            } else {
                const failedMessage = getMpesaStatusMessage(data, 'Payment failed');
                setMpesaStatusBadge(statusBadge, 'danger', failedMessage);
                toastr.error(failedMessage);
                setMpesaRetryButton(sendBtn);
            }
        } catch (error) {
            console.error('Polling error:', error);
            setMpesaRetryButton(sendBtn);
            pollMpesaStatusPurchase(checkoutRequestId, phone, statusBadge, receiptInput, statusInput, attempts + 1);
        }
    }, 3000); // Check every 3 seconds
}