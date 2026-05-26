<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bank Reconciliation {{ $run->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111827; }
        h2 { margin: 0 0 8px; font-size: 18px; }
        .meta { margin-bottom: 14px; }
        .meta div { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; }
        th { background: #f3f4f6; font-weight: 700; }
        .num { text-align: right; }
    </style>
</head>
<body>
    <h2>Reconciliation Run #{{ $run->id }}</h2>
    <div class="meta">
        <div><strong>Status:</strong> {{ $run->status }}</div>
        <div><strong>Statement File:</strong> {{ $run->statement_filename }}</div>
        <div><strong>Date Range:</strong> {{ $run->start_date }} to {{ $run->end_date }}</div>
        <div><strong>Totals:</strong> Lines {{ $run->total_statement_lines }}, Matched {{ $run->matched_count }}, Ambiguous {{ $run->ambiguous_count }}, Unmatched {{ $run->unmatched_count }}, Invalid {{ $run->invalid_count }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Line</th>
                <th>Statement Date</th>
                <th>Statement Amount</th>
                <th>Description</th>
                <th>Reference</th>
                <th>Status</th>
                <th>Payment Ref</th>
                <th>Invoice/Ref</th>
                <th>Payment Date</th>
                <th>Payment Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row->line_no }}</td>
                    <td>{{ $row->statement_date }}</td>
                    <td class="num">{{ number_format((float) $row->statement_amount, 2) }}</td>
                    <td>{{ $row->description }}</td>
                    <td>{{ $row->reference }}</td>
                    <td>{{ $row->status }}</td>
                    <td>{{ $row->payment_ref_no }}</td>
                    <td>{{ $row->invoice_no ?: $row->ref_no }}</td>
                    <td>{{ $row->paid_on }}</td>
                    <td class="num">{{ number_format((float) ($row->payment_amount ?? 0), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
