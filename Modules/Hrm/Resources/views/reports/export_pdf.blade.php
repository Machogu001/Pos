<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11.5px; color: #2c2a25; }
        .header {
            margin-bottom: 16px;
            padding: 10px 12px;
            border: 1px solid #d9c9a6;
            border-radius: 8px;
            background: #f8f2e4;
        }
        .title { font-size: 19px; font-weight: bold; margin-bottom: 4px; color: #213d57; }
        .subtitle { color: #5f5543; margin-bottom: 8px; }
        .filters { margin-bottom: 2px; }
        .filters span {
            display: inline-block;
            margin-right: 12px;
            margin-bottom: 4px;
            padding: 2px 6px;
            background: #efe3c9;
            border: 1px solid #ddcca7;
            border-radius: 4px;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d4c6a8; padding: 6px 8px; }
        th {
            background: #e9dcc0;
            color: #2f271a;
            text-align: left;
            font-weight: bold;
        }
        tbody tr:nth-child(even) { background: #fbf8f1; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ $title }}</div>
        <div class="subtitle">{{ $subtitle }}</div>
        @if(!empty($filters))
            <div class="filters">
                @foreach($filters as $label => $value)
                    <span><strong>{{ $label }}:</strong> {{ $value }}</span>
                @endforeach
            </div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                @foreach($headings as $heading)
                    <th>{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headings) }}">No records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>