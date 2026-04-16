<?php

$origins = env('CORS_ALLOWED_ORIGINS');
$allowedOrigins = $origins
    ? array_values(array_filter(array_map('trim', explode(',', $origins))))
    : ['http://localhost:5173', 'http://127.0.0.1:5173', 'http://localhost:5174',
        'http://127.0.0.1:5174',
        'https://localhost',       // Added for Android Capacitor
        'capacitor://localhost'
        ];

return [

    'paths' => ['*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $allowedOrigins,

    'allowed_origins_patterns' => ['#^https?://localhost(:[0-9]+)?$#'],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', false),

];
