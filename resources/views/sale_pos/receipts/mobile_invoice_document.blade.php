<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta id="meta-viewport" name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=5, user-scalable=yes, viewport-fit=cover">
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
            max-width: 100vw;
            overflow-x: hidden;
            -webkit-text-size-adjust: 100%;
        }
        #invoice_content {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            padding: 12px;
            box-sizing: border-box;
            overflow-x: auto;
            overflow-y: hidden;
        }
        #invoice_content, #invoice_content * {
            box-sizing: border-box;
            max-width: 100%;
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
        }
        .no-print {
            display: none !important;
        }
        @media (max-width: 600px) {
            #invoice_content .receipt-wrap {
                overflow-x: visible !important;
            }
            #invoice_content .receipt-line-items {
                display: table !important;
                width: 740px !important;
                min-width: 740px !important;
                max-width: none !important;
                table-layout: fixed !important;
            }
            #invoice_content .receipt-line-items th.col-bonus,
            #invoice_content .receipt-line-items td.col-bonus,
            #invoice_content .receipt-line-items th.col-lot,
            #invoice_content .receipt-line-items td.col-lot,
            #invoice_content .receipt-line-items th.col-unit-price,
            #invoice_content .receipt-line-items td.col-unit-price {
                white-space: nowrap !important;
                word-break: normal !important;
                overflow-wrap: normal !important;
            }
        }
    </style>
</head>
<body>
    <div id="invoice_content">{!! $receipt_html !!}</div>
</body>
</html>