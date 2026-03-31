<?php

return [
    'apps' => [
        [
            'app_id' => env('REVERB_APP_ID', 'local-app'),
            'key' => env('REVERB_APP_KEY', 'local-key'),
            'secret' => env('REVERB_APP_SECRET', 'local-secret'),
            'max_connections' => env('REVERB_MAX_CONNECTIONS', 1000),
            'enable_client_messages' => env('REVERB_CLIENT_MESSAGES', false),
            'enable_statistics' => env('REVERB_STATISTICS', false),
        ],
    ],

    'host' => env('REVERB_HOST', '127.0.0.1'),
    'port' => env('REVERB_PORT', 8080),
    'scheme' => env('REVERB_SCHEME', env('APP_ENV') === 'production' ? 'https' : 'http'),

    'max_request_size' => env('REVERB_MAX_REQUEST_SIZE', 10240),
    'max_message_size' => env('REVERB_MAX_MESSAGE_SIZE', 10240),

    'allowed_origins' => array_filter(array_map('trim', explode(',', (string) env('REVERB_ALLOWED_ORIGINS', '')))),
];
