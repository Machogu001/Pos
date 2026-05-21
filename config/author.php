<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Company/Author/Developer/Licence information of the application
    |--------------------------------------------------------------------------
    |
    | All details about the author/developer/contact/licence of the application
    |
    | IMPORTANT: CHANGING ANY OF THIS INFORMATION WILL UNSTABILIZE THE APPLICATION
    |
    */

    'vendor' => 'Ultimate BreMac Systems Ltd',
    'vendor_url' => 'http://bremac.co.ke',
    'email' => 'info@bremac.co.ke',
    'app_version' => function_exists('pos_release_version') ? pos_release_version() : '13.0',
    'released_at'  => function_exists('pos_release_date') ? pos_release_date() : '2026-05-21',
    'update_check_url' => env('UPDATE_CHECK_URL', ''),
    'lic1' => 'aHR0cHM6Ly9sLnVsdGltYXRlZm9zdGVycy5jb20vYXBpL3R5cGVfMQ==',
    'pid' => 1,
    'envato_purchase_code' => env('ENVATO_PURCHASE_CODE', 0),
];
