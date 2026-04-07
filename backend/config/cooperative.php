<?php

return [
    'zakat' => [
        // Nisab threshold in NGN; default can be overridden via .env
        'nisab_ngn' => env('COOP_ZAKAT_NISAB_NGN', 500000),
        // Zakat rate (2.5% = 0.025)
        'rate' => env('COOP_ZAKAT_RATE', 0.025),
        // Number of days to consider as one lunar year (approx.)
        'lunar_days' => env('COOP_ZAKAT_LUNAR_DAYS', 354),
        // Which scheme name to use for Zakat collections
        'scheme_name' => env('COOP_ZAKAT_SCHEME', 'Zakat'),
        // Whether to include Shares in the balance calculation
        'include_shares' => env('COOP_ZAKAT_INCLUDE_SHARES', false),
    ],
    'admin_ip_whitelist' => array_filter(array_map('trim', explode(',', env('ADMIN_IP_WHITELIST', '')))),
];
