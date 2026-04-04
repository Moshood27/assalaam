<?php

return [
    // Available: mock, dojah (extendable later: smileid, else)
    'provider' => env('KYC_PROVIDER', 'mock'),

    'thresholds' => [
        // Minimum face match confidence (0..1) to accept
        'face_match_min' => (float) env('KYC_FACE_MATCH_MIN', 0.82),
    ],

    'dojah' => [
        'app_id' => env('DOJAH_APP_ID'),
        'secret' => env('DOJAH_SECRET'),
        'base_url' => env('DOJAH_BASE_URL', 'https://api.dojah.io'),
    ],
];
