<?php

namespace App\Observers;

use App\Models\IncomeEntry;
use App\Services\LedgerService;

class IncomeEntryObserver
{
    public function __construct(protected LedgerService $ledgerService)
    {}

    /**
     * Handle the IncomeEntry "created" event.
     */
    public function created(IncomeEntry $incomeEntry): void
    {
        try {
            $journal = $this->ledgerService->recordByCode([
                'date' => $incomeEntry->date ?? now(),
                'reference' => 'INCOME-' . $incomeEntry->id,
                'description' => "Income: {$incomeEntry->title} ({$incomeEntry->category})",
                'created_by' => $incomeEntry->created_by,
            ], [
                ['code' => '1100', 'debit' => $incomeEntry->amount], // Bank
                ['code' => '4000', 'credit' => $incomeEntry->amount], // Service Income
            ]);

            $incomeEntry->updateQuietly(['ledger_journal_id' => $journal->id]);
        } catch (\Exception $e) {
            \Log::error("Failed to record income in ledger: " . $e->getMessage());
        }
    }
}
