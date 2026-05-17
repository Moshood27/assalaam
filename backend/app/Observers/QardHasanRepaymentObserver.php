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

        if ($repayment->status === 'success') {
            try {
                app(\App\Services\AttaqwaScoreService::class)->calculateAndUpdateScore($repayment->qardHasan->user);
            } catch (\Exception $e) {
                \Log::error("Failed to update Attaqwa score: " . $e->getMessage());
            }
        }

        $repayment->qardHasan->recalculatePaidAmount();
    }

    /**
     * Handle the QardHasanRepayment "created" event.
     */
    public function created(QardHasanRepayment $repayment): void
    {
        if ($repayment->status === 'success') {
            if (!$repayment->ledger_journal_id) {
                $this->recordToLedger($repayment);
            }

            try {
                app(\App\Services\AttaqwaScoreService::class)->calculateAndUpdateScore($repayment->qardHasan->user);
            } catch (\Exception $e) {
                \Log::error("Failed to update Attaqwa score: " . $e->getMessage());
            }
        }

        $repayment->qardHasan->recalculatePaidAmount();
    }

    /**
     * Handle the QardHasanRepayment "deleted" event.
     */
    public function deleted(QardHasanRepayment $repayment): void
    {
        $repayment->qardHasan->recalculatePaidAmount();
    }

    protected function recordToLedger(QardHasanRepayment $repayment): void
    {
        try {
            $journal = $this->ledgerService->recordLoanRepayment($repayment);
            $repayment->updateQuietly(['ledger_journal_id' => $journal->id]);
        } catch (\Exception $e) {
            \Log::error("Failed to record loan repayment in ledger: " . $e->getMessage());
        }
    }
}
