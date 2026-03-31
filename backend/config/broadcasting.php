<?php

return [

    'default' => env('BROADCAST_CONNECTION', env('BROADCAST_DRIVER', 'log')),

    'connections' => [

        'reverb' => [
            'driver' => 'reverb',
            'key' => env('REVERB_APP_KEY', 'local-key'),
            'secret' => env('REVERB_APP_SECRET', 'local-secret'),
            'app_id' => env('REVERB_APP_ID', 'local-app'),
            'options' => [
                'host' => env('REVERB_HOST', '127.0.0.1'),
                'port' => env('REVERB_PORT', 8080),
                'scheme' => env('REVERB_SCHEME', env('APP_ENV') === 'production' ? 'https' : 'http'),
                'useTLS' => env('REVERB_SCHEME', 'http') === 'https',
                'capacity' => env('REVERB_CAPACITY', 100),
                'max_message_size' => env('REVERB_MAX_MESSAGE_SIZE', 10240),
            ],
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],
    ],

];
