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

    const phone = phoneInput.val().trim();
    const amount = __read_number(amountInput);

    if (!phone) {
        toastr.error(__translate('payment.mpesa_phone_required'));
        return;
    }

    if (!amount || amount <= 0) {
        toastr.error('Please enter a valid payment amount');
        return;
    }

    // Normalize phone
    let normalizedPhone = phone.replace(/^\+/, '');
    if (/^0?7\d{8}$/.test(normalizedPhone)) {
        normalizedPhone = '254' + normalizedPhone.replace(/^0/, '');
    }

    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ' + __translate('payment.initiating_payment'));
    statusBadge.html('<i class="fas fa-spinner fa-spin"></i> Initiating...');

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

        const data = await response.json();

        if (data.success) {
            checkoutInput.val(data.checkoutRequestId);
            toastr.success(__translate('payment.stk_sent'));
            statusBadge.html('<span class="badge bg-warning">Pending</span>');
            
            // Start polling for status
            pollMpesaStatusPurchase(data.checkoutRequestId, statusBadge, receiptInput, statusInput);
        } else {
            toastr.error(data.message || __translate('payment.payment_initiation_failed'));
            statusBadge.html('<span class="badge bg-danger">Failed</span>');
        }
    } catch (error) {
        console.error('Error:', error);
        toastr.error(__translate('payment.payment_initiation_failed'));
        statusBadge.html('<span class="badge bg-danger">Error</span>');
    } finally {
        btn.prop('disabled', false).html(__translate('payment.send_stk'));
    }
});

// Check M-Pesa Status for Purchase Payments
$(document).on('click', '.check-mpesa-status-purchase', async function(e) {
    e.preventDefault();
    const btn = $(this);
    const form = $('#transaction_payment_add_form');
    const checkoutInput = form.find('.checkout_request_id');
    const statusBadge = form.find('.mpesa-status-badge');
    const receiptInput = form.find('.mpesa_receipt_number');
    const statusInput = form.find('.mpesa_status');

    const checkoutRequestId = checkoutInput.val();

    if (!checkoutRequestId) {
        toastr.error('No M-Pesa transaction to check');
        return;
    }

    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Checking...');
    statusBadge.html('<i class="fas fa-spinner fa-spin"></i> Checking...');

    try {
        const response = await fetch('/payment/check-status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            body: JSON.stringify({ checkoutRequestId: checkoutRequestId })
        });

        const data = await response.json();

        if (data.status === 'paid') {
            receiptInput.val(data.mpesa_receipt_number);
            statusInput.val('paid');
            statusBadge.html('<span class="badge bg-success">Paid - ' + data.mpesa_receipt_number + '</span>');
            toastr.success('Payment confirmed: ' + data.mpesa_receipt_number);
        } else if (data.status === 'pending') {
            statusBadge.html('<span class="badge bg-warning">Pending</span>');
            toastr.info('Payment is still pending');
        } else {
            statusBadge.html('<span class="badge bg-danger">Failed</span>');
            toastr.error(data.message || 'Payment failed');
        }
    } catch (error) {
        console.error('Error:', error);
        toastr.error('Error checking payment status');
        statusBadge.html('<span class="badge bg-danger">Error</span>');
    } finally {
        btn.prop('disabled', false).html(__translate('payment.check_status'));
    }
});

// Poll M-Pesa status for purchase payments
function pollMpesaStatusPurchase(checkoutRequestId, statusBadge, receiptInput, statusInput, attempts = 0) {
    if (attempts >= 20) { // Stop after 20 attempts (60 seconds)
        statusBadge.html('<span class="badge bg-warning">Timeout - Check Status</span>');
        return;
    }

    setTimeout(async () => {
        try {
            const response = await fetch('/payment/check-status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: JSON.stringify({ checkoutRequestId: checkoutRequestId })
            });

            const data = await response.json();

            if (data.status === 'paid') {
                receiptInput.val(data.mpesa_receipt_number);
                statusInput.val('paid');
                statusBadge.html('<span class="badge bg-success">Paid - ' + data.mpesa_receipt_number + '</span>');
                toastr.success('Payment confirmed: ' + data.mpesa_receipt_number);
            } else if (data.status === 'pending') {
                // Continue polling
                pollMpesaStatusPurchase(checkoutRequestId, statusBadge, receiptInput, statusInput, attempts + 1);
            } else {
                statusBadge.html('<span class="badge bg-danger">Failed</span>');
                toastr.error(data.message || 'Payment failed');
            }
        } catch (error) {
            console.error('Polling error:', error);
            pollMpesaStatusPurchase(checkoutRequestId, statusBadge, receiptInput, statusInput, attempts + 1);
        }
    }, 3000); // Check every 3 seconds
}