<?php
use App\Models\QardHasan;
use Carbon\Carbon;

$now = Carbon::parse('2026-04-22 12:00:00');
Carbon::setTestNow($now);

echo "Current simulated time: " . now() . "\n";

// Scenario 1: Future defaulted_at
$loan1 = new QardHasan(['user_id' => 1, 'principal_amount' => 10000, 'status' => 'active', 'defaulted_at' => '2026-07-12', 'qard_id_string' => 'T1']);
$loan1->save();
echo "Loan 1 Status: " . $loan1->status . " (Expected: active)\n";

// Scenario 2: Past defaulted_at
$loan2 = new QardHasan(['user_id' => 1, 'principal_amount' => 10000, 'status' => 'active', 'defaulted_at' => '2026-01-12', 'qard_id_string' => 'T2']);
$loan2->save();
echo "Loan 2 Status: " . $loan2->status . " (Expected: defaulted)\n";

// Scenario 3: Update to future
$loan3 = new QardHasan(['user_id' => 1, 'principal_amount' => 10000, 'status' => 'active', 'qard_id_string' => 'T3']);
$loan3->save();
$loan3->update(['defaulted_at' => '2026-07-12']);
echo "Loan 3 Status after update: " . $loan3->status . " (Expected: active)\n";

// Scenario 4: Update to past
$loan4 = new QardHasan(['user_id' => 1, 'principal_amount' => 10000, 'status' => 'active', 'qard_id_string' => 'T4']);
$loan4->save();
$loan4->update(['defaulted_at' => '2026-01-12']);
echo "Loan 4 Status after update: " . $loan4->status . " (Expected: defaulted)\n";

// Scenario 5: Clear default
$loan5 = new QardHasan(['user_id' => 1, 'principal_amount' => 10000, 'status' => 'defaulted', 'defaulted_at' => '2026-01-12', 'qard_id_string' => 'T5']);
$loan5->save();
$loan5->update(['defaulted_at' => null]);
echo "Loan 5 Status after clear: " . $loan5->status . " (Expected: active)\n";
