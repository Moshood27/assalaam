<?php
require __DIR__.'/backend/vendor/autoload.php';
$app = require_once __DIR__.'/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Scheme;
use App\Models\QardHasan;
use App\Models\Contribution;
use App\Models\QardHasanRepayment;

DB::beginTransaction();
try {
    $user = User::factory()->create(['name' => 'Test User Loan Repay']);
    $scheme = Scheme::where('name', 'Loan Repayment')->first();
    if (!$scheme) {
        $scheme = Scheme::create(['name' => 'Loan Repayment', 'active' => true]);
    }

    $loan = QardHasan::create([
        'user_id' => $user->id,
        'principal_amount' => 10000,
        'paid_amount' => 0,
        'status' => 'active',
        'qard_id_string' => 'TEST-LOAN-001'
    ]);

    echo "Loan created with ID: {$loan->id}, status: {$loan->status}, principal: {$loan->principal_amount}\n";

    $contribution = Contribution::create([
        'user_id' => $user->id,
        'scheme_id' => $scheme->id,
        'amount' => 2500,
        'status' => 'success',
        'category' => 'loan_repayment'
    ]);

    echo "Contribution created with ID: {$contribution->id}, amount: {$contribution->amount}\n";

    $loan->refresh();
    echo "Loan paid_amount after contribution: {$loan->paid_amount}\n";

    $repayment = QardHasanRepayment::where('qard_hasan_id', $loan->id)->first();
    if ($repayment) {
        echo "Repayment record found with amount: {$repayment->amount}, reference: {$repayment->reference}\n";
        if ($repayment->amount == 2500 && $loan->paid_amount == 2500) {
            echo "SUCCESS: Loan repayment applied correctly.\n";
        } else {
            echo "FAILURE: Amount mismatch.\n";
        }
    } else {
        echo "FAILURE: Repayment record NOT found.\n";
    }

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
} finally {
    DB::rollBack();
}
