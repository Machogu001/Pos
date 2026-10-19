<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta id="meta-viewport" name="viewport" content="width=1024">
    <meta http-equiv="Content-Security-Policy" content="default-src 'none'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; script-src 'none'; connect-src 'none'; frame-src 'none'; base-uri 'none'; form-action 'none'">
    <title>Invoice</title>
    @include('layouts.partials.css')
    <style>
        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            width: 100%;
            overflow-x: auto;
        }
        #invoice_content {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            padding: 12px;
            box-sizing: border-box;
        }
        #invoice_content, #invoice_content * {
            box-sizing: border-box;
        }
        #invoice_content img {
            max-width: 100% !important;
            height: auto !important;
        }
        .no-print {
            display: none !important;
        }
    </style>
</head>
<body>
    <div id="invoice_content">{!! $receipt_html !!}</div>
</body>
</html>