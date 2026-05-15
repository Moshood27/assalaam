<?php

namespace App\Jobs;

use App\Models\WalletTransaction;
use App\Services\LedgerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWalletTransactionSideEffects implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $transactionId)
    {
        $this->onQueue('transactions');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $tx = WalletTransaction::find($this->transactionId);
            if (!$tx) return;

            // Record in Ledger
            if (!$tx->ledger_journal_id) {
                $ledger = app(LedgerService::class);
                $journal = strtolower((string) $tx->type) === 'credit'
                    ? $ledger->recordWalletCredit($tx)
                    : $ledger->recordWalletDebit($tx);
                $tx->updateQuietly(['ledger_journal_id' => $journal->id]);
            }
        } catch (\Throwable $e) {
            Log::error('ProcessWalletTransactionSideEffects job failed', [
                'tx_id' => $this->transactionId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
