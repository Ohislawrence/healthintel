<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://healthintel.app', // ⚠️ Must match production frontend EXACTLY (including https://)
        'http://localhost:3000', // ⚠️ Must match local frontend EXACTLY (including http://)
        'http://127.0.0.1:8000',
        'http://localhost',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'Accept', 'Origin'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => true,
];