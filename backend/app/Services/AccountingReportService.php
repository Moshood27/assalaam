<?php

namespace App\Services;

use App\Models\WalletTransaction;
use App\Models\QardHasan;
use App\Models\QardHasanRepayment;
use App\Models\CharityEntry;
use App\Models\IncomeEntry;
use App\Models\ExpenseEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class AccountingReportService
{
    /**
     * Build a simple Trial Balance between dates (inclusive).
     * If $from is null, computes from beginning of time up to $to (or now).
     */
    public function buildTrialBalance(?string $from = null, ?string $to = null): array
    {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDate = $to ? Carbon::parse($to)->endOfDay() : Carbon::now();

        $accounts = [];
        $post = function (string $name, float $debit = 0.0, float $credit = 0.0) use (&$accounts) {
            if (!isset($accounts[$name])) {
                $accounts[$name] = ['debit' => 0.0, 'credit' => 0.0];
            }
            $accounts[$name]['debit'] += (float) $debit;
            $accounts[$name]['credit'] += (float) $credit;
        };

        // Wallet transactions: model wallet topups and allocations
        $wtQuery = WalletTransaction::query();
        if ($fromDate) {
            $wtQuery->where('created_at', '>=', $fromDate);
        }
        $wtQuery->where('created_at', '<=', $toDate);
        $wtQuery->orderBy('created_at');

        foreach ($wtQuery->get() as $wt) {
            $amount = (float) $wt->amount;
            if ($wt->type === 'credit') {
                // Dr Cash/Bank, Cr Wallets Payable
                $post('Cash & Bank', $amount, 0);
                $post('Wallets Payable', 0, $amount);
            } else { // debit
                if (($wt->source ?? null) === 'wallet_allocation') {
                    // Dr Wallets Payable, Cr Member Savings Payable
                    $post('Wallets Payable', $amount, 0);
                    $post('Member Savings Payable', 0, $amount);
                } else {
                    // Fallback: Dr Wallets Payable, Cr Cash (e.g., withdrawal)
                    $post('Wallets Payable', $amount, 0);
                    $post('Cash & Bank', 0, $amount);
                }
            }
        }

        // Loans disbursed (treat created active/completed as disbursed)
        $loanQuery = QardHasan::query()->whereIn('status', ['active', 'completed']);
        if ($fromDate) {
            $loanQuery->where('created_at', '>=', $fromDate);
        }
        $loanQuery->where('created_at', '<=', $toDate);
        foreach ($loanQuery->get() as $loan) {
            $principal = (float) $loan->principal_amount;
            $post('Loans Receivable', $principal, 0);
            $post('Cash & Bank', 0, $principal);
        }

        // Loan repayments (cash in, reduce receivable)
        $repQuery = QardHasanRepayment::query();
        if ($fromDate) {
            $repQuery->where(function ($q) use ($fromDate) {
                $q->whereNotNull('paid_at')->where('paid_at', '>=', $fromDate)
                  ->orWhereNull('paid_at')->where('created_at', '>=', $fromDate);
            });
        }
        $repQuery->where(function ($q) use ($toDate) {
            $q->whereNotNull('paid_at')->where('paid_at', '<=', $toDate)
              ->orWhereNull('paid_at')->where('created_at', '<=', $toDate);
        });
        // Include common success statuses only
        $repQuery->whereIn('status', ['success', 'paid', 'completed']);
        foreach ($repQuery->get() as $rep) {
            $amt = (float) $rep->amount;
            $post('Cash & Bank', $amt, 0);
            $post('Loans Receivable', 0, $amt);
        }

        // Charity receipts -> restricted fund
        $charityQuery = CharityEntry::query();
        if ($fromDate) {
            $charityQuery->where('created_at', '>=', $fromDate);
        }
        $charityQuery->where('created_at', '<=', $toDate);
        foreach ($charityQuery->get() as $ce) {
            $amt = (float) $ce->amount;
            $post('Cash & Bank', $amt, 0);
            $post('Charity Fund (Restricted)', 0, $amt);
        }

        // Manual Income Entries (admin-entered)
        if (Schema::hasTable('income_entries')) {
            $miQuery = IncomeEntry::query();
            if ($fromDate) {
                $miQuery->where('date', '>=', $fromDate->toDateString());
            }
            $miQuery->where('date', '<=', $toDate->toDateString());
            foreach ($miQuery->get() as $mi) {
                $amt = (float) $mi->amount;
                $cat = $mi->category ?: 'Uncategorized';
                // Dr Cash & Bank, Cr Income - {Category}
                $post('Cash & Bank', $amt, 0);
                $post('Income - ' . $cat, 0, $amt);
            }
        }

        // Manual Expense Entries (admin-entered)
        if (Schema::hasTable('expense_entries')) {
            $meQuery = ExpenseEntry::query();
            if ($fromDate) {
                $meQuery->where('date', '>=', $fromDate->toDateString());
            }
            $meQuery->where('date', '<=', $toDate->toDateString());
            foreach ($meQuery->get() as $me) {
                $amt = (float) $me->amount;
                $cat = $me->category ?: 'Uncategorized';
                // Dr Expense - {Category}, Cr Cash & Bank
                $post('Expense - ' . $cat, $amt, 0);
                $post('Cash & Bank', 0, $amt);
            }
        }

        // Totals and check
        $totalDebit = 0.0; $totalCredit = 0.0;
        foreach ($accounts as $row) {
            $totalDebit += $row['debit'];
            $totalCredit += $row['credit'];
        }

        return [
            'from' => $fromDate?->toDateString(),
            'to' => $toDate->toDateString(),
            'accounts' => $accounts,
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
            'balanced' => abs($totalDebit - $totalCredit) < 0.01,
        ];
    }

    /**
     * Build a Balance Sheet as of a given date (inclusive).
     */
    public function buildBalanceSheet(?string $asOf = null): array
    {
        $date = $asOf ? Carbon::parse($asOf)->endOfDay() : Carbon::now();
        $tb = $this->buildTrialBalance(null, $date->toDateString());

        $assets = [];
        $liabilities = [];

        $push = function (array &$arr, string $name, float $amount) {
            if ($amount <= 0.00001) return;
            $arr[] = ['name' => $name, 'amount' => round($amount, 2)];
        };

        // Determine net balances per account
        foreach ($tb['accounts'] as $name => $row) {
            $net = (float) $row['debit'] - (float) $row['credit'];
            if (in_array($name, ['Cash & Bank', 'Loans Receivable'])) {
                $push($assets, $name, max(0, $net));
            } else {
                // Liabilities typically have credit balances (negative net in our calc)
                $push($liabilities, $name, max(0, -$net));
            }
        }

        $totalAssets = array_sum(array_column($assets, 'amount'));
        $totalLiab = array_sum(array_column($liabilities, 'amount'));
        $equity = round($totalAssets - $totalLiab, 2);

        if (abs($equity) > 0.00001) {
            $liabilities[] = ['name' => 'Accumulated Surplus / (Deficit)', 'amount' => round($equity, 2)];
            $totalLiab += $equity;
        }

        return [
            'as_of' => $date->toDateString(),
            'assets' => $assets,
            'liabilities' => $liabilities,
            'total_assets' => round($totalAssets, 2),
            'total_liabilities_and_equity' => round($totalLiab, 2),
        ];
    }

    /**
     * Build Income & Expenditure Account for the date range.
     * We treat Admin Fees on loans as income when the loan becomes active/completed within the period.
     */
    public function buildIncomeAndExpenditure(?string $from = null, ?string $to = null): array
    {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfYear();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : Carbon::now();

        $incomeLines = [];
        $addIncome = function (string $name, float $amount) use (&$incomeLines) {
            if ($amount <= 0) return;
            $incomeLines[] = ['name' => $name, 'amount' => round($amount, 2)];
        };

        $expenseLines = [];
        $addExpense = function (string $name, float $amount) use (&$expenseLines) {
            if ($amount <= 0) return;
            $expenseLines[] = ['name' => $name, 'amount' => round($amount, 2)];
        };

        // Admin Fee Income from loans activated/completed in period
        $loans = QardHasan::query()
            ->whereIn('status', ['active', 'completed'])
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->get();
        $adminIncome = 0.0;
        foreach ($loans as $l) {
            $fee = (float) ($l->admin_fee_flat ?? 0) + ((float) $l->principal_amount) * (((float) $l->admin_fee_pct ?? 0) / 100.0);
            $adminIncome += $fee;
        }
        $addIncome('Administrative Fees (Qard Hasan)', $adminIncome);

        // Manual Income Entries (admin-entered)
        if (Schema::hasTable('income_entries')) {
            $manualIncomes = IncomeEntry::query()
                ->whereBetween('date', [$fromDate->toDateString(), $toDate->toDateString()])
                ->get();
            $incomeByCategory = [];
            foreach ($manualIncomes as $mi) {
                $cat = $mi->category ?: 'Uncategorized';
                $incomeByCategory[$cat] = ($incomeByCategory[$cat] ?? 0) + (float) $mi->amount;
            }
            foreach ($incomeByCategory as $cat => $sum) {
                $label = $cat === 'Uncategorized' ? 'Manual Income (Uncategorized)' : "Manual Income - {$cat}";
                $addIncome($label, $sum);
            }
        }

        // Manual Expense Entries (admin-entered)
        if (Schema::hasTable('expense_entries')) {
            $manualExpenses = ExpenseEntry::query()
                ->whereBetween('date', [$fromDate->toDateString(), $toDate->toDateString()])
                ->get();
            $expenseByCategory = [];
            foreach ($manualExpenses as $me) {
                $cat = $me->category ?: 'Uncategorized';
                $expenseByCategory[$cat] = ($expenseByCategory[$cat] ?? 0) + (float) $me->amount;
            }
            foreach ($expenseByCategory as $cat => $sum) {
                $label = $cat === 'Uncategorized' ? 'Expense (Uncategorized)' : "Expense - {$cat}";
                $addExpense($label, $sum);
            }
        }

        $totalIncome = array_sum(array_column($incomeLines, 'amount'));
        $totalExpense = array_sum(array_column($expenseLines, 'amount'));

        $surplus = round($totalIncome - $totalExpense, 2);

        return [
            'from' => $fromDate->toDateString(),
            'to' => $toDate->toDateString(),
            'income' => $incomeLines,
            'expenses' => $expenseLines,
            'total_income' => round($totalIncome, 2),
            'total_expense' => round($totalExpense, 2),
            'surplus' => $surplus,
        ];
    }

    /**
     * Build an Appropriation Account for the date range.
     * Uses the Income & Expenditure surplus and applies optional ratios from env('APPROPRIATION_RATIOS').
     * The env should be a JSON array of objects: [{"name":"Statutory Reserve","percent":25}, ...]
     */
    public function buildAppropriationAccount(?string $from = null, ?string $to = null): array
    {
        $ie = $this->buildIncomeAndExpenditure($from, $to);
        $surplus = (float) ($ie['surplus'] ?? 0);

        $appropriations = [];
        $totalAppropriated = 0.0;
        $ratios = null;
        $ratiosJson = env('APPROPRIATION_RATIOS');
        if (!empty($ratiosJson)) {
            try {
                $ratios = json_decode($ratiosJson, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $e) {
                $ratios = null;
            }
        }

        if ($surplus > 0 && is_array($ratios)) {
            foreach ($ratios as $r) {
                $pct = isset($r['percent']) ? (float) $r['percent'] : 0.0;
                $name = $r['name'] ?? 'Appropriation';
                if ($pct <= 0) continue;
                $amt = round($surplus * ($pct / 100.0), 2);
                if ($amt <= 0) continue;
                $appropriations[] = ['name' => $name, 'percent' => $pct, 'amount' => $amt];
                $totalAppropriated += $amt;
            }
        }

        $carriedForward = round($surplus - $totalAppropriated, 2);

        return [
            'from' => $ie['from'] ?? $from,
            'to' => $ie['to'] ?? $to,
            'surplus' => round($surplus, 2),
            'appropriations' => $appropriations,
            'total_appropriated' => round($totalAppropriated, 2),
            'carried_forward' => $carriedForward,
        ];
    }
}
