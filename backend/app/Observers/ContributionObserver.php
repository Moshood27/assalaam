<?php

namespace App\Observers;

use App\Models\Contribution;
use App\Services\LedgerService;

class ContributionObserver
{
    public function __construct(protected LedgerService $ledgerService)
    {}

    /**
     * Handle the Contribution "updated" event.
     */
    public function updated(Contribution $contribution): void
    {
        if ($contribution->wasChanged('status') && $contribution->status === 'success' && !$contribution->ledger_journal_id) {
            $this->recordToLedger($contribution);
        }
    }

    /**
     * Handle the Contribution "created" event.
     */
    public function created(Contribution $contribution): void
    {
        if ($contribution->status === 'success' && !$contribution->ledger_journal_id) {
            $this->recordToLedger($contribution);
        }
    }

    protected function recordToLedger(Contribution $contribution): void
    {
        try {
            $creditAccount = '2200'; // Default: Member Deposits (Liability)

            if ($contribution->category === 'fine') {
                $creditAccount = '4200'; // Fine Income
            } elseif ($contribution->scheme && str_contains(strtolower($contribution->scheme->name), 'share')) {
                $creditAccount = '3100'; // Member Equity
            }

            $journal = $this->ledgerService->recordByCode([
                'date' => $contribution->created_at ?? now(),
                'reference' => 'CONTRIB-' . $contribution->id,
                'description' => "Contribution: {$contribution->category} (Ref: {$contribution->reference})",
                'created_by' => $contribution->user_id,
            ], [
                ['code' => '1100', 'debit' => $contribution->amount], // Bank
                ['code' => $creditAccount, 'credit' => $contribution->amount],
            ]);

            $contribution->updateQuietly(['ledger_journal_id' => $journal->id]);
        } catch (\Exception $e) {
            \Log::error("Failed to record contribution in ledger: " . $e->getMessage());
        }
    }
}
