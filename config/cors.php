<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:8081')
        ? explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost:8081'))
        : ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'Accept', 'Origin'],

    'exposed_headers' => [],

    'max_age' => 86400,

    'supports_credentials' => false,
];