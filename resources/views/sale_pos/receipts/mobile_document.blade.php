<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Security-Policy" content="default-src 'none'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; script-src 'none'; connect-src 'none'; frame-src 'none'; base-uri 'none'; form-action 'none'">
    <title>Receipt</title>
    <link rel="stylesheet" href="{{ asset('css/vendor.css?v='.$asset_v) }}">
    <link rel="stylesheet" href="{{ asset('css/app.css?v='.$asset_v) }}">
    <style>
        html, body { margin: 0; padding: 0; background: #fff; color: #000; }
        #invoice_content { width: 100%; }
        .no-print { display: none !important; }
    </style>
</head>
<body>
    <div id="invoice_content">{!! $receipt_html !!}</div>
</body>
</html>
