<?php

require __DIR__ . '/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\QardHasan;
use Carbon\Carbon;

function testOverdueAmount() {
    $now = Carbon::parse('2026-04-22');
    Carbon::setTestNow($now);

    echo "Testing with report date: " . $now->toDateString() . "\n";

    // Case 1: Loan marked as defaulted with future defaulted_at
    $loan = new QardHasan([
        'status' => 'defaulted',
        'principal_amount' => 10000,
        'paid_amount' => 2000,
        'defaulted_at' => '2026-07-12', // Future
        'per_installment' => 1000,
        'total_installments' => 10,
        'interval' => 'monthly',
        'received_at' => '2025-07-12',
    ]);

    $overdue = $loan->getOverdueAmount($now);
    $defaultStartDate = $loan->getDefaultStartDate($now);
    $periodOfDefault = 'None';
    if ($defaultStartDate) {
        $days = (int) abs($now->diffInDays($defaultStartDate));
        $formattedDuration = "formatted duration"; // Simulating DurationHelper::format
        $periodOfDefault = $defaultStartDate->format('d/m/Y') . " ({$formattedDuration})";
    }

    echo "Report Display:\n";
    echo "  Amount Defaulted: " . number_format($overdue, 2) . "\n";
    echo "  Period of Default: " . $periodOfDefault . "\n";

    // Case 3: Loan marked as active with future defaulted_at (and some missed installments based on schedule)
    $loan3 = new QardHasan([
        'status' => 'active',
        'principal_amount' => 10000,
        'paid_amount' => 2000,
        'defaulted_at' => '2026-07-12', // Future
        'per_installment' => 1000,
        'total_installments' => 10,
        'interval' => 'monthly',
        'received_at' => '2025-07-12',
    ]);
    // It started 2025-07-12. Today is 2026-04-22. That's about 9 months.
    // Expected paid should be about 9000. Paid is 2000. Overdue should be 7000.
    // BUT if future defaulted_at is set, should it be 0?
    // The user's request suggests so.

    echo "\nLoan 3 status: " . $loan3->status . "\n";
    echo "Defaulted at: " . $loan3->defaulted_at->toDateString() . "\n";
    echo "Current Overdue Amount: " . $loan3->getOverdueAmount($now) . "\n";
    echo "Expected Overdue Amount (if future date overrides): 0.00\n";

    // Case 2: Loan marked as defaulted with past defaulted_at
    $loan2 = new QardHasan([
        'status' => 'defaulted',
        'principal_amount' => 10000,
        'paid_amount' => 2000,
        'defaulted_at' => '2026-01-12', // Past
    ]);
    echo "\nLoan 2 status: " . $loan2->status . "\n";
    echo "Defaulted at: " . $loan2->defaulted_at->toDateString() . "\n";
    echo "Current Overdue Amount: " . $loan2->getOverdueAmount($now) . "\n";
    echo "Expected Overdue Amount: " . $loan2->remaining_principal . "\n";

}

testOverdueAmount();
