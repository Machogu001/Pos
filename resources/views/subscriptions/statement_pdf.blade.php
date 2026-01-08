<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Subscription Statement</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; line-height: 1.45; color: #333; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { padding: 8px; border: 1px solid #ddd; word-wrap: break-word; }
        th { background-color: #f5f5f5; font-weight: bold; }
        .header { text-align: center; margin: 0 0 16px; }
    </style>
</head>
<body>
    <div class="header">
        <table style="width:100%;">
            <tr>
                <td style="width:50%; text-align:left;">
                    @php
                        $company_display = $settings->company_name ?? optional($subscription->user->business)->name ?? optional($subscription->user->business)->tax_number ?? config('app.name');
                    @endphp
                    @if(!empty($settings->company_logo))
                        <div style="display:flex; align-items:center; gap:12px;">
                            <img src="{{ public_path($settings->company_logo) }}" alt="logo" style="max-height:80px;" />
                            <div>
                                <h3 style="margin:0;">{{ $company_display }}</h3>
                            </div>
                        </div>
                    @else
                        <h3>{{ $company_display }}</h3>
                    @endif
                </td>
                <td style="width:50%; text-align:right; vertical-align:middle;">
                    <h2>Subscription Account Statement</h2>
                    @if(!empty($settings->company_contact_phone))<div>{{ $settings->company_contact_phone }}</div>@endif
                    @if(!empty($settings->company_contact_email))<div>{{ $settings->company_contact_email }}</div>@endif
                    @if(!empty($settings->invoice_pin))<div><small>Company PIN: {{ $settings->invoice_pin }}</small></div>@endif
                    @if(!empty($subscription->user->client_pin))<div><small>Client PIN: {{ $subscription->user->client_pin }}</small></div>@endif
                </td>
            </tr>
        </table>
    </div>

    @php
        $subscriber_name = trim(($subscription->user->surname ?? '') . ' ' . ($subscription->user->first_name ?? '') . ' ' . ($subscription->user->last_name ?? ''));
        if (empty($subscriber_name)) {
            $subscriber_name = $subscription->user->name ?? ($subscription->user->username ?? '');
        }
        $startAt = $start instanceof \Carbon\Carbon ? $start : \Carbon\Carbon::parse($start);
        $endAt = $end instanceof \Carbon\Carbon ? $end : \Carbon\Carbon::parse($end);
    @endphp
    <p><strong>Subscriber:</strong> {{ $subscriber_name }}</p>
    <p><strong>Period:</strong> {{ $startAt->format('d/m/Y') }} — {{ $endAt->format('d/m/Y') }}</p>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Reference</th>
                <th>Description</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
        @forelse($payments as $p)
            <tr>
                <td>{{ optional($p->created_at)->format('d/m/Y') }}</td>
                <td>{{ $p->id }}</td>
                <td>{{ $p->payment_type ?? 'Payment' }}</td>
                <td style="text-align:right;">{{ number_format($p->amount ?? 0, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4">No payments found for this period.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <h4 style="margin-top:20px;">Invoices</h4>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Invoice #</th>
                <th>Reference</th>
                <th>VAT</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
        @forelse($invoices ?? collect() as $inv)
            <tr>
                <td>{{ optional($inv->transaction_date)->format('d/m/Y') ?: optional($inv->created_at)->format('d/m/Y') }}</td>
                <td>{{ $inv->invoice_no ?? $inv->id }}</td>
                <td>{{ $inv->subscription_no ?? '' }}</td>
                <td style="text-align:right;">{{ number_format($inv->tax_amount ?? 0, 2) }}</td>
                <td style="text-align:right;">{{ number_format($inv->final_total ?? 0, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4">No invoices found for this period.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div style="margin-top:20px;">
        <small>Generated on {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</small>
    </div>

    @if(!empty($settings->statement_footer))
        <hr />
        <div style="margin-top:10px; font-size:0.9em; color:#333;">
            {!! nl2br(e($settings->statement_footer)) !!}
        </div>
    @endif

</body>
</html>
