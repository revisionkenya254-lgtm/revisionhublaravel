<?php

return [
    'name' => 'BasicPayment',
    'default_schema_string_length' => (int) env('DEFAULT_SCHEMA_STRING_LENGTH', 255),
    'default_status' => [
        'active_text' => 'active',
        'inactive_text' => 'inactive',
        'active_bool' => true,
        'inactive_bool' => false,
        'active_int' => 1,
        'inactive_int' => 0,
    ],
    'mpesa_stk_push' => [
        'sandbox' => [
            'consumer_key' => env('MPESA_SANDBOX_CONSUMER_KEY', ''),
            'consumer_secret' => env('MPESA_SANDBOX_CONSUMER_SECRET', ''),
            'shortcode' => env('MPESA_SANDBOX_SHORTCODE', ''),
            'party_b' => env('MPESA_SANDBOX_PARTY_B', ''),
            'passkey' => env('MPESA_SANDBOX_PASSKEY', ''),
            'transaction_type' => env('MPESA_SANDBOX_TRANSACTION_TYPE', 'CustomerPayBillOnline'),
        ],
        'production' => [
            'consumer_key' => env('MPESA_PRODUCTION_CONSUMER_KEY', ''),
            'consumer_secret' => env('MPESA_PRODUCTION_CONSUMER_SECRET', ''),
            'shortcode' => env('MPESA_PRODUCTION_SHORTCODE', ''),
            'party_b' => env('MPESA_PRODUCTION_PARTY_B', ''),
            'passkey' => env('MPESA_PRODUCTION_PASSKEY', ''),
            'transaction_type' => env('MPESA_PRODUCTION_TRANSACTION_TYPE', 'CustomerBuyGoodsOnline'),
        ],
    ],
];
