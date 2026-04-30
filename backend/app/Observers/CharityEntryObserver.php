<?php

namespace App\Observers;

use App\Models\CharityEntry;
use App\Services\LedgerService;

class CharityEntryObserver
{
    public function __construct(protected LedgerService $ledgerService)
    {}

    /**
     * Handle the CharityEntry "updated" event.
     */
    public function updated(CharityEntry $charityEntry): void
    {
        if ($charityEntry->wasChanged('status') && $charityEntry->status === 'processed' && !$charityEntry->ledger_journal_id) {
            $this->recordToLedger($charityEntry);
        }
    }

    /**
     * Handle the CharityEntry "created" event.
     */
    public function created(CharityEntry $charityEntry): void
    {
        if ($charityEntry->status === 'processed' && !$charityEntry->ledger_journal_id) {
            $this->recordToLedger($charityEntry);
        }
    }

    protected function recordToLedger(CharityEntry $charityEntry): void
    {
        try {
            $journal = $this->ledgerService->recordByCode([
                'date' => $charityEntry->processed_at ?? now(),
                'reference' => 'CHARITY-' . $charityEntry->id,
                'description' => "Charity: {$charityEntry->source} ({$charityEntry->note})",
                'created_by' => $charityEntry->user_id,
            ], [
                ['code' => '5200', 'debit' => $charityEntry->amount], // Charity Expenses
                ['code' => '1100', 'credit' => $charityEntry->amount], // Bank
            ]);

            $charityEntry->updateQuietly(['ledger_journal_id' => $journal->id]);
        } catch (\Exception $e) {
            \Log::error("Failed to record charity in ledger: " . $e->getMessage());
        }
    }
}
