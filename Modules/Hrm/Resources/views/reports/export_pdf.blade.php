<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        .header { margin-bottom: 18px; }
        .title { font-size: 20px; font-weight: bold; margin-bottom: 4px; }
        .subtitle { color: #555; margin-bottom: 10px; }
        .filters { margin-bottom: 14px; }
        .filters span { display: inline-block; margin-right: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d9d9d9; padding: 6px 8px; }
        th { background: #f1f5f9; text-align: left; }
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