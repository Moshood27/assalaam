<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'paystack' => (function () {
        // Do NOT call app()->environment() here; config files load before the container binds 'env'.
        $appEnv = env('APP_ENV', 'production');
        $defaultMode = ($appEnv === 'production') ? 'live' : 'test';
        $mode = env('PAYSTACK_ENV', $defaultMode);
        $public = $mode === 'live'
            ? env('PAYSTACK_LIVE_PUBLIC_KEY', env('PAYSTACK_PUBLIC_KEY'))
            : env('PAYSTACK_TEST_PUBLIC_KEY', env('PAYSTACK_PUBLIC_KEY'));
        $secret = $mode === 'live'
            ? env('PAYSTACK_LIVE_SECRET_KEY', env('PAYSTACK_SECRET_KEY'))
            : env('PAYSTACK_TEST_SECRET_KEY', env('PAYSTACK_SECRET_KEY'));
        return [
            'public_key' => $public,
            'secret_key' => $secret,
            'mode' => $mode,
        ];
    })(),

    'flutterwave' => [
        'public_key' => env('FLW_PUBLIC_KEY'),
        'secret_key' => env('FLW_SECRET_KEY'),
        // Secret Hash used to verify webhooks (set in Flutterwave Dashboard -> Webhooks)
        'secret_hash' => env('FLW_SECRET_HASH'),
        // Optional: keep this in sync with your dashboard webhook URL if needed
        'webhook_url' => env('FLW_WEBHOOK_URL', rtrim(env('APP_URL', ''), '/') . '/api/webhooks/flutterwave'),
    ],

    // VTU provider (VTpass by default)
    'vtu' => [
        'provider' => env('VTU_PROVIDER', 'clubkonnect'),
        // Providers routing order for Smart VTU (comma-separated): e.g. clubkonnect,shago,vtpass
        'routing_order' => env('VTU_ROUTING_ORDER', 'clubkonnect,shago,vtpass'),
        'low_balance_threshold' => (float) env('VTU_LOW_BALANCE_THRESHOLD', 10000),

        // Auto-select sandbox base URL in local/dev unless explicitly overridden
        // You can force sandbox by setting VTU_SANDBOX=true
        // Or set VTU_BASE_URL directly to override
        'base_url' => (function () {
            $sandboxDefault = env('APP_ENV') === 'local';
            $sandbox = filter_var(env('VTU_SANDBOX', $sandboxDefault), FILTER_VALIDATE_BOOLEAN);
            if ($sandbox) {
                // Force sandbox base URL when VTU_SANDBOX=true (or in local by default)
                return 'https://sandbox.vtpass.com/api';
            }
            // Live/base URL can be overridden via VTU_BASE_URL
            return env('VTU_BASE_URL', 'https://vtpass.com/api');
        })(),
        'api_key' => env('VTPASS_API_KEY'),
        'public_key' => env('VTPASS_PUBLIC_KEY'),
        'secret_key' => env('VTPASS_SECRET_KEY'),
        'sandbox' => (function () {
            $sandboxDefault = env('APP_ENV') === 'local';
            return filter_var(env('VTU_SANDBOX', $sandboxDefault), FILTER_VALIDATE_BOOLEAN);
        })(),
        // Revenue knobs
        'default_discount' => (float) env('VTU_DEFAULT_DISCOUNT', 0.03), // 3% default
        'convenience_fee' => (float) env('VTU_CONVENIENCE_FEE', 0),      // flat fee on data
        'webhook_url' => env('VTU_WEBHOOK_URL', rtrim(env('APP_URL', ''), '/') . '/api/vtu/webhook'),

        // Optional other providers for Smart VTU routing
        'clubkonnect' => [
            'enabled' => filter_var(env('CLUBKONNECT_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
            'base_url' => env('CLUBKONNECT_BASE_URL', 'https://www.nellobytesystems.com'),
            // Nellobytes/ClubKonnect credentials
            'user_id' => env('CLUBKONNECT_USER_ID', env('VTU_USER_ID', env('CLUBKONNECT_USERNAME'))),
            'api_key' => env('CLUBKONNECT_API_KEY', env('VTU_API_KEY')),
            // Backward-compat/optional fields
            'username' => env('CLUBKONNECT_USERNAME'),
            'password' => env('CLUBKONNECT_PASSWORD'),
        ],
        'shago' => [
            'enabled' => filter_var(env('SHAGO_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
            'base_url' => env('SHAGO_BASE_URL'),
            'api_key' => env('SHAGO_API_KEY'),
            'secret' => env('SHAGO_SECRET'),
        ],
    ],

    'google' => [
        'maps_api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'goals' => [
        'commission_rate' => (float) env('GOALS_COMMISSION_RATE', 0.05),
    ],

    // Takaful (Mutual Protection Pool)
    'takaful' => [
        'monthly_amount' => (float) env('TAKAFUL_MONTHLY_AMOUNT', 200),
        'notify_contacts' => (bool) env('TAKAFUL_NOTIFY_CONTACTS', true),
    ],

    'monnify' => [
        'api_key' => env('MONNIFY_API_KEY'),
        'secret_key' => env('MONNIFY_SECRET_KEY'),
        'contract_code' => env('MONNIFY_CONTRACT_CODE'),
        'base_url' => env('MONNIFY_BASE_URL', 'https://api.monnify.com'),
    ],

    'opay' => [
        'merchant_id' => env('OPAY_MERCHANT_ID'),
        'public_key' => env('OPAY_PUBLIC_KEY'),
        'secret_key' => env('OPAY_SECRET_KEY'),
        'base_url' => env('OPAY_BASE_URL', 'https://api.opaycheckout.com/api/v1/international'),
    ],

    ];
