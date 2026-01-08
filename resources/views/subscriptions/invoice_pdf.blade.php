<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Subscription Invoice</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        <!doctype html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Invoice {{ $subscription->id }}</title>
            <style>
                body { font-family: DejaVu Sans, Arial, sans-serif; line-height: 1.45; color: #333; }
                .container { width: 100%; max-width: 800px; margin: 0 auto; }
                .invoice-header { text-align: center; margin: 0 0 16px; }
                .invoice-title { font-size: 22px; font-weight: bold; margin: 0 0 6px; }
                .section-title { font-weight: bold; font-size: 16px; margin: 0 0 8px; border-bottom: 2px solid #333; padding-bottom: 4px; }
                table { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 0 0 12px; }
                th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; word-wrap: break-word; }
                th { background-color: #f5f5f5; font-weight: bold; }
                .text-right { text-align: right; }
                .text-bold { font-weight: bold; }
                .totals-wrap { width: 100%; margin-top: 8px; }
                .totals-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
                .totals-table td { border: none; padding: 6px 8px; }
                .totals-table .label { width: 70%; }
                .totals-table .value { width: 30%; text-align: right; }
                .totals-table .total-row td { border-top: 2px solid #333; font-weight: bold; }
                .paid { color: #0a7a0a; font-weight: bold; text-align: center; margin: 14px 0; }
                .footer-note { margin-top: 14px; font-style: italic; text-align: center; font-size: 13px; color: #666; }
            </style>
        </head>
        <body>
        @php
            // Helpers to safely format dates that might be strings
            $toCarbon = function($dt) {
                if ($dt instanceof \Carbon\Carbon || $dt instanceof \DateTimeInterface) return $dt;
                if (empty($dt)) return null;
                try { return \Carbon\Carbon::parse($dt); } catch (\Exception $e) { return null; }
            };
            $fmt = function($dt, $format = 'm/d/Y') use ($toCarbon) {
                $c = $toCarbon($dt);
                return $c ? $c->format($format) : '';
            };

            // Resolve transaction and display fields
            $tx = null;
            try {
                $endDateYmd = $fmt($subscription->end_date ?? null, 'Ymd') ?: now()->format('Ymd');
                $uniqueKey = 'sub_invoice_' . $subscription->id . '_' . $endDateYmd;
                $tx = \App\Transaction::where('subscription_no', $uniqueKey)->first();
                if (! $tx && !empty($subscription->pending_invoice_transaction_id)) {
                    $tx = \App\Transaction::find($subscription->pending_invoice_transaction_id);
                }
            } catch (\Exception $e) { $tx = null; }

            $currency = $settings->invoice_currency ?? 'KES';
            $invoiceNo = $tx->invoice_no ?? ('INV-' . $subscription->id);
            $invoiceDate = $fmt($tx->transaction_date ?? null) ?: now()->format('m/d/Y');
            $dueDate = $fmt($tx->due_date ?? ($subscription->end_date ?? null));
            $paymentDate = $fmt($tx->paid_on ?? null);

            $companyName = $settings->company_name ?? optional($subscription->user->business)->name ?? config('app.name');
            $attention = $subscription->user->name ?? $subscription->user->email ?? '';
            $address1 = $settings->company_address_line1 ?? '';
            $address2 = $settings->company_address_line2 ?? '';
            $country = $settings->company_country ?? 'Kenya';

            // Amounts
            $subtotal = $tx ? ($tx->total_before_tax ?? 0) : ($subscription->amount ?? 0);
            $taxRate = $tx && isset($tx->tax_percentage) ? ($tx->tax_percentage . '%') : (($settings->subscription_vat_percent ?? 0) . '%');
            $taxAmount = $tx ? ($tx->tax_amount ?? 0) : round(($subtotal * (floatval($settings->subscription_vat_percent ?? 0)/100)), 2);
            $totalDue = $tx ? ($tx->final_total ?? ($subtotal + $taxAmount)) : ($subtotal + $taxAmount);
            // Compute amount paid using transaction payments if tx exists
            if ($tx) {
                $amountPaid = \App\TransactionPayment::where('transaction_id', $tx->id)->sum('amount');
                $primaryPayment = \App\TransactionPayment::where('transaction_id', $tx->id)->orderBy('paid_on', 'desc')->first();
                $paymentMethodRaw = $primaryPayment->method ?? ($tx->payment_method ?? null);
                $paymentMethod = $paymentMethodRaw ? ucfirst(strtolower($paymentMethodRaw)) : null;
                $receiptNo = \App\MpesaPayment::where('consumed_by_transaction_id', $tx->id)->orderBy('created_at', 'desc')->value('mpesa_receipt_number');
                if (empty($receiptNo) && !empty($tx->ref_no)) {
                    $receiptNo = \App\MpesaPayment::where('account_reference', $tx->ref_no)->orderBy('created_at', 'desc')->value('mpesa_receipt_number');
                }
            } else {
                $amountPaid = 0;
                $paymentMethod = null;
                $receiptNo = null;
            }
            $balance = max(0, round($totalDue - $amountPaid, 2));
            $paidInFull = ($balance <= 0.0001);

            // Items
            $planStatus = ucfirst($subscription->status ?? '');
            $planName = $subscription->plan_name ?? '';
            $items = [
                [
                    'description' => ($subscription->plan_name ?? 'Subscription Plan') . ' (' . ($subscription->id) . ')',
                    'duration' => trim(($planStatus ? $planStatus . ' ' : '') . 'Plan: ' . ($planName ?: '-')),
                    'amount' => number_format($subtotal, 2)
                ]
            ];

            // Payment info
            $paymentInfo = [
                'transaction_date' => $paymentDate,
                'payment_method' => $paymentMethod ?? 'Mpesa',
                'transaction_id' => $receiptNo ?? ($tx->ref_no ?? ($tx->subscription_no ?? '')),
                'payment_amount' => $amountPaid ? number_format($amountPaid, 2) : ''
            ];
        @endphp

        <div class="container">
            <div class="invoice-header">
                <div class="invoice-title">INVOICE</div>
                <div>Invoice #: {{ $invoiceNo }} | Invoice Date: {{ $invoiceDate }}</div>
                <div>Due Date: {{ $dueDate }} | Payment Date: {{ $paymentDate }}</div>
            </div>

            <div class="bill-to">
                <div class="section-title">Bill To:</div>
                <div>{{ $companyName }}</div>
                @php
                    $attnName = trim(($subscription->user->surname ?? '') . ' ' . ($subscription->user->first_name ?? '') . ' ' . ($subscription->user->last_name ?? ''));
                    if (empty(trim($attnName))) {
                        $attnName = $subscription->user->name ?? ($subscription->user->username ?? '');
                    }
                    if (empty(trim($attnName))) {
                        $attnName = $attention; // last resort
                    }
                @endphp
                <div>ATTN: {{ trim($attnName) }}</div>
                <div>{{ $address1 }}</div>
                <div>{{ $address2 }}</div>
                <div>{{ $country }}</div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Duration</th>
                        <th class="text-right">Amount ({{ $currency }})</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>{{ $item['description'] }}</td>
                        <td>{{ $item['duration'] }}</td>
                        <td class="text-right">{{ $item['amount'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="totals-wrap">
                <table class="totals-table">
                    <tr>
                        <td class="label">Subtotal:</td>
                        <td class="value">{{ $currency }} {{ number_format($subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="label">Tax ({{ $taxRate }}):</td>
                        <td class="value">{{ $currency }} {{ number_format($taxAmount, 2) }}</td>
                    </tr>
                    <tr class="total-row">
                        <td class="label">Total Amount Due:</td>
                        <td class="value">{{ $currency }} {{ number_format($totalDue, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="label">Amount Paid:</td>
                        <td class="value">{{ $currency }} {{ number_format($amountPaid, 2) }}</td>
                    </tr>
                    <tr class="total-row">
                        <td class="label">BALANCE:</td>
                        <td class="value">{{ $currency }} {{ number_format($balance, 2) }}</td>
                    </tr>
                </table>
            </div>

            <div class="payment-info clearfix" style="margin-top: 30px;">
                <div class="section-title">Payment Information</div>
                <table>
                    <thead>
                        <tr>
                            <th>Transaction Date</th>
                            <th>Payment Method</th>
                            <th>Transaction ID / Code</th>
                            <th class="text-right">Amount ({{ $currency }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $paymentInfo['transaction_date'] }}</td>
                            <td>{{ $paymentInfo['payment_method'] }}</td>
                            <td class="text-bold">{{ $paymentInfo['transaction_id'] }}</td>
                            <td class="text-right text-bold">{{ $currency }} {{ $paymentInfo['payment_amount'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            @if($paidInFull)
                <div class="paid">PAID IN FULL</div>
            @endif

            <div class="footer-note">
                {{ $settings->invoice_footer ?? 'Thank you for your business. This is a computer-generated invoice and does not require a physical signature' }}
            </div>
        </div>

        </body>
        </html>
