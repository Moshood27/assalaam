<?php

return [
    // Master switch: set to true to enable SMS sending
    'enabled' => env('SMS_ENABLED', false),

    // Driver: 'termii', 'log', or 'generic'
    'driver' => env('SMS_DRIVER', 'log'),

    // Common options
    'sender' => env('SMS_SENDER', 'Coop'),

    // Termii-specific
    'api_key' => env('SMS_API_KEY', ''),
    'base_url' => env('SMS_BASE_URL', 'https://api.ng.termii.com'),
    'channel' => env('SMS_CHANNEL', 'generic'),

    // Generic JSON POST provider
    'url' => env('SMS_URL', ''),
];
