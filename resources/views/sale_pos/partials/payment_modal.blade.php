<div class="modal fade" tabindex="-1" role="dialog" id="modal_payment">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
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
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
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
            let normalizedPhoneView = phone.replace(/^\+/, '');
            if (/^0?7\d{8}$/.test(normalizedPhoneView)) {
                normalizedPhoneView = normalizedPhoneView.replace(/^0/, '254');
            }

            const modal = document.getElementById('mpesa_log_modal');
            const body = modal.querySelector('.mpesa-log-body');
            body.innerHTML = '<div class="text-center">@lang('messages.loading')</div>';
            $('#mpesa_log_modal').modal('show');

            try {
                const url = new URL("{{ route('mpesa.logs') }}", window.location.origin);
                if (checkout) url.searchParams.set('checkout_request_id', checkout);
                else if (normalizedPhoneView) url.searchParams.set('phone', normalizedPhoneView);

                const res = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                let data;
                try {
                    const ct = res.headers.get('content-type') || '';
                    if (res.ok && ct.includes('application/json')) {
                        data = await res.json();
                    } else {
                        const text = await res.text();
                        console.error('Non-JSON response for mpesa.logs:', text);
                        body.innerHTML = '<div class="text-danger">Server error fetching logs</div>';
                        return;
                    }
                } catch (err) {
                    console.error('Error parsing JSON response for mpesa.logs', err);
                    body.innerHTML = '<div class="text-danger">Error parsing server response</div>';
                    return;
                }
                if (data && data.success) {
                    if (!data.payments || data.payments.length === 0) {
                        body.innerHTML = '<div class="text-center">No logs found</div>';
                    } else {
                        let html = '<table class="table table-sm"><thead><tr><th>@lang('payment.status')</th><th>@lang('payment.receipt')</th><th>@lang('messages.date')</th></tr></thead><tbody>';
                        data.payments.forEach(p => {
                            html += `<tr><td>${p.transaction_status}</td><td>${p.mpesa_receipt_number || 'N/A'}</td><td>${p.created_at}</td></tr>`;
                        });
                        html += '</tbody></table>';
                        body.innerHTML = html;
                    }
                } else {
                    body.innerHTML = '<div class="text-danger">' + (data.message || 'Error') + '</div>';
                }
            } catch (err) {
                console.error(err);
                body.innerHTML = '<div class="text-danger">Error fetching logs</div>';
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
            // Normalize local phone formats (0717xxxxxxx) to 2547xxxxxxxx
            let normalizedPhone = phone.replace(/^\+/, '');
            if (/^0?7\d{8}$/.test(normalizedPhone)) {
                normalizedPhone = normalizedPhone.replace(/^0/, '254');
            }

            if (!normalizedPhone || !/^2547\d{8}$/.test(normalizedPhone)) {
                alert('Please enter a valid MPESA phone (e.g. 0717XXXXXXX or 2547XXXXXXXX)');
                return;
            }

            try {
                sendBtn.disabled = true;
                statusBadge.textContent = 'Sending STK...';

                const res = await fetch("{{ route('mpesa.initiate') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    // Explicitly mark this initiation as a sell so server records it correctly
                    body: JSON.stringify({ phone: normalizedPhone, amount: amount, payment_type: 'sell' })
                });
                let data;
                try {
                    const ct = res.headers.get('content-type') || '';
                    if (res.ok && ct.includes('application/json')) {
                        data = await res.json();
                    } else {
                        const text = await res.text();
                        console.error('Non-JSON response for mpesa.initiate:', text);
                        statusBadge.textContent = 'Server error initiating STK';
                        return;
                    }
                } catch (err) {
                    console.error('Error parsing JSON for mpesa.initiate', err);
                    statusBadge.textContent = 'Error initiating STK';
                    return;
                }
                if (data && data.checkout_request_id) {
                    checkoutInput.value = data.checkout_request_id;
                    statusInput.value = 'pending';
                    statusBadge.textContent = 'Pending - awaiting customer PIN';

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
                        statusBadge.textContent = 'Paid';
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
                        statusBadge.textContent = 'Pending - awaiting confirmation';
                    }
                } else {
                    statusBadge.textContent = data.message || 'Error initiating STK';
                }
            } catch (err) {
                console.error(err);
                statusBadge.textContent = 'Error initiating STK';
            } finally {
                sendBtn.disabled = false;
            }
        }

        if (checkBtn) {
            const row = checkBtn.closest('.payment_row');
            const checkoutInput = row.querySelector('.checkout_request_id');
            const phoneInput = row.querySelector('.mpesa-phone');
            const statusBadge = row.querySelector('.mpesa-status-badge');
            const statusInput = row.querySelector('.mpesa_status');
            const receiptInput = row.querySelector('.mpesa_receipt_number');

            const checkout = checkoutInput ? checkoutInput.value : '';
            const phone = phoneInput ? phoneInput.value.trim() : '';
            // Normalize phone for status check (allow 0-prefixed local numbers)
            let normalizedPhoneCheck = phone.replace(/^\+/, '');
            if (/^0?7\d{8}$/.test(normalizedPhoneCheck)) {
                normalizedPhoneCheck = normalizedPhoneCheck.replace(/^0/, '254');
            }

            if (!checkout && !normalizedPhoneCheck) {
                alert('Provide phone or checkout id to check status');
                return;
            }

            try {
                checkBtn.disabled = true;
                statusBadge.textContent = 'Checking...';

                const res = await fetch("{{ route('mpesa.queryStatus') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ checkout_request_id: checkout, phone: normalizedPhoneCheck })
                });
                let data;
                try {
                    const ct = res.headers.get('content-type') || '';
                    if (res.ok && ct.includes('application/json')) {
                        data = await res.json();
                    } else {
                        const text = await res.text();
                        console.error('Non-JSON response for mpesa.queryStatus:', text);
                        statusBadge.textContent = 'Server error checking status';
                        return;
                    }
                } catch (err) {
                    console.error('Error parsing JSON for mpesa.queryStatus', err);
                    statusBadge.textContent = 'Error checking status';
                    return;
                }
                if (data && data.success && (data.transaction_status === 'success' || data.transaction_status === 'paid')) {
                    // Only mark paid if we have a MPESA receipt number from the query; otherwise treat as pending
                    const mpesaReceipt = data.mpesa_receipt_number || data.receipt_number || '';
                    if (mpesaReceipt) {
                        statusInput.value = 'paid';
                        receiptInput.value = mpesaReceipt;
                        statusBadge.textContent = 'Paid';
                    } else {
                        statusInput.value = 'pending';
                        statusBadge.textContent = 'Pending';
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
                    statusBadge.textContent = 'Pending';
                } else {
                    statusBadge.textContent = data.message || (data.status || 'Not found');
                }
            } catch (err) {
                console.error(err);
                statusBadge.textContent = 'Error checking status';
            } finally {
                checkBtn.disabled = false;
            }
        }
    });

    // polling helper
    function startMpesaPolling(checkoutRequestId, phone, row) {
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
                let data;
                try {
                    const ct = res.headers.get('content-type') || '';
                    if (res.ok && ct.includes('application/json')) {
                        data = await res.json();
                    } else {
                        const text = await res.text();
                        console.error('Non-JSON response for mpesa.queryStatus (poll):', text);
                        statusBadge.textContent = 'Server error';
                        clearInterval(interval);
                        return;
                    }
                } catch (err) {
                    console.error('Error parsing JSON for mpesa.queryStatus (poll):', err);
                    statusBadge.textContent = 'Polling error';
                    clearInterval(interval);
                    return;
                }
                if (data && data.success && (data.transaction_status === 'success' || data.transaction_status === 'paid')) {
                    row.querySelector('.mpesa_status').value = 'paid';
                    row.querySelector('.mpesa_receipt_number').value = data.mpesa_receipt_number || data.receipt_number || '';
                    statusBadge.textContent = 'Paid';
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
                if (attempts >= maxAttempts) {
                    statusBadge.textContent = 'Timed out - try check status';
                    clearInterval(interval);
                }
            } catch (err) {
                console.error(err);
                statusBadge.textContent = 'Polling error';
                clearInterval(interval);
            }
    }, 1000);
    }
});
</script>
@endpush

