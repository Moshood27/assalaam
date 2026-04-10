<?php

return [
    // GoldAPI settings
    'goldapi_key' => env('GOLDAPI_KEY'),

    // Nisab threshold in NGN. Default is a placeholder; set ZAKAT_NISAB_NGN in .env to override.
    'nisab_ngn' => (float) env('ZAKAT_NISAB_NGN', 500000),

    // Zakat rate (2.5% as a decimal)
    'rate' => (float) env('ZAKAT_RATE', 0.025),

    // Number of days in a lunar year
    'lunar_days' => (int) env('ZAKAT_LUNAR_DAYS', 354),

    // Nisab gram requirements
    'nisab_gold_grams' => (float) env('ZAKAT_NISAB_GOLD_GRAMS', 85),
    'nisab_silver_grams' => (float) env('ZAKAT_NISAB_SILVER_GRAMS', 595),

    // Zakat Al-Fitr fixed amount in NGN (should be updated annually)
    'fitr_amount' => (float) env('ZAKAT_FITR_AMOUNT', 3500),

    // Digital Gold Settings
    'gold_spread' => (float) env('GOLD_SPREAD', 0.01), // 1% spread between buy/sell
    'gold_buy_fee' => (float) env('GOLD_BUY_FEE', 0.005), // 0.5% transaction fee on buy
    'gold_sell_fee' => (float) env('GOLD_SELL_FEE', 0.005), // 0.5% transaction fee on sell
];
