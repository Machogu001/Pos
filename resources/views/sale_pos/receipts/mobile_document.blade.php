<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta http-equiv="Content-Security-Policy" content="default-src 'none'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; script-src 'none'; connect-src 'none'; frame-src 'none'; base-uri 'none'; form-action 'none'">
    <title>Receipt</title>
    <link rel="stylesheet" href="{{ asset('css/vendor.css?v='.$asset_v) }}">
    <link rel="stylesheet" href="{{ asset('css/app.css?v='.$asset_v) }}">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            width: 100%;
            max-width: 100vw;
            overflow-x: hidden;
            -webkit-text-size-adjust: 100%;
        }
        body {
            font-size: 12px;
            line-height: 1.35;
        }
        #invoice_content {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            padding: 8px;
            box-sizing: border-box;
            overflow-x: auto;
            overflow-y: hidden;
        }
        #invoice_content, #invoice_content * {
            box-sizing: border-box;
            max-width: 100%;
        }
        #invoice_content .row {
            margin-left: 0 !important;
            margin-right: 0 !important;
        }
        #invoice_content [class*="col-"] {
            min-width: 0 !important;
            padding-left: 4px !important;
            padding-right: 4px !important;
        }
        #invoice_content table {
            width: 100% !important;
            max-width: 100% !important;
            table-layout: fixed;
            border-collapse: collapse;
            display: block;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        #invoice_content th,
        #invoice_content td {
            white-space: normal !important;
            word-break: break-word !important;
            overflow-wrap: anywhere !important;
            font-size: 10px !important;
            line-height: 1.3 !important;
            padding: 3px 4px !important;
            vertical-align: top;
            min-width: 0;
        }
        #invoice_content th {
            font-weight: 700;
        }
        #invoice_content p,
        #invoice_content span,
        #invoice_content div,
        #invoice_content strong,
        #invoice_content small {
            max-width: 100%;
            word-break: break-word;
            overflow-wrap: anywhere;
        }
        #invoice_content img {
            max-width: 100% !important;
            height: auto !important;
            display: block;
        }
        .no-print { display: none !important; }
    </style>
</head>
<body>
    <div id="invoice_content">{!! $receipt_html !!}</div>
</body>
</html>
