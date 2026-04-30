<?php

namespace App\Observers;

use App\Models\QardHasanRepayment;
use App\Services\LedgerService;

class QardHasanRepaymentObserver
{
    public function __construct(protected LedgerService $ledgerService)
    {}

    /**
     * Handle the QardHasanRepayment "updated" event.
     */
    public function updated(QardHasanRepayment $repayment): void
    {
        if ($repayment->wasChanged('status') && $repayment->status === 'success' && !$repayment->ledger_journal_id) {
            $this->recordToLedger($repayment);
        }
    }

    /**
     * Handle the QardHasanRepayment "created" event.
     */
    public function created(QardHasanRepayment $repayment): void
    {
        if ($repayment->status === 'success' && !$repayment->ledger_journal_id) {
            $this->recordToLedger($repayment);
        }
    }

    protected function recordToLedger(QardHasanRepayment $repayment): void
    {
        try {
            $journal = $this->ledgerService->recordByCode([
                'date' => $repayment->paid_at ?? now(),
                'reference' => 'LOAN-REPAY-' . $repayment->id,
                'description' => "Loan Repayment: Qard Hasan #{$repayment->qard_hasan_id} (Ref: {$repayment->reference})",
                'created_by' => $repayment->qardHasan->user_id,
            ], [
                ['code' => '1100', 'debit' => $repayment->amount], // Bank
                ['code' => '1300', 'credit' => $repayment->amount], // Loans Receivable
            ]);

            $repayment->updateQuietly(['ledger_journal_id' => $journal->id]);
        } catch (\Exception $e) {
            \Log::error("Failed to record loan repayment in ledger: " . $e->getMessage());
        }
    }
}