@push('scripts')
<script>
// Fallback: attach jQuery delegated handlers if available (some pages rely on jQuery event system)
if (window.jQuery) {
    (function($){
        $(document).on('click', '.send-mpesa-stk', async function(e){
            e.preventDefault();
            const sendBtn = this;
            const row = $(this).closest('.payment_row')[0];
            if (!row) return;
            const phoneInput = row.querySelector('.mpesa-phone');
            const amountInput = row.querySelector('.payment-amount');
            const checkoutInput = row.querySelector('.checkout_request_id');
            const receiptInput = row.querySelector('.mpesa_receipt_number');
            const statusInput = row.querySelector('.mpesa_status');
            const statusBadge = row.querySelector('.mpesa-status-badge');

            const phone = phoneInput ? phoneInput.value.trim() : '';
            const amount = amountInput ? amountInput.value.trim() : '';
            let normalizedPhone = phone.replace(/^\+/, '');
            if (/^0?7\d{8}$/.test(normalizedPhone)) {
                normalizedPhone = normalizedPhone.replace(/^0/, '254');
            }
            if (!normalizedPhone || !/^2547\d{8}$/.test(normalizedPhone)) {
                alert('Please enter a valid MPESA phone (e.g. 0717XXXXXXX or 2547XXXXXXXX)');
                return;
            }

            try {
                sendBtn.disabled = true;
                if (statusBadge) statusBadge.textContent = 'Sending STK...';

                const res = await fetch("{{ route('mpesa.initiate') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    // jQuery fallback path: include payment_type='sell' as well
                    body: JSON.stringify({ phone: normalizedPhone, amount: amount, payment_type: 'sell' })
                });
                const data = await res.json();
                if (data.checkout_request_id) {
                    if (checkoutInput) checkoutInput.value = data.checkout_request_id;
                    if (statusInput) statusInput.value = 'pending';
                    if (statusBadge) statusBadge.textContent = 'Pending - awaiting customer PIN';
                    // Clear any leftover checkout ids in other rows (prevent re-use across sells)
                    document.querySelectorAll('.checkout_request_id').forEach(function(ci){ if(ci !== checkoutInput) ci.value = ''; });
                    // Reset used flag for this row
                    row.dataset.mpesaUsed = '0';
                    const existingUsedInput2 = row.querySelector('.mpesa_used');
                    if (existingUsedInput2) existingUsedInput2.value = '0';
                    // start polling
                    startMpesaPolling(data.checkout_request_id, normalizedPhone, row);
                } else if (data.transaction_status && data.transaction_status === 'success') {
                    // Only auto-mark paid if we received a definitive mpesa receipt number
                    const mpesaReceipt = data.mpesa_receipt_number || data.receipt_number || '';
                    if (mpesaReceipt) {
                        if (receiptInput) receiptInput.value = mpesaReceipt;
                        if (statusInput) statusInput.value = 'paid';
                        if (statusBadge) statusBadge.textContent = 'Paid';
                    } else {
                        if (statusInput) statusInput.value = 'pending';
                        if (statusBadge) statusBadge.textContent = 'Pending - awaiting confirmation';
                    }
                    // Recalculate and auto-finalize when paid
                    try {
                        if (typeof calculate_balance_due === 'function') {
                            calculate_balance_due();
                            var bal = typeof __read_number === 'function' ? __read_number($('#in_balance_due')) : parseFloat(document.querySelector('#in_balance_due').value || 0);
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
                } else {
                    if (statusBadge) statusBadge.textContent = data.message || 'Error initiating STK';
                }
            } catch (err) {
                console.error(err);
                if (statusBadge) statusBadge.textContent = 'Error initiating STK';
            } finally {
                sendBtn.disabled = false;
            }
        });

        $(document).on('click', '.check-mpesa-status', async function(e){
            e.preventDefault();
            const row = $(this).closest('.payment_row')[0];
            if (!row) return;
            const checkoutInput = row.querySelector('.checkout_request_id');
            const phoneInput = row.querySelector('.mpesa-phone');
            const statusBadge = row.querySelector('.mpesa-status-badge');
            const statusInput = row.querySelector('.mpesa_status');
            const receiptInput = row.querySelector('.mpesa_receipt_number');

            const checkout = checkoutInput ? checkoutInput.value : '';
            const phone = phoneInput ? phoneInput.value.trim() : '';
            let normalizedPhone = phone.replace(/^\+/, '');
            if (/^0?7\d{8}$/.test(normalizedPhone)) {
                normalizedPhone = normalizedPhone.replace(/^0/, '254');
            }
            if (!checkout && !normalizedPhone) {
                alert('Provide phone or checkout id to check status');
                return;
            }

            try {
                $(this).prop('disabled', true);
                if (statusBadge) statusBadge.textContent = 'Checking...';

                const res = await fetch("{{ route('mpesa.queryStatus') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ checkout_request_id: checkout, phone: normalizedPhone })
                });

                const data = await res.json();
                if (data.success && (data.transaction_status === 'success' || data.transaction_status === 'paid')) {
                    // Only mark as paid if the query returned a MPESA receipt number. Otherwise treat as pending.
                    const mpesaReceipt = data.mpesa_receipt_number || data.receipt_number || '';
                    if (mpesaReceipt) {
                        if (statusInput) statusInput.value = 'paid';
                        if (receiptInput) receiptInput.value = mpesaReceipt;
                        if (statusBadge) statusBadge.textContent = 'Paid';
                    } else {
                        if (statusInput) statusInput.value = 'pending';
                        if (statusBadge) statusBadge.textContent = 'Pending';
                    }
                    try {
                        if (typeof calculate_balance_due === 'function') {
                            calculate_balance_due();
                            var bal = typeof __read_number === 'function' ? __read_number($('#in_balance_due')) : parseFloat(document.querySelector('#in_balance_due').value || 0);
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
                    if (statusInput) statusInput.value = 'pending';
                    if (statusBadge) statusBadge.textContent = 'Pending';
                } else {
                    if (statusBadge) statusBadge.textContent = data.message || (data.status || 'Not found');
                }
            } catch (err) {
                console.error(err);
                if (statusBadge) statusBadge.textContent = 'Error checking status';
            } finally {
                $(this).prop('disabled', false);
            }
        });

        $(document).on('click', '.mpesa-view-log', async function(e){
            e.preventDefault();
            const row = $(this).closest('.payment_row')[0];
            const checkoutInput = row ? row.querySelector('.checkout_request_id') : null;
            const phoneInput = row ? row.querySelector('.mpesa-phone') : null;
            const checkout = checkoutInput ? checkoutInput.value : '';
            const phone = phoneInput ? phoneInput.value.trim() : '';
            let normalizedPhone = phone.replace(/^\+/, '');
            if (/^0?7\d{8}$/.test(normalizedPhone)) {
                normalizedPhone = normalizedPhone.replace(/^0/, '254');
            }

            const modal = document.getElementById('mpesa_log_modal');
            const body = modal.querySelector('.mpesa-log-body');
            body.innerHTML = '<div class="text-center">@lang("messages.loading")</div>';
            $('#mpesa_log_modal').modal('show');

            try {
                const url = new URL("{{ route('mpesa.logs') }}", window.location.origin);
                if (checkout) url.searchParams.set('checkout_request_id', checkout);
                else if (normalizedPhone) url.searchParams.set('phone', normalizedPhone);

                const res = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                if (data.success) {
                    if (!data.payments || data.payments.length === 0) {
                        body.innerHTML = '<div class="text-center">No logs found</div>';
                    } else {
                        let html = '<table class="table table-sm"><thead><tr><th>@lang("payment.status")</th><th>@lang("payment.receipt")</th><th>@lang("messages.date")</th></tr></thead><tbody>';
                        data.payments.forEach(p => {
                            html += `<tr><td>${p.transaction_status}</td><td>${p.mpesa_receipt_number || 'N/A'}</td><td>${p.created_at}</td></tr>`;
                        });
                        html += '</tbody></table>';
                        body.innerHTML = html;
                    }
                } else {
                    body.innerHTML = '<div class="text-danger">' + (data.message || 'Error') + '</div>';
                }
            } catch (err) {
                console.error(err);
                body.innerHTML = '<div class="text-danger">Error fetching logs</div>';
            }
        });
    })(jQuery);
}
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
                                {!! Form::select('', ['visa' => 'Visa', 'master' => 'MasterCard'], 'visa', [
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
