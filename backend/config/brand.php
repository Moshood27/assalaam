<?php

return [
    // Brand slug used to resolve asset filenames like images/{slug}-logo.svg
    'slug' => env('BRAND_SLUG', 'assalaam'),

    // Human readable brand name. Defaults to APP_NAME if provided
    'name' => env('APP_NAME', 'assalaam CO-OPERATIVE'),
];
