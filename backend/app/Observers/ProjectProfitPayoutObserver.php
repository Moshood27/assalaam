<?php

namespace App\Observers;

use App\Models\ProjectProfitPayout;
use App\Services\LedgerService;

class ProjectProfitPayoutObserver
{
    public function __construct(protected LedgerService $ledgerService)
    {}

    /**
     * Handle the ProjectProfitPayout "updated" event.
     */
    public function updated(ProjectProfitPayout $payout): void
    {
        if ($payout->wasChanged('status') && $payout->status === 'paid' && !$payout->ledger_journal_id) {
            $this->recordToLedger($payout);
        }
    }

    /**
     * Handle the ProjectProfitPayout "created" event.
     */
    public function created(ProjectProfitPayout $payout): void
    {
        if ($payout->status === 'paid' && !$payout->ledger_journal_id) {
            $this->recordToLedger($payout);
        }
    }

    protected function recordToLedger(ProjectProfitPayout $payout): void
    {
        try {
            // When profit is paid out to member wallet:
            // Dr Project Profits Payable (2300)
            // Cr Member Deposits (2200)

            $journal = $this->ledgerService->recordByCode([
                'date' => $payout->updated_at ?? now(),
                'reference' => 'PROFIT-PAY-' . $payout->id,
                'description' => "Profit Payout to Member: " . ($payout->user->name ?? 'User #' . $payout->user_id),
                'created_by' => auth()->id(),
            ], [
                ['code' => '2300', 'debit' => $payout->amount], // Profits Payable
                ['code' => '2200', 'credit' => $payout->amount], // Member Deposits (Wallet)
            ]);

            $payout->updateQuietly(['ledger_journal_id' => $journal->id]);
        } catch (\Exception $e) {
            \Log::error("Failed to record project profit payout in ledger: " . $e->getMessage());
        }
    }
}
