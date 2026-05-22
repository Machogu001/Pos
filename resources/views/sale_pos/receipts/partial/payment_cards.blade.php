@php
    $receipt_payment_rows = !empty($receipt_details->payments) ? $receipt_details->payments : [];
    $mpesa_logo_file = public_path('img/mpesa-logo.svg');
    $mpesa_logo_src = file_exists($mpesa_logo_file)
        ? 'data:image/svg+xml;base64,' . base64_encode(file_get_contents($mpesa_logo_file))
        : asset('img/mpesa-logo.svg');
@endphp

@if(!empty($receipt_payment_rows))
    <style>
        .pay-card-wrap {
            width: 100%;
            margin: 6px 0;
        }
        .pay-card {
            border: 1px solid #222;
            border-radius: 8px;
            padding: 6px;
            margin-bottom: 6px;
            display: flex;
            gap: 8px;
            break-inside: avoid;
        }
        .pay-card-left {
            width: 30%;
            min-width: 70px;
            border-right: 1px solid #222;
            text-align: center;
            padding-right: 6px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 4px;
        }
        .pay-card-label {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            line-height: 1.1;
        }
        .pay-card-logo {
            height: 11px;
            width: auto;
            max-width: 44px;
        }
        .pay-card-method {
            font-size: 11px;
            font-weight: 700;
            line-height: 1.1;
            word-break: break-word;
        }
        .pay-card-right {
            flex: 1;
            min-width: 0;
        }
        .pay-card-row {
            display: grid;
            grid-template-columns: 108px minmax(0, 1fr);
            align-items: baseline;
            gap: 6px;
            font-size: 11px;
            margin-bottom: 3px;
            line-height: 1.2;
        }
        .pay-card-row:last-child {
            margin-bottom: 0;
        }
        .pay-card-key {
            font-weight: 700;
            white-space: nowrap;
        }
        .pay-card-val {
            font-weight: 600;
            word-break: break-word;
            text-align: left;
        }
        @media (max-width: 420px) {
            .pay-card-row {
                grid-template-columns: 92px minmax(0, 1fr);
                font-size: 10px;
                gap: 4px;
            }
            .pay-card-label {
                font-size: 9px;
            }
            .pay-card-logo {
                height: 10px;
            }
        }
    </style>

    <div class="pay-card-wrap">
        @foreach($receipt_payment_rows as $payment)
            @php
                $method_raw = $payment['method'] ?? '';
                $method_label = $method_raw;
                $transaction_code = '';

                if (stripos($method_raw, 'MPESA:') !== false) {
                    $parts = explode(', MPESA:', $method_raw, 2);
                    $method_label = trim($parts[0]);
                    $transaction_code = trim($parts[1] ?? '');
                }

                $is_mpesa = strpos(strtolower($method_label), 'mpesa') !== false;

                if (empty($transaction_code) && !empty($payment['transaction_no'])) {
                    $transaction_code = $payment['transaction_no'];
                }

                $paid_on = $payment['date_time'] ?? ($payment['date'] ?? __('payment.na'));
                $amount_paid = $payment['amount'] ?? __('payment.na');
                $display_method = !empty($method_label) ? $method_label : __('payment.payment');
            @endphp

            <div class="pay-card">
                <div class="pay-card-left">
                        <div class="pay-card-label">{{ __('payment.paid_via') }}</div>
                    @if($is_mpesa)
                        <img src="{{ $mpesa_logo_src }}" alt="M-Pesa" class="pay-card-logo">
                    @else
                        <div class="pay-card-method">{{ $display_method }}</div>
                    @endif
                </div>
                <div class="pay-card-right">
                    <div class="pay-card-row">
                        <span class="pay-card-key">{{ __('payment.transaction_code') }}</span>
                        <span class="pay-card-val">{{ !empty($transaction_code) ? $transaction_code : __('payment.na') }}</span>
                    </div>
                    <div class="pay-card-row">
                        <span class="pay-card-key">{{ __('payment.amount_paid') }}</span>
                        <span class="pay-card-val">{{ $amount_paid }}</span>
                    </div>
                    <div class="pay-card-row">
                        <span class="pay-card-key">{{ __('payment.paid_on') }}</span>
                        <span class="pay-card-val">{{ $paid_on }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
