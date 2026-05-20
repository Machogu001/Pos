<?php

return [
    /*
    |--------------------------------------------------------------------------
    | M-Pesa Daraja API credentials
    |--------------------------------------------------------------------------
    | Used by MpesaController for POS/sell and purchase payments.
    | Subscription payments use the credentials stored in admin_settings.
    */
    'consumer_key'    => env('MPESA_CONSUMER_KEY'),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
    'shortcode'       => env('MPESA_SHORTCODE'),
    'passkey'         => env('MPESA_PASSKEY'),
    'callback'        => env('MPESA_CALLBACK'),
    'shortcode_type'  => env('MPESA_SHORTCODE_TYPE', 'paybill'),
    'store_number'    => env('MPESA_STORE_NUMBER'),
];
