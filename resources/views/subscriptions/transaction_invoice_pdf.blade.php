<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $tx->invoice_no ?? ('INV-' . $tx->id) }}</title>
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
    $currency = $settings->invoice_currency ?? ($tx->currency ?? 'KES');
    $invoiceNo = $tx->invoice_no ?? ('INV-' . $tx->id);
    $invoiceDate = $tx->transaction_date ? \Carbon\Carbon::parse($tx->transaction_date)->format('m/d/Y') : now()->format('m/d/Y');
    $dueDate = $tx->due_date ? \Carbon\Carbon::parse($tx->due_date)->format('m/d/Y') : '';
    $paymentDate = $tx->paid_on ? \Carbon\Carbon::parse($tx->paid_on)->format('m/d/Y') : '';

    $companyName = $settings->company_name ?? optional($tx->business)->name ?? config('app.name');
    $address1 = $settings->company_address_line1 ?? '';
    $address2 = $settings->company_address_line2 ?? '';
    $country = $settings->company_country ?? 'Kenya';

    $subtotal = $tx->total_before_tax ?? ($tx->final_total - ($tx->tax_amount ?? 0));
    $taxRate = isset($tx->tax_percentage) ? ($tx->tax_percentage . '%') : (($settings->subscription_vat_percent ?? 0) . '%');
    $taxAmount = $tx->tax_amount ?? 0;
    $totalDue = $tx->final_total ?? ($subtotal + $taxAmount);
    // Compute amount paid from transaction payments to be accurate
    $amountPaid = \App\TransactionPayment::where('transaction_id', $tx->id)->sum('amount');
    $primaryPayment = \App\TransactionPayment::where('transaction_id', $tx->id)->orderBy('paid_on', 'desc')->first();
    $paymentMethodRaw = $primaryPayment->method ?? ($tx->payment_method ?? null);
    $computedMethod = $paymentMethodRaw ? ucfirst(strtolower($paymentMethodRaw)) : null;
    // Try to resolve receipt number from Mpesa payments, falling back to ref_no
    // Try receipt from MpesaPayment using consumed_by_transaction_id and mpesa_receipt_number
    $receiptNo = \App\MpesaPayment::where('consumed_by_transaction_id', $tx->id)->orderBy('created_at', 'desc')->value('mpesa_receipt_number');
    if (empty($receiptNo) && !empty($tx->ref_no)) {
        $receiptNo = \App\MpesaPayment::where('account_reference', $tx->ref_no)->orderBy('created_at', 'desc')->value('mpesa_receipt_number');
    }
    $balance = max(0, round($totalDue - $amountPaid, 2));
    $paidInFull = ($balance <= 0.0001);

    // Try to fetch subscription for better plan display
    $subForTx = null;
    if (!empty($tx->subscription_no) && strpos($tx->subscription_no, 'sub_invoice_') === 0) {
        try {
            $parts = explode('_', $tx->subscription_no);
            if (isset($parts[2]) && is_numeric($parts[2])) {
                $subForTx = \App\Subscription::find((int)$parts[2]);
            }
        } catch (\Exception $e) { $subForTx = null; }
    }
    // Build ATTN preferring subscription user's full name over system contact/email
    $attention = '';
    if ($subForTx && ($subForTx->user ?? null)) {
        $full = trim(($subForTx->user->surname ?? '') . ' ' . ($subForTx->user->first_name ?? '') . ' ' . ($subForTx->user->last_name ?? ''));
        if (!empty($full)) {
            $attention = $full;
        } else {
            $attention = $subForTx->user->name ?? ($subForTx->user->username ?? '');
        }
    }
    if (empty($attention)) {
        $attention = optional($tx->contact)->name ?? (optional($tx->contact)->supplier_business_name ?? '');
    }
    $planStatus = $subForTx ? ucfirst($subForTx->status ?? '') : '';
    $planName = $subForTx ? ($subForTx->plan_name ?? '') : '';
    $items = [
        [
            'description' => $tx->subscription_no ? ('Subscription Invoice ' . $tx->subscription_no) : ($tx->invoice_no ? ('Invoice ' . $tx->invoice_no) : 'Invoice'),
            'duration' => trim(($planStatus ? $planStatus . ' ' : '') . 'Plan: ' . ($planName ?: '-')),
            'amount' => number_format($subtotal, 2)
        ]
    ];

    $paymentInfo = [
        'transaction_date' => $paymentDate,
        'payment_method' => $computedMethod ?? 'Mpesa',
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
        <div>ATTN: {{ $attention }}</div>
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
