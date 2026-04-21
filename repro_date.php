<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Carbon\Carbon;

$values = [
    20230512, // Integer YYYYMMDD
    '2023-05-12',
    '20230512',
    45058, // Excel serial for 2023-05-12
    '45058',
];

foreach ($values as $val) {
    try {
        $parsed = Carbon::parse($val);
        echo "Value: " . var_export($val, true) . " => Parsed: " . $parsed->toDateTimeString() . "\n";
    } catch (\Exception $e) {
        echo "Value: " . var_export($val, true) . " => Error: " . $e->getMessage() . "\n";
    }
}
