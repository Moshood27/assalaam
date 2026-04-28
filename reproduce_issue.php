<?php
require 'backend/vendor/autoload.php';
$app = require_once 'backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\QardHasan;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

// Mock now
$now = Carbon::parse('2026-04-28 15:00:00');
Carbon::setTestNow($now);

echo "Current simulated time: " . now() . "\n\n";

function createLoan($status, $principal, $paid, $defaultedAt = null) {
    return new QardHasan([
        'user_id' => 1, // Assuming user 1 exists or it doesn't matter for this test
        'qard_id_string' => 'TEST-' . Str::upper(Str::random(5)),
        'principal_amount' => $principal,
        'paid_amount' => $paid,
        'status' => $status,
        'defaulted_at' => $defaultedAt,
        'total_installments' => 12,
        'per_installment' => $principal / 12,
        'interval' => 'monthly',
    ]);
}

DB::beginTransaction();
try {
    echo "Scenario 1: New loan, fully paid, status set to 'defaulted' (like in LoansImport)\n";
    $loan1 = createLoan('defaulted', 1000, 1000, Carbon::parse('2026-01-01'));
    // We simulate what happens in the database/Eloquent
    $loan1->save();
    echo "Status: " . $loan1->status . " (Expected: completed)\n";
    echo "Defaulted At: " . ($loan1->defaulted_at ? $loan1->defaulted_at->toISOString() : 'null') . " (Expected: null)\n\n";

    echo "Scenario 2: Existing defaulted loan, updated to full payment via increment (like in seeder)\n";
    $loan2 = createLoan('defaulted', 1000, 500, Carbon::parse('2026-01-01'));
    $loan2->save();
    echo "Initial Status: " . $loan2->status . "\n";
    
    // Simulate seeder increment and update
    $loan2->paid_amount += 500;
    if ($loan2->paid_amount >= $loan2->principal_amount) {
        $loan2->status = 'completed';
    }
    $loan2->save();
    echo "Status after update: " . $loan2->status . " (Expected: completed)\n";
    echo "Defaulted At: " . ($loan2->defaulted_at ? $loan2->defaulted_at->toISOString() : 'null') . " (Expected: null) <- BUG IF NOT NULL\n\n";

    echo "Scenario 3: Zero outstanding loan stuck in defaulted status (The Issue)\n";
    // Manually force a bad state in DB if possible, or just simulate saving it again
    $loan3 = createLoan('defaulted', 1000, 1000, Carbon::parse('2026-01-01'));
    // Bypass Eloquent for a moment to create the "bad" state
    $id = DB::table('qard_hasans')->insertGetId([
        'user_id' => 1,
        'qard_id_string' => 'BAD-' . Str::upper(Str::random(5)),
        'principal_amount' => 1000,
        'paid_amount' => 1000,
        'status' => 'defaulted',
        'defaulted_at' => '2026-01-01 00:00:00',
        'total_installments' => 12,
        'per_installment' => 1000/12,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    $loan3 = QardHasan::find($id);
    echo "Initial BAD state - Status: " . $loan3->status . ", Paid: " . $loan3->paid_amount . ", Principal: " . $loan3->principal_amount . "\n";
    
    $loan3->save(); // Try to self-heal via saving
    echo "Status after save(): " . $loan3->status . " (Expected: completed)\n";
    echo "Defaulted At: " . ($loan3->defaulted_at ? $loan3->defaulted_at->toISOString() : 'null') . " (Expected: null)\n\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    DB::rollBack();
    echo "Transaction rolled back.\n";
}
