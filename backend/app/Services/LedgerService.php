<?php

namespace App\Services;

use App\Models\LedgerJournal;
use App\Models\LedgerAccount;
use Illuminate\Support\Facades\DB;
use Exception;

class LedgerService
{
    /**
     * Create a balanced journal entry.
     *
     * @param array $data ['date', 'reference', 'description', 'created_by']
     * @param array $entries [['ledger_account_id', 'debit', 'credit', 'description'], ...]
     * @return LedgerJournal
     * @throws Exception
     */
    public function record(array $data, array $entries): LedgerJournal
    {
        return DB::transaction(function () use ($data, $entries) {
            $journal = LedgerJournal::create([
                'date' => $data['date'] ?? now(),
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            foreach ($entries as $entryData) {
                $journal->entries()->create([
                    'ledger_account_id' => $entryData['ledger_account_id'],
                    'debit' => $entryData['debit'] ?? 0,
                    'credit' => $entryData['credit'] ?? 0,
                    'description' => $entryData['description'] ?? null,
                ]);
            }

            if (!$journal->isBalanced()) {
                throw new Exception("Journal entry is not balanced. Total debits must equal total credits.");
            }

            return $journal;
        });
    }

    /**
     * Record a journal entry using account codes instead of IDs.
     *
     * @param array $data
     * @param array $entries [['code', 'debit', 'credit', 'description'], ...]
     * @return LedgerJournal
     * @throws Exception
     */
    public function recordByCode(array $data, array $entries): LedgerJournal
    {
        $resolvedEntries = array_map(function ($entry) {
            if (isset($entry['code'])) {
                $account = LedgerAccount::where('code', $entry['code'])->first();
                if (!$account) {
                    throw new Exception("Ledger account with code {$entry['code']} not found.");
                }
                $entry['ledger_account_id'] = $account->id;
                unset($entry['code']);
            }
            return $entry;
        }, $entries);

        return $this->record($data, $resolvedEntries);
    }

    /**
     * Get the balance of an account by code.
     */
    public function getBalance(string $code): float
    {
        $account = LedgerAccount::where('code', $code)->firstOrFail();
        return $account->balance;
    }

    /**
     * Record a member contribution.
     * Debit Bank, Credit Member Deposits.
     */
    public function recordContribution(\App\Models\Contribution $contribution): LedgerJournal
    {
        return $this->recordByCode([
            'date' => $contribution->created_at ?? now(),
            'reference' => $contribution->reference,
            'description' => "Contribution from {$contribution->user->name} for {$contribution->scheme->name}",
        ], [
            ['code' => '1100', 'debit' => $contribution->amount, 'description' => 'Bank Deposit'],
            ['code' => '2200', 'credit' => $contribution->amount, 'description' => "Member Deposit ({$contribution->user->membership_number})"],
        ]);
    }

    /**
     * Record a fine payment.
     * Debit Bank (or Member Deposit), Credit Fine Income.
     */
    public function recordFine(\App\Models\Contribution $contribution): LedgerJournal
    {
        return $this->recordByCode([
            'date' => $contribution->created_at ?? now(),
            'reference' => $contribution->reference,
            'description' => "Fine payment from {$contribution->user->name}",
        ], [
            ['code' => '1100', 'debit' => $contribution->amount, 'description' => 'Bank Deposit'],
            ['code' => '4200', 'credit' => $contribution->amount, 'description' => 'Fine Income'],
        ]);
    }

    /**
     * Record a loan disbursement.
     * Debit Loans Receivable, Credit Bank.
     */
    public function recordLoanDisbursement(\App\Models\QardHasan $loan): LedgerJournal
    {
        return $this->recordByCode([
            'date' => $loan->approved_at ?? now(),
            'reference' => $loan->reference,
            'description' => "Qard Hasan Disbursement to {$loan->user->name}",
        ], [
            ['code' => '1300', 'debit' => $loan->amount, 'description' => 'Loan Asset'],
            ['code' => '1100', 'credit' => $loan->amount, 'description' => 'Bank Withdrawal'],
        ]);
    }

    /**
     * Record a loan repayment.
     * Debit Bank, Credit Loans Receivable.
     */
    public function recordLoanRepayment(\App\Models\QardHasanRepayment $repayment): LedgerJournal
    {
        return $this->recordByCode([
            'date' => $repayment->paid_at ?? now(),
            'reference' => $repayment->reference,
            'description' => "Qard Hasan Repayment from {$repayment->qardHasan->user->name}",
        ], [
            ['code' => '1100', 'debit' => $repayment->amount, 'description' => 'Bank Deposit'],
            ['code' => '1300', 'credit' => $repayment->amount, 'description' => 'Loan Asset Reduction'],
        ]);
    }
    /**
     * Record a wallet credit (external funding).
     * Debit Bank, Credit Member Deposits.
     */
    public function recordWalletCredit(\App\Models\WalletTransaction $tx): LedgerJournal
    {
        return $this->recordByCode([
            'date' => $tx->created_at ?? now(),
            'reference' => $tx->reference,
            'description' => "Wallet Credit for {$tx->user->name} via {$tx->source}",
        ], [
            ['code' => '1100', 'debit' => $tx->amount, 'description' => 'Bank Deposit'],
            ['code' => '2200', 'credit' => $tx->amount, 'description' => "Member Deposit ({$tx->user->membership_number})"],
        ]);
    }

    /**
     * Record a wallet debit (external withdrawal).
     * Debit Member Deposits, Credit Bank.
     */
    public function recordWalletDebit(\App\Models\WalletTransaction $tx): LedgerJournal
    {
        return $this->recordByCode([
            'date' => $tx->created_at ?? now(),
            'reference' => $tx->reference,
            'description' => "Wallet Debit for {$tx->user->name} via {$tx->source}",
        ], [
            ['code' => '2200', 'debit' => $tx->amount, 'description' => "Member Withdrawal ({$tx->user->membership_number})"],
            ['code' => '1100', 'credit' => $tx->amount, 'description' => 'Bank Withdrawal'],
        ]);
    }

    /**
     * Record a Takaful contribution.
     * Debit Bank, Credit Takaful Pool Fund.
     */
    public function recordTakafulContribution(\App\Models\TakafulContribution $contribution): LedgerJournal
    {
        return $this->recordByCode([
            'date' => $contribution->created_at ?? now(),
            'reference' => $contribution->reference,
            'description' => "Takaful Contribution from {$contribution->user->name}",
        ], [
            ['code' => '1100', 'debit' => $contribution->amount, 'description' => 'Bank Deposit'],
            ['code' => '2210', 'credit' => $contribution->amount, 'description' => 'Takaful Pool Fund Credit'],
        ]);
    }

    /**
     * Record general income.
     * Debit Bank, Credit Income.
     */
    public function recordIncome(\App\Models\IncomeEntry $income): LedgerJournal
    {
        return $this->recordByCode([
            'date' => $income->date ?? now(),
            'reference' => 'INC-' . $income->id,
            'description' => "Income: {$income->title} ({$income->category})",
        ], [
            ['code' => '1100', 'debit' => $income->amount, 'description' => 'Bank Deposit'],
            ['code' => '4000', 'credit' => $income->amount, 'description' => "Income - {$income->category}"],
        ]);
    }

    /**
     * Record general expense.
     * Debit Expense, Credit Bank.
     */
    public function recordExpense(\App\Models\ExpenseEntry $expense): LedgerJournal
    {
        return $this->recordByCode([
            'date' => $expense->date ?? now(),
            'reference' => $expense->payout_reference ?? 'EXP-' . $expense->id,
            'description' => "Expense: {$expense->title} ({$expense->category})",
        ], [
            ['code' => '5000', 'debit' => $expense->amount, 'description' => "Expense - {$expense->category}"],
            ['code' => '1100', 'credit' => $expense->amount, 'description' => 'Bank Withdrawal'],
        ]);
    }
    /**
     * Record a store order sale (Murabahah).
     * Debit Member Deposits/Cash, Credit Murabahah Income & Inventory/Cost.
     */
    public function recordStoreOrder(\App\Models\StoreOrder $order): LedgerJournal
    {
        return $this->recordByCode([
            'date' => $order->created_at ?? now(),
            'reference' => $order->reference,
            'description' => "Store Order: {$order->reference} from {$order->user->name}",
        ], [
            ['code' => '1100', 'debit' => $order->total_amount, 'description' => 'Bank/Wallet Receipt'],
            ['code' => '4100', 'credit' => $order->total_profit, 'description' => 'Murabahah Profit'],
            ['code' => '1200', 'credit' => $order->total_cost, 'description' => 'Inventory Cost'],
        ]);
    }
}
