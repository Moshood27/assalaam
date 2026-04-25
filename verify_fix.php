<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
require __DIR__ . '/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

function testPregnancyGrace() {
    echo "Starting Pregnancy Grace Logic Test...\n";

    // Create a temporary user (or use an existing one if available, but better to be safe)
    // We will use DB transaction to avoid cluttering the DB
    DB::beginTransaction();

    try {
        $user = User::factory()->create([
            'gender' => 'female',
            'pregnancy_request_status' => null,
            'is_pregnant' => false,
            'baby_birth_date' => null,
            'pregnancy_grace_until' => null,
        ]);

        echo "1. Initial State: is_in_pregnancy_grace = " . ($user->isInPregnancyGracePeriod() ? "TRUE" : "FALSE") . " (Expected: FALSE)\n";

        // Simulate application
        $user->pregnancy_request_status = 'pending';
        $user->is_pregnant = true;
        $user->save();
        $user->refresh();

        echo "2. Pending State (Pregnant): is_in_pregnancy_grace = " . ($user->isInPregnancyGracePeriod() ? "TRUE" : "FALSE") . " (Expected: FALSE)\n";
        echo "   Status: " . $user->pregnancy_request_status . "\n";

        // Simulate birth date application
        $user->is_pregnant = false;
        $user->baby_birth_date = now()->subMonth();
        $user->save();
        $user->refresh();

        echo "3. Pending State (Birth): is_in_pregnancy_grace = " . ($user->isInPregnancyGracePeriod() ? "TRUE" : "FALSE") . " (Expected: FALSE)\n";

        // Simulate approval
        $user->pregnancy_request_status = 'approved';
        $user->save();
        $user->refresh();

        echo "4. Approved State: is_in_pregnancy_grace = " . ($user->isInPregnancyGracePeriod() ? "TRUE" : "FALSE") . " (Expected: TRUE)\n";

        // Test manual admin update (booted hook)
        $user2 = User::factory()->create([
            'gender' => 'female',
            'pregnancy_request_status' => null,
        ]);
        $user2->is_pregnant = true;
        $user2->save();
        $user2->refresh();

        echo "5. Manual Admin Update (Status NULL): Status = " . $user2->pregnancy_request_status . " (Expected: approved)\n";
        echo "   is_in_pregnancy_grace = " . ($user2->isInPregnancyGracePeriod() ? "TRUE" : "FALSE") . " (Expected: TRUE)\n";

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    } finally {
        DB::rollBack();
    }
}

testPregnancyGrace();
