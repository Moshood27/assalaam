<?php

use App\Models\LedgerAccount;
use App\Models\LedgerJournal;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Double-Entry Ledger Verification ---\n";

// 1. Check for unbalanced journals
$unbalancedJournals = LedgerJournal::all()->filter(function ($journal) {
    $debits = $journal->entries()->sum('debit');
    $credits = $journal->entries()->sum('credit');
    return abs($debits - $credits) > 0.001;
});

if ($unbalancedJournals->isEmpty()) {
    echo "✅ All journals are balanced.\n";
} else {
    echo "❌ Found " . $unbalancedJournals->count() . " unbalanced journals!\n";
    foreach ($unbalancedJournals as $j) {
        echo "   Journal ID: {$j->id}, Ref: {$j->reference}, Diff: " . ($j->entries()->sum('debit') - $j->entries()->sum('credit')) . "\n";
    }
}

// 2. Verify account balances against entries
$mismatchedAccounts = [];
foreach (LedgerAccount::all() as $account) {
    $entriesSum = $account->entries()->sum(DB::raw('debit - credit'));
    if ($account->type === 'liability' || $account->type === 'equity' || $account->type === 'income') {
        $expectedBalance = -$entriesSum;
    } else {
        $expectedBalance = $entriesSum;
    }

    if (abs($account->balance - $expectedBalance) > 0.001) {
        $mismatchedAccounts[] = [
            'account' => $account->name,
            'stored' => $account->balance,
            'calculated' => $expectedBalance
        ];
    }
}

if (empty($mismatchedAccounts)) {
    echo "✅ All account balances match their entries.\n";
} else {
    echo "❌ Found " . count($mismatchedAccounts) . " accounts with mismatched balances!\n";
    foreach ($mismatchedAccounts as $m) {
        echo "   Account: {$m['account']}, Stored: {$m['stored']}, Calculated: {$m['calculated']}\n";
    }
}

// 3. Accounting Equation Check: Assets = Liabilities + Equity
$assets = LedgerAccount::where('type', 'asset')->sum('balance');
$liabilities = LedgerAccount::where('type', 'liability')->sum('balance');
$equity = LedgerAccount::where('type', 'equity')->sum('balance');
// Include net income in equity if not yet closed
$income = LedgerAccount::where('type', 'income')->sum('balance');
$expenses = LedgerAccount::where('type', 'expense')->sum('balance');
$netIncome = $income - $expenses;

$totalEquityAndLiabilities = $liabilities + $equity + $netIncome;

echo "--- Summary ---\n";
echo "Total Assets: " . number_format($assets, 2) . "\n";
echo "Total Liabilities: " . number_format($liabilities, 2) . "\n";
echo "Total Equity: " . number_format($equity, 2) . "\n";
echo "Net Income (Unclosed): " . number_format($netIncome, 2) . "\n";

if (abs($assets - $totalEquityAndLiabilities) < 0.01) {
    echo "✅ Accounting Equation Balance: Assets = Liabilities + Equity (₦" . number_format($assets, 2) . ")\n";
} else {
    echo "❌ Accounting Equation Mismatch! Diff: " . ($assets - $totalEquityAndLiabilities) . "\n";
}
