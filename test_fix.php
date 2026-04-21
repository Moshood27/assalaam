<?php
require __DIR__ . '/backend/vendor/autoload.php';

use App\Imports\Concerns\HandlesExcelDates;
use Carbon\Carbon;

class Test {
    use HandlesExcelDates;
    public function test($val) {
        return $this->parseExcelDate($val);
    }
}

$tester = new Test();
$values = [
    '2023-05-12',
    '20230512',
    45058, // Excel serial for 2023-05-12
    '45058',
    20573, // The value in the issue description?
];

foreach ($values as $val) {
    $parsed = $tester->test($val);
    echo "Value: " . var_export($val, true) . " => Parsed: " . ($parsed ? $parsed->toDateTimeString() : 'NULL') . "\n";
}
