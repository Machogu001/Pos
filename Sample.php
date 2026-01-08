<?php
/**
 * Invoice Template for 
 * 
 * @param array $invoiceData Invoice data array
 * @return string HTML/Text invoice
 */
function generateInvoice($invoiceData) {
    // Default data structure
    $defaultData = [
        'invoice_number' => 'INV-',
        'invoice_date' => '',
        'due_date' => '',
        'payment_date' => '',
        'company_name' => '',
        'attention' => '',
        'address_line1' => '',
        'address_line2' => '',
        'country' => 'Kenya',
        'items' => [
            [
                'description' => '',
                'duration' => '',
                'amount' => ''
            ]
        ],
        'subtotal' => '',
        'tax_rate' => '0%',
        'tax_amount' => '0.00',
        'total_due' => '',
        'amount_paid' => '',
        'balance' => '0.00',
        'payment_info' => [
            'transaction_date' => '',
            'payment_method' => '',
            'transaction_id' => '',
            'payment_amount' => ''
        ],
        'currency' => 'KES',
        'paid_in_full' => true,
        'footer_note' => 'Thank you for your business. This is a computer-generated invoice and does not require a physical signature'
    ];
    
    // Merge provided data with defaults
    $data = array_merge($defaultData, $invoiceData);
    
    // Calculate totals if not provided
    if (!isset($data['subtotal']) && isset($data['items'])) {
        $data['subtotal'] = 0;
        foreach ($data['items'] as $item) {
            $amount = str_replace(',', '', $item['amount']);
            $data['subtotal'] += (float)$amount;
        }
        $data['subtotal'] = number_format($data['subtotal'], 2);
    }
    
    // Generate the invoice
    $invoice = <<<INVOICE
> **INVOICE**
>
> Invoice #: {$data['invoice_number']} Invoice Date : {$data['invoice_date']}
>
> Due Date: {$data['due_date']} Payment Date: {$data['payment_date']}

# Bill To:

> {$data['company_name']}
>
> ATTN: {$data['attention']}
>
> {$data['address_line1']}
>
> {$data['address_line2']}
>
> {$data['country']}

-----------------------------------------------------------------------
**Description**                           **Duration**        **Amount
                                                                ({$data['currency']})**
----------------------------------------- ------------------- -----------
INVOICE;

    // Add items
    foreach ($data['items'] as $item) {
        $description = str_pad($item['description'], 40, ' ');
        $duration = str_pad($item['duration'], 18, ' ');
        $amount = str_pad($item['amount'], 10, ' ', STR_PAD_LEFT);
        $invoice .= "\n$description $duration {$data['currency']} $amount";
    }
    
    // Add totals
    $invoice .= <<<INVOICE

-----------------------------------------------------------------------

+----------------------------+----------------------+------------------+
|                            | > Subtotal:          | {$data['currency']} {$data['subtotal']}    |
|                            | >                    |                  |
|                            | > Tax ({$data['tax_rate']}):          | {$data['currency']} {$data['tax_amount']}         |
+============================+======================+==================+
| **Total Amount Due:**      |                      | **{$data['currency']}{$data['total_due']}** |
+----------------------------+----------------------+------------------+
|                            | > Amount Paid:       | {$data['currency']} {$data['amount_paid']}    |
+----------------------------+----------------------+------------------+
| **BALANCE:**               |                      | **{$data['currency']} {$data['balance']}**     |
+----------------------------+----------------------+------------------+

# Payment Information

-----------------------------------------------------------------------
 Transaction   Payment Method     Transaction ID / Code     Amount ({$data['currency']})
     Date                                                   
-------------- -------------- ----------------------------- -------------
 {$data['payment_info']['transaction_date']}       {$data['payment_info']['payment_method']}             **{$data['payment_info']['transaction_id']}**             **{$data['currency']}
                                                               {$data['payment_info']['payment_amount']}**

-----------------------------------------------------------------------
INVOICE;

    // Add paid status
    if ($data['']) {
        $invoice .= "\n\n**PAID IN FULL**\n\n";
    }
    
    // Add footer note
    $invoice .= $data['footer_note'];
    
    return $invoice;
}

/**
 * HTML Invoice Template (for web display/email)
 * 
 * @param array $invoiceData Invoice data array
 * @return string HTML invoice
 */
