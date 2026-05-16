<?php

namespace App\Services;

use App\Models\WalletTransaction;
use App\Models\QardHasan;
use App\Models\QardHasanRepayment;
use App\Models\CharityEntry;
use App\Models\IncomeEntry;
use App\Models\ExpenseEntry;
use App\Models\StoreOrder;
use App\Models\ProjectProfit;
use App\Models\User;
use App\Support\DurationHelper;
use App\Models\JuniorAccount;
use App\Models\TakafulPoolEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AccountingReportService
{
    /**
     * Get balances from the Double-Entry Ledger.
     */
    protected function getLedgerBalances(?string $from = null, ?string $to = null): \Illuminate\Support\Collection
    {
        $toDate = $to ? Carbon::parse($to)->endOfDay() : Carbon::now();
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;

        $query = DB::table('ledger_entries')
            ->join('ledger_journals', 'ledger_entries.ledger_journal_id', '=', 'ledger_journals.id')
            ->join('ledger_accounts', 'ledger_entries.ledger_account_id', '=', 'ledger_accounts.id')
            ->select(
                'ledger_accounts.name',
                'ledger_accounts.code',
                'ledger_accounts.type',
                DB::raw('SUM(ledger_entries.debit) as total_debit'),
                DB::raw('SUM(ledger_entries.credit) as total_credit')
            )
            ->where('ledger_journals.date', '<=', $toDate->toDateString());

        if ($fromDate) {
            $query->where('ledger_journals.date', '>=', $fromDate->toDateString());
        }

        return $query->groupBy('ledger_accounts.id', 'ledger_accounts.name', 'ledger_accounts.code', 'ledger_accounts.type')->get();
    }

    /**
     * Build a simple Trial Balance between dates (inclusive).
     * If $from is null, computes from beginning of time up to $to (or now).
     */
    public function buildTrialBalance(?string $from = null, ?string $to = null, float $goldPrice = 0.0): array
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
                } elseif (($wt->source ?? null) === 'store_installment') {
                    // Dr Wallets Payable, Cr Murabahah Receivables
                    $post('Wallets Payable', $amount, 0);
                    $post('Murabahah Receivables', 0, $amount);
                } elseif (($wt->source ?? null) === 'admin_charge') {
                    // Dr Wallets Payable, Cr Income - Administrative Fees
                    $post('Wallets Payable', $amount, 0);
                    $post('Income - Administrative Fees', 0, $amount);
                } else {
                    // Fallback: Dr Wallets Payable, Cr Cash (e.g., withdrawal)
                    $post('Wallets Payable', $amount, 0);
                    $post('Cash & Bank', 0, $amount);
                }
            }
        }

        // Loans disbursed (treat created active/completed as disbursed)
        $loanQuery = QardHasan::query()->whereIn('status', ['active', 'completed', 'defaulted']);
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
            $meQuery = ExpenseEntry::query()
                ->where(function($q) {
                    $q->whereIn('status', ['processed', 'approved'])
                      ->orWhereNull('status');
                });
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

        // Store Profits (Murabahah)
        $storeQuery = StoreOrder::query()->whereIn('status', ['completed', 'paid', 'processing', 'shipped']);
        if ($fromDate) {
            $storeQuery->where('updated_at', '>=', $fromDate);
        }
        $storeQuery->where('updated_at', '<=', $toDate);
        foreach ($storeQuery->get() as $order) {
            $isMurabahah = isset($order->meta['financing']['type']) && $order->meta['financing']['type'] === 'murabaha';
            $profit = (float) $order->total_profit;
            $cost = (float) $order->total_cost;
            $total = (float) $order->total_amount;

            if ($isMurabahah) {
                // Dr Murabahah Receivables, Cr Cash (Cost), Cr Store Profit (Income)
                $post('Murabahah Receivables', $total, 0);
                $post('Cash & Bank', 0, $cost);
                $post('Income - Store Profit', 0, $profit);
            } else {
                // Cash order (Wallet debit)
                // Dr Cash (Total), Cr Store Profit (Income), Cr Cash (Cost) -> Net: Dr Cash (Profit)
                $post('Cash & Bank', $profit, 0);
                $post('Income - Store Profit', 0, $profit);
            }
        }

        // Project Management Fees (Investment ROI)
        $projectQuery = ProjectProfit::query();
        if ($fromDate) {
            $projectQuery->where('created_at', '>=', $fromDate);
        }
        $projectQuery->where('created_at', '<=', $toDate);
        foreach ($projectQuery->get() as $pp) {
            $fee = (float) $pp->management_fee_amount;
            $post('Cash & Bank', $fee, 0);
            $post('Income - Investment ROI', 0, $fee);
        }

        // Member Balances (Snapshot as of $toDate for current liabilities)
        // Note: Trial Balance usually tracks flows, but for a Coop, we often need current state.
        // For a true periodic TB, we'd need a transaction ledger for everything.
        // If we don't have a full ledger for savings/shares/gold, we use the balances as of $toDate.
        // WARNING: This part mixes flows and balances. In a real system, these would come from the Ledger.

        // Total Member Savings, Shares & Other Funds (Current Liabilities)
        $memberStats = User::query()
            ->selectRaw('
                SUM(ordinary_savings) as total_savings,
                SUM(shares_capital) as total_shares,
                SUM(gold_balance) as total_gold,
                SUM(building_balance) as total_building,
                SUM(development_fund_balance) as total_development,
                SUM(agm_balance) as total_agm,
                SUM(loan_repayment_balance) as total_loan_repayment,
                SUM(fine_balance) as total_fine,
                SUM(welfare_balance) as total_welfare,
                SUM(lateness_balance) as total_lateness,
                SUM(stationery_balance) as total_stationery,
                SUM(loan_form_balance) as total_loan_form,
                SUM(others_balance) as total_others,
                SUM(id_card_balance) as total_id_card,
                SUM(emergency_balance) as total_emergency,
                SUM(entrance_balance) as total_entrance,
                SUM(h_savings_balance) as total_h_savings,
                SUM(investment_balance) as total_investment,
                SUM(special_savings_balance) as total_special_savings,
                SUM(group_savings_balance) as total_group_savings
            ')
            ->first();

        $post('Member Savings Payable', 0, (float) $memberStats->total_savings);
        $post('Member Special Savings Payable', 0, (float) $memberStats->total_special_savings);
        $post('Member Shares Payable', 0, (float) $memberStats->total_shares);

        $otherFundsTotal = (float) $memberStats->total_building +
                          (float) $memberStats->total_development +
                          (float) $memberStats->total_agm +
                          (float) $memberStats->total_loan_repayment +
                          (float) $memberStats->total_fine +
                          (float) $memberStats->total_welfare +
                          (float) $memberStats->total_lateness +
                          (float) $memberStats->total_stationery +
                          (float) $memberStats->total_loan_form +
                          (float) $memberStats->total_others +
                          (float) $memberStats->total_id_card +
                          (float) $memberStats->total_emergency +
                          (float) $memberStats->total_entrance +
                          (float) $memberStats->total_h_savings +
                          (float) $memberStats->total_investment +
                          (float) $memberStats->total_group_savings;

        $post('Member Other Funds Payable', 0, $otherFundsTotal);
        $post('Member Gold Payable', 0, (float) $memberStats->total_gold * $goldPrice);
        // Dr Gold Inventory, Cr Member Gold Payable
        $post('Gold Inventory', (float) $memberStats->total_gold * $goldPrice, 0);
        // We'd need an offset for these if we want TB to balance, usually "Opening Balance Equity".
        // But for now, we'll focus on the reporting metrics.

        // Junior Accounts
        $juniorTotal = JuniorAccount::query()->sum('balance');
        $post('Junior Accounts Payable', 0, (float) $juniorTotal);

        // Takaful Pool
        $takafulTotal = TakafulPoolEntry::query()->sum('amount'); // Net balance
        $post('Takaful Pool Fund', 0, (float) $takafulTotal);

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
    public function buildBalanceSheet(?string $asOf = null, float $goldPrice = 0.0): array
    {
        $date = $asOf ? Carbon::parse($asOf)->endOfDay() : Carbon::now();
        $tb = $this->buildTrialBalance(null, $date->toDateString(), $goldPrice);

        $assets = [];
        $liabilities = [];
        $equity = [];

        $push = function (array &$arr, string $name, float $amount) {
            if (abs($amount) <= 0.00001) return;
            $arr[] = ['name' => $name, 'amount' => round($amount, 2)];
        };

        // Determine net balances per account
        foreach ($tb['accounts'] as $name => $row) {
            $net = (float) $row['debit'] - (float) $row['credit'];

            // Assets (Debit balance)
            if (in_array($name, ['Cash & Bank', 'Loans Receivable', 'Investments'])) {
                $push($assets, $name, $net);
            }
            // Liabilities & Equity (Credit balance)
            elseif (in_array($name, [
                'Wallets Payable', 'Member Savings Payable', 'Member Shares Payable',
                'Member Other Funds Payable', 'Junior Accounts Payable',
                'Takaful Pool Fund', 'Charity Fund (Restricted)'
            ])) {
                $push($liabilities, $name, -$net);
            }
            elseif (str_starts_with($name, 'Statutory Reserve') || str_starts_with($name, 'Education Fund')) {
                $push($equity, $name, -$net);
            }
        }

        // Gold Valuation
        if ($goldPrice > 0) {
            $totalGoldWeight = User::sum('gold_balance');
            $goldValuation = $totalGoldWeight * $goldPrice;
            $push($assets, "Gold Holdings ({$totalGoldWeight}g @ {$goldPrice}/g)", $goldValuation);
        }

        $totalAssets = array_sum(array_column($assets, 'amount'));
        $totalLiab = array_sum(array_column($liabilities, 'amount'));
        $totalEquity = array_sum(array_column($equity, 'amount'));

        $surplus = round($totalAssets - ($totalLiab + $totalEquity), 2);

        if (abs($surplus) > 0.00001) {
            $equity[] = ['name' => 'Accumulated Surplus / (Deficit)', 'amount' => $surplus];
            $totalEquity += $surplus;
        }

        return [
            'as_of' => $date->toDateString(),
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_assets' => round($totalAssets, 2),
            'total_liabilities' => round($totalLiab, 2),
            'total_equity' => round($totalEquity, 2),
            'total_liabilities_and_equity' => round($totalLiab + $totalEquity, 2),
        ];
    }

    /**
     * Build a Statement of Cash Flows for the date range.
     */
    public function buildStatementOfCashFlows(?string $from = null, ?string $to = null): array
    {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfYear();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : Carbon::now();

        // Simplified Direct Method
        $operatingInflows = [];
        $operatingOutflows = [];
        $investingActivities = [];
        $financingActivities = [];

        // 1. Operating Activities
        // Inflows: Store profits, Admin fees, Member contributions (if treated as operating cash in)
        $storeProfit = StoreOrder::where('status', 'completed')->whereBetween('updated_at', [$fromDate, $toDate])->sum('total_profit');
        if ($storeProfit > 0) $operatingInflows[] = ['name' => 'Cash from Store Sales (Profit)', 'amount' => (float)$storeProfit];

        $adminFees = 0.0;
        foreach (QardHasan::whereIn('status', ['active', 'completed', 'defaulted'])->whereBetween('created_at', [$fromDate, $toDate])->get() as $l) {
            $adminFees += (float) ($l->admin_fee_flat ?? 0) + ((float) $l->principal_amount) * (((float) $l->admin_fee_pct ?? 0) / 100.0);
        }
        if ($adminFees > 0) $operatingInflows[] = ['name' => 'Loan Administrative Fees', 'amount' => $adminFees];

        // Outflows: Expenses
        $manualExpenses = ExpenseEntry::whereBetween('date', [$fromDate->toDateString(), $toDate->toDateString()])->sum('amount');
        if ($manualExpenses > 0) $operatingOutflows[] = ['name' => 'Operating Expenses', 'amount' => (float)$manualExpenses];

        // 2. Investing Activities
        // Outflows: Loans Disbursed
        $loansDisbursed = QardHasan::whereIn('status', ['active', 'completed', 'defaulted'])->whereBetween('created_at', [$fromDate, $toDate])->sum('principal_amount');
        if ($loansDisbursed > 0) $investingActivities[] = ['name' => 'Qard Hasan Loans Disbursed', 'amount' => -(float)$loansDisbursed];

        // Inflows: Loan Repayments
        $loanRepayments = QardHasanRepayment::whereIn('status', ['success', 'paid', 'completed'])
            ->where(function($q) use ($fromDate, $toDate) {
                $q->whereBetween('paid_at', [$fromDate, $toDate])
                  ->orWhere(fn($sq) => $sq->whereNull('paid_at')->whereBetween('created_at', [$fromDate, $toDate]));
            })->sum('amount');
        if ($loanRepayments > 0) $investingActivities[] = ['name' => 'Qard Hasan Repayments Received', 'amount' => (float)$loanRepayments];

        // 3. Financing Activities
        // Inflows: Wallet Topups (New cash from members)
        $walletTopups = WalletTransaction::where('type', 'credit')->where('source', 'paystack')->whereBetween('created_at', [$fromDate, $toDate])->sum('amount');
        if ($walletTopups > 0) $financingActivities[] = ['name' => 'Member Deposits (Wallet Topups)', 'amount' => (float)$walletTopups];

        // Outflows: Withdrawals
        $withdrawals = WalletTransaction::where('type', 'debit')->where('source', 'withdrawal')->whereBetween('created_at', [$fromDate, $toDate])->sum('amount');
        if ($withdrawals > 0) $financingActivities[] = ['name' => 'Member Withdrawals', 'amount' => -(float)$withdrawals];

        $netOperating = array_sum(array_column($operatingInflows, 'amount')) - array_sum(array_column($operatingOutflows, 'amount'));
        $netInvesting = array_sum(array_column($investingActivities, 'amount'));
        $netFinancing = array_sum(array_column($financingActivities, 'amount'));

        return [
            'from' => $fromDate->toDateString(),
            'to' => $toDate->toDateString(),
            'operating' => [
                'inflows' => $operatingInflows,
                'outflows' => $operatingOutflows,
                'net' => round($netOperating, 2)
            ],
            'investing' => [
                'items' => $investingActivities,
                'net' => round($netInvesting, 2)
            ],
            'financing' => [
                'items' => $financingActivities,
                'net' => round($netFinancing, 2)
            ],
            'net_increase' => round($netOperating + $netInvesting + $netFinancing, 2)
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

        // Store Profits (Murabahah)
        $storeProfit = StoreOrder::where('status', 'completed')
            ->whereBetween('updated_at', [$fromDate, $toDate])
            ->sum('total_profit');
        $addIncome('Store Profit (Murabahah)', (float)$storeProfit);

        // Project Management Fees (Investment ROI)
        $projectFees = ProjectProfit::whereBetween('created_at', [$fromDate, $toDate])
            ->sum('management_fee_amount');
        $addIncome('Investment Management Fees (ROI)', (float)$projectFees);

        // Monthly Administrative Fees
        $monthlyFees = WalletTransaction::where('source', 'admin_charge')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->sum('amount');
        $addIncome('Monthly Administrative Fees', (float)$monthlyFees);

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
                ->where(function($q) {
                    $q->whereIn('status', ['processed', 'approved'])
                      ->orWhereNull('status'); // For legacy entries
                })
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
     * Specific to Cooperatives: Statutory Reserve (25%), Education Fund (2.5%).
     */
    public function buildAppropriationAccount(?string $from = null, ?string $to = null): array
    {
        $ie = $this->buildIncomeAndExpenditure($from, $to);
        $surplus = (float) ($ie['surplus'] ?? 0);

        $appropriations = [];
        $totalAppropriated = 0.0;

        // Use ratios from config or defaults for Nigerian Coop laws
        $ratios = config('cooperative.appropriation.ratios', [
            ['name' => 'Statutory Reserve', 'percent' => 25],
            ['name' => 'Education Fund', 'percent' => 2.5],
            ['name' => 'Dividend to Members', 'percent' => 50],
            ['name' => 'Honorarium to Officers', 'percent' => 10],
        ]);

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

    /**
     * Build Zakat Report.
     */
    public function buildZakatReport(float $goldPrice): array
    {
        $nisabNgn = config('cooperative.zakat.nisab_ngn', 500000);
        $rate = config('cooperative.zakat.rate', 0.025);

        // 1. Cooperative's own Zakat (from assets)
        $tb = $this->buildTrialBalance(null, now()->toDateString(), $goldPrice);
        $cash = $tb['accounts']['Cash & Bank']['debit'] - $tb['accounts']['Cash & Bank']['credit'];
        $murabahah = $tb['accounts']['Murabahah Receivables']['debit'] - $tb['accounts']['Murabahah Receivables']['credit'];
        $goldInv = $tb['accounts']['Gold Inventory']['debit'] - $tb['accounts']['Gold Inventory']['credit'];

        $totalCoopZakatable = $cash + $murabahah + $goldInv;
        $coopZakatDue = $totalCoopZakatable >= $nisabNgn ? $totalCoopZakatable * $rate : 0;

        // 2. Member Zakat Summary (Current due)
        $members = User::whereNotNull('zakat_tracking')->get();
        $memberZakatData = $members->map(function ($user) use ($goldPrice, $nisabNgn, $rate) {
            $baseWealth = $user->zakatBaseWealth($goldPrice);
            return [
                'name' => $user->full_name,
                'membership_number' => $user->membership_number,
                'base_wealth' => $baseWealth,
                'zakat_due' => $baseWealth >= $nisabNgn ? $baseWealth * $rate : 0,
            ];
        });

        // 3. Member Zakat Paid History (Amanah)
        $zakatProject = \App\Models\SadaqahProject::where('name', 'General Zakat Fund')->first();
        $totalPaidZakat = 0.0;
        if ($zakatProject) {
            $totalPaidZakat = (float) \App\Models\SadaqahContribution::where('sadaqah_project_id', $zakatProject->id)
                ->where('status', 'success')
                ->sum('amount');
        }

        return [
            'date' => now()->toDateString(),
            'gold_price' => $goldPrice,
            'nisab_ngn' => $nisabNgn,
            'rate' => $rate * 100 . '%',
            'coop_cash_balance' => round($cash, 2),
            'coop_murabahah_receivables' => round($murabahah, 2),
            'coop_gold_inventory' => round($goldInv, 2),
            'coop_zakatable_total' => round($totalCoopZakatable, 2),
            'coop_zakat_due' => round($coopZakatDue, 2),
            'members_count' => $members->count(),
            'total_member_zakat_due' => round($memberZakatData->sum('zakat_due'), 2),
            'total_collected_zakat' => round($totalPaidZakat, 2),
            'member_details' => $memberZakatData,
        ];
    }

    /**
     * Build Charity Fund Report (Non-Halal Income Disposal).
     */
    public function buildCharityFundReport(?string $from = null, ?string $to = null): array
    {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfYear();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : Carbon::now();

        $entries = CharityEntry::whereBetween('created_at', [$fromDate, $toDate])->get();

        $inflows = $entries->where('amount', '>', 0);
        $outflows = $entries->where('amount', '<', 0);

        return [
            'from' => $fromDate->toDateString(),
            'to' => $toDate->toDateString(),
            'total_inflow' => round($inflows->sum('amount'), 2),
            'total_outflow' => round(abs($outflows->sum('amount')), 2),
            'net_balance' => round($entries->sum('amount'), 2),
            'details' => $entries->map(fn($e) => [
                'date' => $e->created_at->toDateString(),
                'source' => $e->source,
                'amount' => (float)$e->amount,
                'note' => $e->note,
            ]),
        ];
    }

    /**
     * Build Project ROI Report.
     */
    public function buildProjectRoiReport(): array
    {
        $projects = \App\Models\Project::with(['investments', 'profits'])->get();

        return $projects->map(function ($p) {
            $invested = $p->investments->sum('amount');
            $grossProfit = $p->profits->sum('gross_profit');
            $mgtFee = $p->profits->sum('management_fee_amount');
            $netDistributable = $p->profits->sum('net_distributable');

            return [
                'project_name' => $p->name,
                'status' => $p->status,
                'capital_invested' => (float)$invested,
                'gross_profit' => (float)$grossProfit,
                'coop_management_fee' => (float)$mgtFee,
                'net_for_investors' => (float)$netDistributable,
                'roi_percent' => $invested > 0 ? round(($grossProfit / $invested) * 100, 2) : 0,
            ];
        })->toArray();
    }

    /**
     * Build Member Savings Ledger.
     */
    public function buildMemberSavingsLedger(int $userId): array
    {
        $user = User::findOrFail($userId);
        $contributions = \App\Models\Contribution::where('user_id', $userId)
            ->with('scheme')
            ->orderBy('created_at', 'desc')
            ->get();

        $takafulEntries = \App\Models\TakafulPoolEntry::where('user_id', $userId)
            ->where('direction', 'credit')
            ->orderBy('created_at', 'desc')
            ->get();

        $history = $contributions->map(fn($c) => [
            'date' => $c->created_at->toDateString(),
            'scheme' => $c->scheme?->name ?? 'Direct Contribution',
            'type' => $c->type,
            'amount' => (float)$c->amount,
            'status' => $c->status,
        ])->toArray();

        // Add Takaful entries to history
        foreach ($takafulEntries as $te) {
            $history[] = [
                'date' => $te->created_at->toDateString(),
                'scheme' => 'Takaful Welfare Pool',
                'type' => 'Contribution',
                'amount' => (float)$te->amount,
                'status' => 'success',
            ];
        }

        // Sort by date descending
        usort($history, fn($a, $b) => strcmp($b['date'], $a['date']));

        return [
            'member_name' => $user->full_name,
            'membership_number' => $user->membership_number,
            'current_savings' => (float)$user->ordinary_savings,
            'current_shares' => (float)$user->shares_capital,
            'current_gold' => (float)$user->gold_balance,
            'total_takaful_paid' => (float)$takafulEntries->sum('amount'),
            'history' => $history,
        ];
    }

    /**
     * Build Loan (Qard Hasan) & Murabahah Aging Report.
     */
    public function buildLoanAgingReport(): array
    {
        $now = Carbon::now();
        $agingData = [];

        // 1. Qard Hasan Loans
        $loans = QardHasan::whereIn('status', ['active', 'defaulted'])->with('user')->get();
        foreach ($loans as $l) {
            $lastRepayment = QardHasanRepayment::where('qard_hasan_id', $l->id)
                ->whereIn('status', ['success', 'paid', 'completed'])
                ->orderBy('paid_at', 'desc')
                ->first();

            $daysSinceLastPayment = $lastRepayment
                ? (int) abs($now->diffInDays(Carbon::parse($lastRepayment->paid_at)))
                : (int) abs($now->diffInDays($l->created_at));

            $repaid = QardHasanRepayment::where('qard_hasan_id', $l->id)
                ->whereIn('status', ['success', 'paid', 'completed'])
                ->sum('amount');

            $balance = (float)$l->principal_amount - (float)$repaid;

            $agingData[] = [
                'type' => 'Qard Hasan',
                'member' => $l->user->full_name,
                'principal' => (float)$l->principal_amount,
                'repaid' => (float)$repaid,
                'balance' => $balance,
                'days_since_last_payment' => DurationHelper::format($daysSinceLastPayment),
                'status' => $daysSinceLastPayment > 30 ? 'Overdue' : 'Active',
            ];
        }

        // 2. Murabahah Store Orders (Credit)
        $orders = StoreOrder::where('status', 'murabaha_active')->with('user')->get();
        foreach ($orders as $order) {
            $meta = $order->meta;
            $fin = $meta['financing'] ?? null;
            if (!is_array($fin) || ($fin['type'] ?? null) !== 'murabaha') continue;

            $schedule = $fin['schedule'] ?? [];
            $totalPaid = (float)($fin['total_paid'] ?? 0);
            $balance = (float)$order->total_amount - $totalPaid;

            // Find last payment date from schedule
            $lastPaidDate = null;
            foreach ($schedule as $item) {
                if (($item['status'] ?? '') === 'paid' && isset($item['paid_at'])) {
                    $pd = Carbon::parse($item['paid_at']);
                    if (!$lastPaidDate || $pd->gt($lastPaidDate)) {
                        $lastPaidDate = $pd;
                    }
                }
            }

            $daysSinceLastPayment = $lastPaidDate
                ? (int) abs($now->diffInDays($lastPaidDate))
                : (int) abs($now->diffInDays($order->created_at));

            $agingData[] = [
                'type' => 'Murabahah',
                'member' => $order->user->full_name,
                'principal' => (float)$order->total_amount,
                'repaid' => $totalPaid,
                'balance' => $balance,
                'days_since_last_payment' => DurationHelper::format($daysSinceLastPayment),
                'status' => $daysSinceLastPayment > 30 ? 'Overdue' : 'Active',
            ];
        }

        return $agingData;
    }

    /**
     * Build Member Zakat Portfolio Report.
     */
    public function buildMemberZakatPortfolio(?string $from = null, ?string $to = null): array
    {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfYear();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : Carbon::now();

        $zakatProject = \App\Models\SadaqahProject::where('name', 'General Zakat Fund')->first();
        if (!$zakatProject) return [];

        $contributions = \App\Models\SadaqahContribution::where('sadaqah_project_id', $zakatProject->id)
            ->where('status', 'success')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->with('user')
            ->get();

        $summary = $contributions->groupBy('user_id')->map(function ($group) {
            $user = $group->first()->user;
            return [
                'name' => $user?->full_name ?? 'Unknown',
                'membership_number' => $user?->membership_number ?? '-',
                'total_paid' => (float)$group->sum('amount'),
                'last_payment_date' => $group->max('created_at')->toDateString(),
                'count' => $group->count(),
            ];
        })->values()->sortByDesc('total_paid')->toArray();

        return [
            'from' => $fromDate->toDateString(),
            'to' => $toDate->toDateString(),
            'total_zakat_collected' => (float)$contributions->sum('amount'),
            'members_count' => count($summary),
            'portfolio' => $summary,
        ];
    }

    /**
     * Build Detailed Mudarabah/Musharakah Profit Distribution Report.
     */
    public function buildProjectDistributionReport(int $projectId): array
    {
        $project = \App\Models\Project::with(['investments.user', 'profits.payouts.user'])->findOrFail($projectId);

        $investments = $project->investments->map(fn($i) => [
            'member' => $i->user?->full_name,
            'amount' => (float)$i->amount,
            'date' => $i->created_at->toDateString(),
        ]);

        $profits = $project->profits->map(fn($p) => [
            'date' => $p->created_at->toDateString(),
            'gross_profit' => (float)$p->gross_profit,
            'management_fee' => (float)$p->management_fee_amount,
            'net_distributable' => (float)$p->net_distributable,
            'payouts' => $p->payouts->map(fn($pay) => [
                'member' => $pay->user?->full_name,
                'amount' => (float)$pay->amount,
                'status' => $pay->status,
            ]),
        ]);

        return [
            'project_name' => $project->name,
            'description' => $project->description,
            'status' => $project->status,
            'total_invested' => (float)$project->investments->sum('amount'),
            'investments' => $investments,
            'profit_history' => $profits,
        ];
    }

    /**
     * Build Takaful Pool Report.
     */
    public function buildTakafulPoolReport(): array
    {
        $entries = TakafulPoolEntry::with('user')->orderBy('created_at', 'desc')->get();
        $totalContributions = TakafulPoolEntry::where('direction', 'credit')->sum('amount');
        $totalClaims = TakafulPoolEntry::where('direction', 'debit')->sum('amount');

        return [
            'total_contributions' => (float)$totalContributions,
            'total_claims_paid' => (float)$totalClaims,
            'net_pool_balance' => (float)($totalContributions - $totalClaims),
            'recent_activity' => $entries->take(20)->map(fn($e) => [
                'date' => $e->created_at->toDateString(),
                'member' => $e->user?->full_name ?? 'System',
                'amount' => (float)$e->amount,
                'type' => $e->direction === 'credit' ? 'Contribution' : 'Claim/Payout',
            ]),
        ];
    }

    /**
     * Build Gold Savings Valuation Report.
     */
    public function buildGoldSavingsReport(float $goldPrice): array
    {
        $users = User::where('gold_balance', '>', 0)->get();
        $totalWeight = $users->sum('gold_balance');

        return [
            'current_gold_price' => $goldPrice,
            'total_weight_grams' => (float)$totalWeight,
            'total_market_value' => round($totalWeight * $goldPrice, 2),
            'top_holders' => $users->sortByDesc('gold_balance')->take(10)->map(fn($u) => [
                'name' => $u->full_name,
                'weight' => (float)$u->gold_balance,
                'value' => round($u->gold_balance * $goldPrice, 2),
            ])->values(),
        ];
    }

    /**
     * Build Vendor Settlement Report.
     */
    public function buildVendorSettlementReport(): array
    {
        $vendors = \App\Models\Vendor::with('owner')->get();

        return $vendors->map(function ($v) {
            $totalSales = \App\Models\StoreOrderItem::where('vendor_id', $v->id)->sum('total_amount');
            $vendorEarnings = \App\Models\StoreOrderItem::where('vendor_id', $v->id)->sum('vendor_amount');
            $coopCommission = (float)$totalSales - (float)$vendorEarnings;

            return [
                'vendor_name' => $v->name,
                'owner' => $v->owner?->full_name,
                'total_sales' => (float)$totalSales,
                'vendor_payouts' => (float)$vendorEarnings,
                'coop_commission' => $coopCommission,
            ];
        })->toArray();
    }

    /**
     * Build Attendance & Fine Summary Report.
     */
    public function buildAttendanceReport(?string $from = null, ?string $to = null): array
    {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfYear();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : Carbon::now();

        $meetings = \App\Models\Meeting::whereBetween('held_at', [$fromDate, $toDate])
            ->with(['attendanceRecords.user'])
            ->get();

        return $meetings->map(function ($m) {
            $present = $m->attendanceRecords->where('status', 'present')->count();
            $absent = $m->attendanceRecords->where('status', 'absent')->count();
            $fines = $m->attendanceRecords->sum('fine_amount');

            return [
                'meeting_title' => $m->title,
                'date' => $m->held_at->toDateString(),
                'present_count' => $present,
                'absent_count' => $absent,
                'total_fines' => (float)$fines,
            ];
        })->toArray();
    }

    /**
     * Build Loan Analysis Report for AT-TQWA C.I.C.D.
     */
    public function buildLoanAnalysisReport(?int $branchId = null, ?string $dateStr = null, ?string $search = null): array
    {
        $toDate = $dateStr ? Carbon::parse($dateStr)->endOfMonth() : Carbon::now()->endOfMonth();
        return $this->generateLoanAnalysisData($toDate, $branchId, $search);
    }

    public function buildMemberLoanAnalysisReport(\App\Models\User $user, ?string $dateStr = null): array
    {
        $toDate = $dateStr ? Carbon::parse($dateStr)->endOfMonth() : Carbon::now()->endOfMonth();
        return $this->generateLoanAnalysisData($toDate, null, null, $user->id);
    }

    protected function generateLoanAnalysisData(Carbon $toDate, ?int $branchId = null, ?string $search = null, ?int $userId = null): array
    {
        $monthStr = $toDate->format('F');
        $yearStr = $toDate->format('Y');

        $savingsSchemes = \App\Models\Scheme::whereIn('name', ['Savings', 'Shares', 'Special Savings', 'Ordinary Savings', 'Share Capital'])->pluck('id')->toArray();

        // Get all active, defaulted or recently completed loans as of that date
        $loans = \App\Models\QardHasan::with(['user.branch', 'user.contributions' => function($q) use ($toDate, $savingsSchemes) {
            $q->where('status', 'success')
                ->where('created_at', '<=', $toDate)
                ->whereIn('scheme_id', $savingsSchemes);
        }])
            ->where('created_at', '<=', $toDate)
            ->when($branchId, function($q) use ($branchId) {
                $q->whereHas('user', fn($u) => $u->where('branch_id', $branchId));
            })
            ->when($userId, function($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->when($search, function($q) use ($search) {
                $q->whereHas('user', function($u) use ($search) {
                    $u->where('surname', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('other_names', 'like', "%{$search}%")
                        ->orWhere('membership_number', 'like', "%{$search}%");
                });
            })
            ->whereIn('status', ['active', 'defaulted', 'completed'])
            ->get();

        $rows = [];
        $sn = 1;

        $totals = [
            'loan_granted' => 0.0,
            'amount_repaid' => 0.0,
            'expected_amount_to_pay' => 0.0,
            'amount_defaulted' => 0.0,
            'loan_balance' => 0.0,
            'savings_balance' => 0.0,
        ];

        foreach ($loans as $loan) {
            $user = $loan->user;
            if (!$user) continue;

            $principal = (float)$loan->principal_amount;
            $paid = (float)$loan->paid_amount;
            $balance = (float)$loan->remaining_principal;

            $expectedToDate = (float)$loan->getExpectedAmountToDate($toDate);
            $overdue = (float)$loan->getOverdueAmount($toDate);
            $defaultStartDate = $loan->getDefaultStartDate($toDate);

            $periodOfDefault = 'None';
            if ($defaultStartDate) {
                $days = (int) abs($toDate->diffInDays($defaultStartDate));
                $formattedDuration = DurationHelper::format($days);
                $periodOfDefault = $defaultStartDate->format('d-m-Y') . " ({$formattedDuration})";
            }

            $savingsBalance = (float)$user->contributions->sum('amount');

            $rows[] = [
                'sn' => $sn++,
                'member_name' => $user->full_name,
                'branch_name' => optional($user->branch)->name,
                'date_granted' => $loan->received_at ?: ($loan->approved_at ?: $loan->created_at),
                'loan_granted' => $principal,
                'amount_repaid' => $paid,
                'expected_amount_to_pay' => $expectedToDate,
                'amount_defaulted' => $overdue,
                'loan_balance' => $balance,
                'savings_balance' => $savingsBalance,
                'phone_number' => $user->phone,
                'period_of_default' => $periodOfDefault,
            ];

            $totals['loan_granted'] += $principal;
            $totals['amount_repaid'] += $paid;
            $totals['expected_amount_to_pay'] += $expectedToDate;
            $totals['amount_defaulted'] += $overdue;
            $totals['loan_balance'] += $balance;
            $totals['savings_balance'] += $savingsBalance;
        }

        return [
            'rows' => $rows,
            'totals' => $totals,
            'month' => $monthStr,
            'year' => $yearStr,
            'cooperative_name' => 'AT-TAQWA C.I.C.S.',
        ];
    }

    /**
     * Build Branch-by-Branch Outstanding Qard Hasan Report.
     */
    public function buildBranchQardHasanReport(?int $branchId = null, ?string $from = null, ?string $to = null, bool $onlyDefaulted = false): array
    {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDate = $to ? Carbon::parse($to)->endOfDay() : null;

        $branches = \App\Models\Branch::when($branchId, fn($q) => $q->where('id', $branchId))
            ->with(['users.qardHasans' => function ($query) use ($fromDate, $toDate, $onlyDefaulted) {
                $query->whereIn('status', ['active', 'defaulted']);

                if ($onlyDefaulted) {
                    $query->whereNotNull('defaulted_at')
                        ->where('defaulted_at', '<=', now());
                }

                if ($fromDate) {
                    $query->where('created_at', '>=', $fromDate);
                }
                if ($toDate) {
                    $query->where('created_at', '<=', $toDate);
                }
            }, 'users.qardHasans.repayments' => function($q) {
                $q->whereIn('status', ['success', 'paid', 'completed'])->orderBy('paid_at', 'desc');
            }])->get();

        $report = [
            'branches' => [],
            'grand_total_principal' => 0,
            'grand_total_paid' => 0,
            'grand_total_overdue' => 0,
            'grand_total_outstanding' => 0,
            'grand_total_loans_count' => 0,
            'from' => $from,
            'to' => $to,
        ];

        foreach ($branches as $branch) {
            $branchData = [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'loans' => [],
                'total_principal' => 0,
                'total_paid' => 0,
                'total_overdue' => 0,
                'total_outstanding' => 0,
            ];

            foreach ($branch->users as $user) {
                foreach ($user->qardHasans as $loan) {
                    $outstanding = max(0, (float)$loan->principal_amount - (float)$loan->paid_amount);
                    if ($outstanding > 0) {
                        $lastPayment = $loan->repayments->first();
                        $overdue = (float)$loan->getOverdueAmount();

                        $branchData['loans'][] = [
                            'member_name' => $user->full_name,
                            'loan_id' => $loan->qard_id_string,
                            'principal' => (float)$loan->principal_amount,
                            'paid' => (float)$loan->paid_amount,
                            'outstanding' => $outstanding,
                            'overdue' => $overdue,
                            'status' => ($loan->defaulted_at && $loan->defaulted_at->year > 1970 && $loan->defaulted_at->lte(now())) ? 'DEFAULTED' : $loan->status,
                            'last_payment_date' => $lastPayment ? $lastPayment->paid_at : null,
                        ];
                        $branchData['total_principal'] += (float)$loan->principal_amount;
                        $branchData['total_paid'] += (float)$loan->paid_amount;
                        $branchData['total_overdue'] += $overdue;
                        $branchData['total_outstanding'] += $outstanding;
                    }
                }
            }

            if (!empty($branchData['loans'])) {
                $report['branches'][] = $branchData;
                $report['grand_total_principal'] += $branchData['total_principal'];
                $report['grand_total_paid'] += $branchData['total_paid'];
                $report['grand_total_overdue'] += $branchData['total_overdue'];
                $report['grand_total_outstanding'] += $branchData['total_outstanding'];
                $report['grand_total_loans_count'] += count($branchData['loans']);
            }
        }

        return $report;
    }

    /**
     * Build Branch-by-Branch Contribution Report.
     */
    public function buildBranchContributionReport(?int $branchId = null, ?string $from = null, ?string $to = null): array
    {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDate = $to ? Carbon::parse($to)->endOfDay() : null;

        $branches = \App\Models\Branch::when($branchId, fn($q) => $q->where('id', $branchId))
            ->with(['users.contributions' => function ($query) use ($fromDate, $toDate) {
                $query->whereIn('status', ['success', 'paid', 'completed']);
                if ($fromDate) {
                    $query->where('created_at', '>=', $fromDate);
                }
                if ($toDate) {
                    $query->where('created_at', '<=', $toDate);
                }
            }])->get();

        $report = [
            'branches' => [],
            'grand_total_amount' => 0,
            'grand_total_members_count' => 0,
            'from' => $from,
            'to' => $to,
        ];

        foreach ($branches as $branch) {
            $branchData = [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'members' => [],
                'total_amount' => 0,
            ];

            foreach ($branch->users as $user) {
                $userTotal = $user->contributions->sum('amount');

                if ($userTotal > 0) {
                    $branchData['members'][] = [
                        'member_name' => $user->full_name,
                        'membership_number' => $user->membership_number,
                        'total_contributed' => (float)$userTotal,
                        'last_contribution_date' => $user->contributions->max('created_at'),
                    ];
                    $branchData['total_amount'] += (float)$userTotal;
                }
            }

            if (!empty($branchData['members'])) {
                // Sort members by amount descending
                usort($branchData['members'], fn($a, $b) => $b['total_contributed'] <=> $a['total_contributed']);

                $report['branches'][] = $branchData;
                $report['grand_total_amount'] += $branchData['total_amount'];
                $report['grand_total_members_count'] += count($branchData['members']);
            }
        }

        return $report;
    }

    /**
     * Build Branch-by-Branch Member Balances Report (Savings, Shares, Gold, etc).
     */
    public function buildBranchMemberBalancesReport(?int $branchId = null, float $goldPrice = 0.0): array
    {
        $branches = \App\Models\Branch::when($branchId, fn($q) => $q->where('id', $branchId))
            ->with(['users'])
            ->get();

        $report = [
            'branches' => [],
            'grand_total_savings' => 0,
            'grand_total_special_savings' => 0,
            'grand_total_shares' => 0,
            'grand_total_gold_weight' => 0,
            'grand_total_gold_value' => 0,
            'grand_total_other' => 0,
            'grand_total_members_count' => 0,
        ];

        foreach ($branches as $branch) {
            $branchData = [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'members' => [],
                'total_savings' => 0,
                'total_special_savings' => 0,
                'total_shares' => 0,
                'total_gold_weight' => 0,
                'total_gold_value' => 0,
                'total_other' => 0,
            ];

            foreach ($branch->users as $user) {
                $otherTotal = (float)$user->building_balance +
                              (float)$user->development_fund_balance +
                              (float)$user->agm_balance +
                              (float)$user->loan_repayment_balance +
                              (float)$user->fine_balance +
                              (float)$user->welfare_balance +
                              (float)$user->lateness_balance +
                              (float)$user->stationery_balance +
                              (float)$user->loan_form_balance +
                              (float)$user->others_balance +
                              (float)$user->id_card_balance +
                              (float)$user->emergency_balance +
                              (float)$user->entrance_balance +
                              (float)$user->h_savings_balance +
                              (float)$user->investment_balance +
                              (float)$user->group_savings_balance;

                $hasBalance = (float)$user->ordinary_savings > 0 ||
                              (float)$user->special_savings_balance > 0 ||
                              (float)$user->shares_capital > 0 ||
                              (float)$user->gold_balance > 0 ||
                              $otherTotal > 0;

                if ($hasBalance) {
                    $goldValue = (float)$user->gold_balance * $goldPrice;
                    $branchData['members'][] = [
                        'member_name' => $user->full_name,
                        'membership_number' => $user->membership_number,
                        'savings' => (float)$user->ordinary_savings,
                        'special_savings' => (float)$user->special_savings_balance,
                        'shares' => (float)$user->shares_capital,
                        'gold_weight' => (float)$user->gold_balance,
                        'gold_value' => $goldValue,
                        'other_funds' => $otherTotal,
                        'total_wealth' => (float)$user->ordinary_savings + (float)$user->special_savings_balance + (float)$user->shares_capital + $goldValue + $otherTotal,
                    ];
                    $branchData['total_savings'] += (float)$user->ordinary_savings;
                    $branchData['total_special_savings'] += (float)$user->special_savings_balance;
                    $branchData['total_shares'] += (float)$user->shares_capital;
                    $branchData['total_gold_weight'] += (float)$user->gold_balance;
                    $branchData['total_gold_value'] += $goldValue;
                    $branchData['total_other'] += $otherTotal;
                }
            }

            if (!empty($branchData['members'])) {
                // Sort by total wealth descending
                usort($branchData['members'], fn($a, $b) => $b['total_wealth'] <=> $a['total_wealth']);

                $report['branches'][] = $branchData;
                $report['grand_total_savings'] += $branchData['total_savings'];
                $report['grand_total_special_savings'] += $branchData['total_special_savings'];
                $report['grand_total_shares'] += $branchData['total_shares'];
                $report['grand_total_gold_weight'] += $branchData['total_gold_weight'];
                $report['grand_total_gold_value'] += $branchData['total_gold_value'];
                $report['grand_total_other'] += $branchData['total_other'];
                $report['grand_total_members_count'] += count($branchData['members']);
            }
        }

        return $report;
    }

    /**
     * Build Branch-by-Branch Users Report.
     */
    public function buildUsersByBranchReport(?int $branchId = null): array
    {
        $branches = \App\Models\Branch::when($branchId, fn($q) => $q->where('id', $branchId))
            ->with(['users'])
            ->get();

        $report = [
            'branches' => [],
            'grand_total_members_count' => 0,
        ];

        foreach ($branches as $branch) {
            $branchData = [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'members' => [],
            ];

            foreach ($branch->users as $user) {
                $branchData['members'][] = [
                    'member_name' => $user->full_name,
                    'membership_number' => $user->membership_number,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'status' => $user->approval_status,
                    'joined_at' => $user->created_at?->format('Y-m-d'),
                ];
            }

            if (!empty($branchData['members'])) {
                // Sort by name
                usort($branchData['members'], fn($a, $b) => strcmp($a['member_name'], $b['member_name']));

                $report['branches'][] = $branchData;
                $report['grand_total_members_count'] += count($branchData['members']);
            }
        }

        return $report;
    }

    /**
     * Build Sharia Audit Report Summary.
     */
    public function buildShariaAuditReport(?string $from = null, ?string $to = null): array
    {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfYear();
        $toDate = $to ? Carbon::parse($to)->endOfDay() : Carbon::now();

        $logs = \App\Models\ShariahAuditLog::whereBetween('created_at', [$fromDate, $toDate])->get();

        // Murabahah Summary (Store orders with financing)
        $murabahahOrders = \App\Models\StoreOrder::whereBetween('created_at', [$fromDate, $toDate])
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) {
                $q->whereJsonContains('meta->financing->type', 'murabaha')
                    ->orWhere('status', 'like', 'murabaha_%');
            })
            ->get();

        $totalMurabahahValue = $murabahahOrders->sum('total_amount');
        $totalMurabahahProfit = $murabahahOrders->sum('total_profit');

        // Project Summary (Mudarabah/Musharakah)
        $projects = \App\Models\Project::whereBetween('created_at', [$fromDate, $toDate])->get();
        $totalProjectCapital = $projects->sum('capital_goal');

        // Takaful Settlement Summary
        $takafulPayouts = \App\Models\TakafulPoolEntry::whereBetween('created_at', [$fromDate, $toDate])
            ->where('direction', 'debit')
            ->get();

        // Zakat Distribution Summary
        $charityDisbursements = \App\Models\CharityEntry::whereBetween('created_at', [$fromDate, $toDate])
            ->where('amount', '<', 0)
            ->where('status', 'processed')
            ->get();

        return [
            'from' => $fromDate->toDateString(),
            'to' => $toDate->toDateString(),
            'total_audits' => $logs->count(),
            'murabahah' => [
                'count' => $murabahahOrders->count(),
                'total_value' => (float)$totalMurabahahValue,
                'total_profit' => (float)$totalMurabahahProfit,
            ],
            'projects' => [
                'count' => $projects->count(),
                'total_capital' => (float)$totalProjectCapital,
            ],
            'takaful' => [
                'count' => $takafulPayouts->count(),
                'total_amount' => (float)$takafulPayouts->sum('amount'),
            ],
            'charity_disbursements' => [
                'count' => $charityDisbursements->count(),
                'total_amount' => abs((float)$charityDisbursements->sum('amount')),
            ],
            'actions_summary' => $logs->groupBy('action')->map(fn($group) => $group->count()),
            'recent_logs' => $logs->take(50)->map(fn($l) => [
                'date' => $l->created_at->toDateTimeString(),
                'action' => $l->action,
                'payload' => $l->payload,
            ]),
        ];
    }
    /**
     * Build Branch-by-Branch Member Schemes Report.
     * Shows a matrix of members and their total contributions per scheme.
     */
    public function buildBranchSchemeReport(?int $branchId = null, ?string $from = null, ?string $to = null): array
    {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDate = $to ? Carbon::parse($to)->endOfDay() : null;

        $schemes = \App\Models\Scheme::where('active', true)->get();

        $branches = \App\Models\Branch::when($branchId, fn($q) => $q->where('id', $branchId))
            ->with(['users.contributions' => function ($query) use ($fromDate, $toDate) {
                $query->whereIn('status', ['success', 'paid', 'completed']);
                if ($fromDate) {
                    $query->where('created_at', '>=', $fromDate);
                }
                if ($toDate) {
                    $query->where('created_at', '<=', $toDate);
                }
            }])->get();

        $report = [
            'branches' => [],
            'schemes' => $schemes->map(fn($s) => ['id' => $s->id, 'name' => $s->name])->toArray(),
            'grand_totals' => [], // scheme_id => amount
            'grand_total_all' => 0,
            'grand_total_members_count' => 0,
            'from' => $from,
            'to' => $to,
        ];

        foreach ($schemes as $s) {
            $report['grand_totals'][$s->id] = 0;
        }

        foreach ($branches as $branch) {
            $branchData = [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'members' => [],
                'totals' => [], // scheme_id => amount
                'branch_total' => 0,
            ];

            foreach ($schemes as $s) {
                $branchData['totals'][$s->id] = 0;
            }

            foreach ($branch->users as $user) {
                $memberSchemes = [];
                $memberTotal = 0;
                $hasContribution = false;

                $userContributions = $user->contributions->groupBy('scheme_id');

                foreach ($schemes as $s) {
                    $sum = $userContributions->has($s->id) ? $userContributions->get($s->id)->sum('amount') : 0;
                    $memberSchemes[$s->id] = (float)$sum;
                    $memberTotal += (float)$sum;

                    $branchData['totals'][$s->id] += (float)$sum;
                    $report['grand_totals'][$s->id] += (float)$sum;

                    if ($sum > 0) $hasContribution = true;
                }

                if ($hasContribution) {
                    $branchData['members'][] = [
                        'member_name' => $user->full_name,
                        'membership_number' => $user->membership_number,
                        'schemes' => $memberSchemes,
                        'total' => $memberTotal,
                    ];
                    $branchData['branch_total'] += $memberTotal;
                    $report['grand_total_all'] += $memberTotal;
                }
            }

            if (!empty($branchData['members'])) {
                // Sort members by total amount descending
                usort($branchData['members'], fn($a, $b) => $b['total'] <=> $a['total']);
                $report['branches'][] = $branchData;
                $report['grand_total_members_count'] += count($branchData['members']);
            }
        }

        return $report;
    }
}
