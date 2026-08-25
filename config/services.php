<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // -- Lab-Doc external services --

    'deepseek' => [
        'api_key' => env('DEEPSEEK_API_KEY'),
        'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
        'model' => env('DEEPSEEK_MODEL', 'deepseek-v4-flash'),
        'max_tokens' => env('DEEPSEEK_MAX_TOKENS', 2048),
        'temperature' => env('DEEPSEEK_TEMPERATURE', 0.3),
    ],

    'termii' => [
        'api_key' => env('TERMII_API_KEY'),
        'base_url' => env('TERMII_BASE_URL', 'https://api.ng.termii.com'),
        'sender_id' => env('TERMII_SENDER_ID', 'LabDoc'),
    ],

    'paystack' => [
        'secret_key' => env('PAYSTACK_SECRET_KEY'),
        'public_key' => env('PAYSTACK_PUBLIC_KEY'),
        'base_url' => env('PAYSTACK_BASE_URL', 'https://api.paystack.co'),
    ],

    'flutterwave' => [
        'secret_key' => env('FLUTTERWAVE_SECRET_KEY'),
        'public_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
        'encryption_key' => env('FLUTTERWAVE_ENCRYPTION_KEY'),
        'secret_hash' => env('FLUTTERWAVE_SECRET_HASH'),
        'base_url' => env('FLUTTERWAVE_BASE_URL', 'https://api.flutterwave.com'),
    ],

    'nomba' => [
        'base_url' => env('NOMBA_BASE_URL', 'https://api.nomba.com'),
        'client_id' => env('NOMBA_CLIENT_ID'),
        'secret_key' => env('NOMBA_SECRET_KEY'),
        'account_id' => env('NOMBA_PUBLIC_KEY'),
        'token_path' => env('NOMBA_TOKEN_PATH', '/v1/auth/token/issue'),
        'initialize_path' => env('NOMBA_INITIALIZE_PATH', '/v1/checkout/order'),
        'verify_path' => env('NOMBA_VERIFY_PATH', '/v1/checkout/transaction'),
        'webhook_secret' => env('NOMBA_WEBHOOK_SECRET'),
        'webhook_header' => env('NOMBA_WEBHOOK_HEADER', 'nomba-signature'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    ],

];