function generateHTMLInvoice($invoiceData) {
    // Default data (same as above)
    $defaultData = [
        'invoice_number' => 'INV-',
        'invoice_date' => '',
        'due_date' => '',
        'payment_date' => '',
        'company_name' => '',
        'attention' => '',
        'address_line1' => '',
        'address_line2' => '',
        'country' => 'Kenya',
        'items' => [
            [
                'description' => '',
                'duration' => ' - ',
                'amount' => ''
            ]
        ],
        'subtotal' => '',
        'tax_rate' => '0%',
        'tax_amount' => '0.00',
        'total_due' => '',
        'amount_paid' => '',
        'balance' => '0.00',
        'payment_info' => [
            'transaction_date' => '',
            'payment_method' => '',
            'transaction_id' => '',
            'payment_amount' => ''
        ],
        'currency' => 'KES',
        'paid_in_full' => true,
        'footer_note' => 'Thank you for your business. This is a computer-generated invoice and does not require a physical signature'
    ];
    
    // Merge provided data with defaults
    $data = array_merge($defaultData, $invoiceData);
    
    // Start HTML
    $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice ' . htmlspecialchars($data['invoice_number']) . '</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 800px; margin: 0 auto; padding: 20px; }
        .invoice-header { text-align: center; margin-bottom: 30px; }
        .invoice-title { font-size: 24px; font-weight: bold; margin-bottom: 10px; }
        .invoice-details { margin-bottom: 30px; }
        .bill-to { margin-bottom: 30px; }
        .section-title { font-weight: bold; font-size: 18px; margin-bottom: 10px; border-bottom: 2px solid #333; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f5f5f5; font-weight: bold; }
        .totals-table { width: 60%; float: right; }
        .totals-table td { border: none; padding: 5px 10px; }
        .totals-table .total-row { font-weight: bold; border-top: 2px solid #333; }
        .payment-info { margin-top: 30px; clear: both; }
        .paid { color: green; font-weight: bold; text-align: center; margin: 20px 0; }
        .footer-note { margin-top: 40px; font-style: italic; text-align: center; font-size: 14px; color: #666; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }
    </style>
</head>
<body>
    <div class="invoice-header">
        <div class="invoice-title">INVOICE</div>
        <div>Invoice #: ' . htmlspecialchars($data['invoice_number']) . ' | Invoice Date: ' . htmlspecialchars($data['invoice_date']) . '</div>
        <div>Due Date: ' . htmlspecialchars($data['due_date']) . ' | Payment Date: ' . htmlspecialchars($data['payment_date']) . '</div>
    </div>
    
    <div class="bill-to">
        <div class="section-title">Bill To:</div>
        <div>' . htmlspecialchars($data['company_name']) . '</div>
        <div>ATTN: ' . htmlspecialchars($data['attention']) . '</div>
        <div>' . htmlspecialchars($data['address_line1']) . '</div>
        <div>' . htmlspecialchars($data['address_line2']) . '</div>
        <div>' . htmlspecialchars($data['country']) . '</div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Duration</th>
                <th class="text-right">Amount (' . htmlspecialchars($data['currency']) . ')</th>
            </tr>
        </thead>
        <tbody>';
    
    // Add items
    foreach ($data['items'] as $item) {
        $html .= '
            <tr>
                <td>' . htmlspecialchars($item['description']) . '</td>
                <td>' . htmlspecialchars($item['duration']) . '</td>
                <td class="text-right">' . htmlspecialchars($item['amount']) . '</td>
            </tr>';
    }
    
    $html .= '
        </tbody>
    </table>
    
    <table class="totals-table">
        <tr>
            <td>Subtotal:</td>
            <td class="text-right">' . htmlspecialchars($data['currency']) . ' ' . htmlspecialchars($data['subtotal']) . '</td>
        </tr>
        <tr>
            <td>Tax (' . htmlspecialchars($data['tax_rate']) . '):</td>
            <td class="text-right">' . htmlspecialchars($data['currency']) . ' ' . htmlspecialchars($data['tax_amount']) . '</td>
        </tr>
        <tr class="total-row">
            <td>Total Amount Due:</td>
            <td class="text-right text-bold">' . htmlspecialchars($data['currency']) . ' ' . htmlspecialchars($data['total_due']) . '</td>
        </tr>
        <tr>
            <td>Amount Paid:</td>
            <td class="text-right">' . htmlspecialchars($data['currency']) . ' ' . htmlspecialchars($data['amount_paid']) . '</td>
        </tr>
        <tr class="total-row">
            <td>BALANCE:</td>
            <td class="text-right text-bold">' . htmlspecialchars($data['currency']) . ' ' . htmlspecialchars($data['balance']) . '</td>
        </tr>
    </table>
    
    <div class="payment-info">
        <div class="section-title">Payment Information</div>
        <table>
            <thead>
                <tr>
                    <th>Transaction Date</th>
                    <th>Payment Method</th>
                    <th>Transaction ID / Code</th>
                    <th class="text-right">Amount (' . htmlspecialchars($data['currency']) . ')</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>' . htmlspecialchars($data['payment_info']['transaction_date']) . '</td>
                    <td>' . htmlspecialchars($data['payment_info']['payment_method']) . '</td>
                    <td class="text-bold">' . htmlspecialchars($data['payment_info']['transaction_id']) . '</td>
                    <td class="text-right text-bold">' . htmlspecialchars($data['currency']) . ' ' . htmlspecialchars($data['payment_info']['payment_amount']) . '</td>
                </tr>
            </tbody>
        </table>
    </div>';
    
    // Add paid status
    if ($data['paid_in_full']) {
        $html .= '<div class="paid">PAID IN FULL</div>';
    }
    
    // Add footer note
    $html .= '<div class="footer-note">' . htmlspecialchars($data['footer_note']) . '</div>
</body>
</html>';
    
    return $html;
}

/**
 * Usage Example
 */
// Example 1: Generate text invoice
$invoiceData = [
    'invoice_number' => 'INV-',
    'invoice_date' => date('m/d/Y'),
    'due_date' => date('m/d/Y', strtotime('')),
    'company_name' => '',
    // ... other data
];

$textInvoice = generateInvoice($invoiceData);

// Example 2: Generate HTML invoice
$htmlInvoice = generateHTMLInvoice($invoiceData);

// Example 3: Save to file
file_put_contents('invoice_' . $invoiceData['invoice_number'] . '.txt', $textInvoice);
file_put_contents('invoice_' . $invoiceData['invoice_number'] . '.html', $htmlInvoice);

// Example 4: Display HTML invoice in browser
if (isset($_GET['view']) && $_GET['view'] == 'html') {
    echo $htmlInvoice;
} else {
    // Show text version or default
    echo '<pre>' . htmlspecialchars($textInvoice) . '</pre>';
}
?>