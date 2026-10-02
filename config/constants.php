<?php

return [

    /*
    |--------------------------------------------------------------------------
    | App Constants
    |--------------------------------------------------------------------------
    |List of all constants for the app
    */

    'langs' => [
        'en' => ['full_name' => 'English', 'short_name' => 'English'],
        'es' => ['full_name' => 'Spanish - Español', 'short_name' => 'Spanish'],
        'sq' => ['full_name' => 'Albanian - Shqip', 'short_name' => 'Albanian'],
        'hi' => ['full_name' => 'Hindi - हिंदी', 'short_name' => 'Hindi'],
        'nl' => ['full_name' => 'Dutch', 'short_name' => 'Dutch'],
        'fr' => ['full_name' => 'French - Français', 'short_name' => 'French'],
        'de' => ['full_name' => 'German - Deutsch', 'short_name' => 'German'],
        'ar' => ['full_name' => 'Arabic - العَرَبِيَّة', 'short_name' => 'Arabic'],
        'tr' => ['full_name' => 'Turkish - Türkçe', 'short_name' => 'Turkish'],
        'id' => ['full_name' => 'Indonesian', 'short_name' => 'Indonesian'],
        'ps' => ['full_name' => 'Pashto', 'short_name' => 'Pashto'],
        'pt' => ['full_name' => 'Portuguese', 'short_name' => 'Portuguese'],
        'vi' => ['full_name' => 'Vietnamese', 'short_name' => 'Vietnamese'],
        'ce' => ['full_name' => 'Chinese', 'short_name' => 'Chinese'],
        'ro' => ['full_name' => 'Romanian', 'short_name' => 'Romanian'],
        'lo' => ['full_name' => 'Lao', 'short_name' => 'Lao'],
    ],
    'langs_rtl' => ['ar'],
    'non_utf8_languages' => ['ar', 'hi', 'ps'],

    'document_size_limit' => '5000000', //in Bytes,
    'image_size_limit' => '5000000', //in Bytes

    'asset_version' => 614,

    'disable_purchase_in_other_currency' => true,

    'iraqi_selling_price_adjustment' => false,

    //currency_precision & quantity_precision moved to business settings

    'product_img_path' => 'img',

    'enable_sell_in_diff_currency' => false,
    'currency_exchange_rate' => 1,
    'orders_refresh_interval' => 600, //Auto refresh interval on Kitchen and Orders page in seconds,

    'default_date_format' => 'd/m/Y', //Default date format to be used if session is not set. All valid formats can be found on https://www.php.net/manual/en/function.date.php

    'new_notification_count_interval' => 60, //Interval to check for new notifications in seconds;Default is 60sec

    'administrator_usernames' => env('ADMINISTRATOR_USERNAMES'),
    'allow_registration' => env('ALLOW_REGISTRATION', true),
    'app_title' => env('APP_TITLE'),

    'google_recaptcha_key' => env('GOOGLE_RECAPTCHA_KEY'),
    'google_recaptcha_secret' => env('GOOGLE_RECAPTCHA_SECRET'),
    'enable_recaptcha' => env('ENABLE_RECAPTCHA', false),
    
    'mpdf_temp_path' => storage_path('app/pdf'), //Temporary path used by mpdf

    'document_upload_mimes_types' => ['application/pdf' => '.pdf',
        'text/csv' => '.csv',
        'application/zip' => '.zip',
        'application/msword' => '.doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '.docx',
        'image/jpeg' => '.jpeg',
        'image/jpg' => '.jpg',
        'image/png' => '.png',

    ], //List of MIME type: https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types/Common_types
    'show_report_606' => false,
    'show_report_607' => false,
    'whatsapp_base_url' => 'https://wa.me',
    'enable_crm_call_log' => false,
    'enable_product_bulk_edit' => false,  //Will be depreciated in future
    'enable_convert_draft_to_invoice' => false, //Experimental beta feature.
    'enable_download_pdf' => false,         //Experimental feature
    'invoice_scheme_separator' => '-',
    'show_payments_recovered_today' => false, //Displays payment recovered today table on dashboard
    'enable_b2b_marketplace' => false,
    'enable_contact_assign' => true, //Used in add/edit contacts screen
    'show_payment_type_on_contact_pay' => false,
    'enable_gst_report_india' => env('ENABLE_GST_REPORT_INDIA', false),
    'enable_secondary_unit' => false, //Experimental feature, may depreciate
    'pwa_install_dismiss_days' => (int) env('PWA_INSTALL_DISMISS_DAYS', 15),

    // Default ledger / payment account mappings by transaction type.
    // These IDs should correspond to entries in the `accounts` table.
    'default_account_mappings' => [
        // Example (configure via .env or config override):
        // 'sell' => env('DEFAULT_SELL_ACCOUNT_ID'),
        // 'purchase' => env('DEFAULT_PURCHASE_ACCOUNT_ID'),
        // 'expense' => env('DEFAULT_EXPENSE_ACCOUNT_ID'),
        // 'payroll' => env('DEFAULT_PAYROLL_ACCOUNT_ID'),
        // 'payment' => env('DEFAULT_PAYMENT_ACCOUNT_ID'),
        // 'cogs' => env('DEFAULT_COGS_ACCOUNT_ID'),
        // 'inventory' => env('DEFAULT_INVENTORY_ACCOUNT_ID'),
        // 'tax' => env('DEFAULT_TAX_ACCOUNT_ID'),
        // 'purchase_tax' => env('DEFAULT_PURCHASE_TAX_ACCOUNT_ID'),
        // 'sales_tax' => env('DEFAULT_SALES_TAX_ACCOUNT_ID'),
        // 'accounts_receivable' => env('DEFAULT_ACCOUNTS_RECEIVABLE_ACCOUNT_ID'),
        // 'opening_stock_equity' => env('DEFAULT_OPENING_STOCK_EQUITY_ACCOUNT_ID'),
        // Leave null or unset to skip automatic linking for that type.
        'sell' => env('DEFAULT_SELL_ACCOUNT_ID'),
        'purchase' => env('DEFAULT_PURCHASE_ACCOUNT_ID'),
        'expense' => env('DEFAULT_EXPENSE_ACCOUNT_ID'),
        'payroll' => env('DEFAULT_PAYROLL_ACCOUNT_ID'),
        'payment' => env('DEFAULT_PAYMENT_ACCOUNT_ID'),
        'cogs' => env('DEFAULT_COGS_ACCOUNT_ID'),
        'inventory' => env('DEFAULT_INVENTORY_ACCOUNT_ID'),
        'inventory_gain' => env('DEFAULT_INVENTORY_GAIN_ACCOUNT_ID'),
        'inventory_loss' => env('DEFAULT_INVENTORY_LOSS_ACCOUNT_ID'),
        'tax' => env('DEFAULT_TAX_ACCOUNT_ID'),
        'purchase_tax' => env('DEFAULT_PURCHASE_TAX_ACCOUNT_ID'),
        'sales_tax' => env('DEFAULT_SALES_TAX_ACCOUNT_ID'),
        'accounts_receivable' => env('DEFAULT_ACCOUNTS_RECEIVABLE_ACCOUNT_ID'),
        'opening_stock_equity' => env('DEFAULT_OPENING_STOCK_EQUITY_ACCOUNT_ID'),
    ],
];