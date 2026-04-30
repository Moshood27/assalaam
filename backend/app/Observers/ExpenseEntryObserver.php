<?php

namespace App\Observers;

use App\Models\ExpenseEntry;
use App\Services\LedgerService;

class ExpenseEntryObserver
{
    public function __construct(protected LedgerService $ledgerService)
    {}

    /**
     * Handle the ExpenseEntry "updated" event.
     */
    public function updated(ExpenseEntry $expenseEntry): void
    {
        if ($expenseEntry->wasChanged('status') && $expenseEntry->status === 'approved' && !$expenseEntry->ledger_journal_id) {
            $this->recordToLedger($expenseEntry);
        }
    }

    /**
     * Handle the ExpenseEntry "created" event.
     */
    public function created(ExpenseEntry $expenseEntry): void
    {
        if ($expenseEntry->status === 'approved' && !$expenseEntry->ledger_journal_id) {
            $this->recordToLedger($expenseEntry);
        }
    }

    protected function recordToLedger(ExpenseEntry $expenseEntry): void
    {
        try {
            $debitAccount = '5000'; // Default: Operating Expenses

            if (strtolower((string)$expenseEntry->category) === 'charity') {
                $debitAccount = '2220'; // Charity Fund (Liability/Restricted)
            } elseif (strtolower((string)$expenseEntry->category) === 'administrative') {
                $debitAccount = '5100'; // Administrative Fees (Expense)
            }

            $journal = $this->ledgerService->recordByCode([
                'date' => $expenseEntry->date ?? now(),
                'reference' => 'EXPENSE-' . $expenseEntry->id,
                'description' => "Expense: {$expenseEntry->title} ({$expenseEntry->category})",
                'created_by' => $expenseEntry->approved_by ?? $expenseEntry->created_by,
            ], [
                ['code' => $debitAccount, 'debit' => $expenseEntry->amount],
                ['code' => '1100', 'credit' => $expenseEntry->amount], // Bank
            ]);

            $expenseEntry->updateQuietly(['ledger_journal_id' => $journal->id]);
        } catch (\Exception $e) {
            \Log::error("Failed to record expense in ledger: " . $e->getMessage());
        }
    }
}
