<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\QardHasan;
use Carbon\Carbon;
use Illuminate\Support\Str;

// Mock now to a specific date
$now = Carbon::parse('2026-04-22 12:00:00');
Carbon::setTestNow($now);

echo "Current simulated time: " . now() . "\n\n";

function createLoan($status, $defaultedAt = null) {
    return new QardHasan([
        'user_id' => 1, // dummy
        'qard_id_string' => 'TEST-' . Str::random(5),
        'principal_amount' => 10000,
        'status' => $status,
        'defaulted_at' => $defaultedAt,
    ]);
}

echo "Testing Scenario 1: Future defaulted_at (2026-07-12)\n";
$loan1 = createLoan('active', Carbon::parse('2026-07-12'));
$loan1->save();
echo "Status: " . $loan1->status . " (Expected: active)\n";
echo "Defaulted At: " . ($loan1->defaulted_at ? $loan1->defaulted_at->toDateString() : 'null') . "\n\n";

echo "Testing Scenario 2: Past defaulted_at (2026-01-12)\n";
$loan2 = createLoan('active', Carbon::parse('2026-01-12'));
$loan2->save();
echo "Status: " . $loan2->status . " (Expected: defaulted)\n";
echo "Defaulted At: " . ($loan2->defaulted_at ? $loan2->defaulted_at->toDateString() : 'null') . "\n\n";

echo "Testing Scenario 3: Update active loan to future defaulted_at\n";
$loan3 = createLoan('active', null);
$loan3->save();
echo "Initial Status: " . $loan3->status . "\n";
$loan3->defaulted_at = Carbon::parse('2026-07-12');
$loan3->save();
echo "After update status: " . $loan3->status . " (Expected: active)\n\n";

echo "Testing Scenario 4: Update active loan to past defaulted_at\n";
$loan4 = createLoan('active', null);
$loan4->save();
echo "Initial Status: " . $loan4->status . "\n";
$loan4->defaulted_at = Carbon::parse('2026-01-12');
$loan4->save();
echo "After update status: " . $loan4->status . " (Expected: defaulted)\n\n";

echo "Testing Scenario 5: Update defaulted loan to future defaulted_at\n";
$loan5 = createLoan('defaulted', Carbon::parse('2026-01-12'));
$loan5->save();
echo "Initial Status: " . $loan5->status . "\n";
$loan5->defaulted_at = Carbon::parse('2026-07-12');
$loan5->save();
echo "After update status: " . $loan5->status . " (Expected: active)\n\n";

echo "Testing Scenario 6: Clear defaulted_at on defaulted loan\n";
$loan6 = createLoan('defaulted', Carbon::parse('2026-01-12'));
$loan6->save();
echo "Initial Status: " . $loan6->status . "\n";
$loan6->defaulted_at = null;
$loan6->save();
echo "After update status: " . $loan6->status . " (Expected: active)\n\n";

// Cleanup (don't actually save to DB if possible, but we need to trigger saving hook)
// Since we used save(), they are in DB if DB is working.
// We can use transactions.
