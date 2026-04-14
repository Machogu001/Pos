<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    @php
        $businessName = data_get(session('business'), 'name', config('app.name'));
        if (is_array($businessName)) {
            $businessName = data_get($businessName, 'name', config('app.name'));
        }
        $currencySymbol = data_get(session('currency'), 'symbol', session('currency', 'KSh'));
        if (is_array($currencySymbol)) {
            $currencySymbol = data_get($currencySymbol, 'symbol', 'KSh');
        }
    @endphp
    <title>{{ $businessName }} - {{ $stocktake->reference_no }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111827;
            margin: 24px;
        }
        .header {
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid #d1d5db;
        }
        .title {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 4px;
        }
        .subtitle {
            color: #475569;
            font-size: 12px;
        }
        .meta-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .meta-grid td {
            width: 50%;
            vertical-align: top;
            padding: 6px 0;
        }
        .meta-label {
            display: block;
            color: #64748b;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 2px;
        }
        .meta-value {
            font-size: 11px;
            font-weight: 600;
        }
        .section-title {
            font-size: 13px;
            font-weight: 700;
            margin: 18px 0 8px;
            color: #0f172a;
        }
        table.items {
            width: 100%;
            border-collapse: collapse;
        }
        table.items th,
        table.items td {
            border: 1px solid #cbd5e1;
            padding: 6px 7px;
            vertical-align: top;
        }
        table.items th {
            background: #e2e8f0;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .right { text-align: right; }
        .center { text-align: center; }
        .badge-negative { color: #991b1b; }
        .badge-positive { color: #166534; }
        .badge-neutral { color: #475569; }
        .notes {
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ $businessName }} - @lang('stocktake.stocktake') - {{ $stocktake->reference_no }}</div>
        <div class="subtitle">{{ is_array($stocktake->location->name) ? data_get($stocktake->location->name, 0, 'N/A') : $stocktake->location->name }}</div>
    </div>

    <table class="meta-grid">
        <tr>
            <td>
                <span class="meta-label">@lang('business.business_location')</span>
                <span class="meta-value">{{ is_array($stocktake->location->name) ? data_get($stocktake->location->name, 0, 'N/A') : $stocktake->location->name }}</span>
            </td>
            <td>
                <span class="meta-label">@lang('stocktake.started_at')</span>
                <span class="meta-value">{{ \Carbon\Carbon::parse($stocktake->transaction_date)->format(session('business.date_format', 'm/d/Y') . ' H:i') }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="meta-label">@lang('stocktake.completed_at')</span>
                <span class="meta-value">{{ $stocktake->completed_at ? \Carbon\Carbon::parse($stocktake->completed_at)->format(session('business.date_format', 'm/d/Y') . ' H:i') : __('stocktake.not_completed') }}</span>
            </td>
            <td>
                <span class="meta-label">@lang('stocktake.status')</span>
                <span class="meta-value">{{ ucfirst(str_replace('_', ' ', $stocktake->status)) }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="meta-label">@lang('stocktake.created_by')</span>
                <span class="meta-value">{{ $stocktake->createdBy?->user_full_name ?? $stocktake->createdBy?->username ?? 'System' }}</span>
            </td>
            <td>
                <span class="meta-label">@lang('stocktake.additional_notes')</span>
                <span class="meta-value">{{ is_array($stocktake->additional_notes) ? json_encode($stocktake->additional_notes) : ($stocktake->additional_notes ?: '—') }}</span>
            </td>
        </tr>
    </table>

    <div class="section-title">@lang('stocktake.stocktake_items') ({{ $rows->count() }})</div>

    <table class="items">
        <thead>
            <tr>
                <th>@lang('stocktake.product_name')</th>
                <th>@lang('product.sku')</th>
                <th class="center">@lang('stocktake.system_quantity')</th>
                <th class="center">@lang('stocktake.counted_quantity')</th>
                <th class="center">@lang('stocktake.variance')</th>
                <th class="center">@lang('stocktake.variance') %</th>
                <th class="right">@lang('stocktake.value_variance')</th>
                <th>@lang('stocktake.notes')</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                @php
                    $varianceClass = $row['variance'] < 0 ? 'badge-negative' : ($row['variance'] > 0 ? 'badge-positive' : 'badge-neutral');
                @endphp
                <tr>
                    <td>{{ is_array($row['product_name']) ? json_encode($row['product_name']) : $row['product_name'] }}</td>
                    <td>{{ is_array($row['sku']) ? json_encode($row['sku']) : $row['sku'] }}</td>
                    <td class="center">{{ number_format($row['system_quantity'], 4) }}</td>
                    <td class="center">{{ number_format($row['counted_quantity'], 4) }}</td>
                    <td class="center {{ $varianceClass }}">{{ number_format($row['variance'], 4) }}</td>
                    <td class="center {{ $varianceClass }}">{{ number_format($row['variance_percent'], 2) }}%</td>
                    <td class="right">{{ $currencySymbol }}{{ number_format($row['value_variance_purchase'], 2) }}</td>
                    <td class="notes">{{ is_array($row['notes']) ? json_encode($row['notes']) : ($row['notes'] ?: '—') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>