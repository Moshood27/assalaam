<?php

require __DIR__ . '/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/bootstrap/app.php';

use App\Models\QardHasan;
use Illuminate\Support\Carbon;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Mock a migrated loan
$loan = new QardHasan([
    'total_installments' => 10,
    'per_installment' => 1000,
    'interval' => 'monthly',
    'received_at' => Carbon::parse('2024-01-01'),
    'approved_at' => Carbon::parse('2024-05-23'), // migration date
    'created_at' => Carbon::parse('2024-05-23'),
]);

echo "Testing generateInstallmentSchedule for migrated loan...\n";
$schedule = $loan->generateInstallmentSchedule();

foreach ($schedule as $item) {
    echo "Index: {$item['index']}, Due Date: {$item['due_date']}\n";
}

// Check if sorted
$lastTimestamp = 0;
$isSorted = true;
foreach ($schedule as $item) {
    $currentTimestamp = Carbon::parse($item['due_date'])->timestamp;
    if ($currentTimestamp < $lastTimestamp) {
        $isSorted = false;
        break;
    }
    $lastTimestamp = $currentTimestamp;
}

if ($isSorted) {
    echo "SUCCESS: Schedule is in ascending order.\n";
} else {
    echo "FAILURE: Schedule is NOT in ascending order.\n";
}
