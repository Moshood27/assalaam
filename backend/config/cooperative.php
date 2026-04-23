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
    'low_stock_threshold' => env('COOP_LOW_STOCK_THRESHOLD', 5),
    'legacy' => [
        'inactivity_months' => env('COOP_LEGACY_INACTIVITY_MONTHS', 6),
        'check_period_days' => env('COOP_LEGACY_CHECK_PERIOD_DAYS', 30), // Notify admin after this many days since wellness check if still inactive
    ],
    'approvals' => [
        'high_value_loan_threshold' => env('COOP_HIGH_VALUE_LOAN_THRESHOLD', 500000), // 500k NGN
        'high_value_withdrawal_threshold' => env('COOP_HIGH_VALUE_WITHDRAWAL_THRESHOLD', 500000), // 500k NGN
        'high_value_expense_threshold' => env('COOP_HIGH_VALUE_EXPENSE_THRESHOLD', 200000), // 200k NGN
        'required_approvals_count' => env('COOP_REQUIRED_APPROVALS_COUNT', 2),
    ],
    'attendance' => [
        'default_fine' => env('COOP_ATTENDANCE_FINE', 500),
        'apology_fine' => env('COOP_APOLOGY_FINE', 100),
        'radius_meters' => env('COOP_ATTENDANCE_RADIUS', 100),
        'grace_period_minutes' => env('COOP_ATTENDANCE_GRACE_PERIOD', 0),
    ],
    'wallet' => [
        'maintenance_charge' => [
            'percentage' => env('COOP_WALLET_MAINTENANCE_CHARGE_PERCENTAGE', 1), // 1%
            'max_amount' => env('COOP_WALLET_MAINTENANCE_CHARGE_MAX', 500),
        ],
    ],
    'admin_charges' => [
        'amount' => env('COOP_ADMIN_CHARGE', 300),
    ],
    'appropriation' => [
        'ratios' => [
            ['name' => 'Statutory Reserve', 'percent' => 25],
            ['name' => 'Education Fund', 'percent' => 2.5],
            ['name' => 'Dividend to Members', 'percent' => 50],
            ['name' => 'Honorarium to Officers', 'percent' => 10],
        ],
    ],
    'timezone' => env('COOP_TIMEZONE', 'Africa/Lagos'),
    'mobile_min_version' => env('MOBILE_MIN_VERSION', '1.0.0'),
    'mobile_current_version' => env('MOBILE_CURRENT_VERSION', '1.0.0'),
    'maintenance_mode' => env('MAINTENANCE_MODE', false),
    'maintenance_message' => env('MAINTENANCE_MESSAGE', 'We are currently performing scheduled maintenance to improve our services. We\'ll be back shortly.'),
    'maintenance_until' => env('MAINTENANCE_UNTIL', 'Approximately 1 hour'),
    'system_announcement' => env('SYSTEM_ANNOUNCEMENT', null),
    'play_store_url' => env('PLAY_STORE_URL', 'https://play.google.com/store/apps/details?id=com.attaqwa.app'),
    'loan_credit_score_enabled' => env('LOAN_CREDIT_SCORE_ENABLED', true),
];
