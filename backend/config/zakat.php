<?php

return [
    // Nisab threshold in NGN. Default is a placeholder; set ZAKAT_NISAB_NGN in .env to override.
    'nisab_ngn' => (float) env('ZAKAT_NISAB_NGN', 500000),

    // Zakat rate (2.5% as a decimal)
    'rate' => (float) env('ZAKAT_RATE', 0.025),

    // Number of days in a lunar year
    'lunar_days' => (int) env('ZAKAT_LUNAR_DAYS', 354),
];
