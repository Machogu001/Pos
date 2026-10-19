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
        html, body { margin: 0; padding: 0; background: #fff; color: #000; width: 100%; overflow-x: hidden; }
        #invoice_content {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            padding: 8px;
            box-sizing: border-box;
            overflow: hidden;
        }
        #invoice_content, #invoice_content * {
            box-sizing: border-box;
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
        }
        #invoice_content th,
        #invoice_content td {
            white-space: normal !important;
            word-break: break-word !important;
            overflow-wrap: anywhere !important;
        }
        #invoice_content img {
            max-width: 100% !important;
            height: auto !important;
        }
        .no-print { display: none !important; }
    </style>
</head>
<body>
    <div id="invoice_content">{!! $receipt_html !!}</div>
</body>
</html>
