<?php

return [
    'enabled' => env('PUSH_ENABLED', false),
    // Driver: fcm (legacy), fcm_v1 (latest HTTP v1), log
    'driver' => env('PUSH_DRIVER', 'fcm'),

    // FCM configuration
    'fcm' => [
        // Legacy HTTP API
        'server_key' => env('FCM_SERVER_KEY', ''),
        'base_url' => rtrim(env('FCM_BASE_URL', 'https://fcm.googleapis.com'), '/'),
        // HTTP v1 uses Service Account credentials via kreait/laravel-firebase package.
        // Provide credentials path in FIREBASE_CREDENTIALS or GOOGLE_APPLICATION_CREDENTIALS env.
    ],

    // Log driver (for local dev): logs and returns success
    'log' => [
        'channel' => env('PUSH_LOG_CHANNEL', null),
    ],
];
