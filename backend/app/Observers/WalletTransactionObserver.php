<?php

namespace App\Observers;

use App\Models\WalletTransaction;
use App\Services\LedgerService;

class WalletTransactionObserver
{
    public function __construct(protected LedgerService $ledgerService)
    {}

    /**
     * Handle the WalletTransaction "created" event.
     */
    public function created(WalletTransaction $tx): void
    {
        try {
            $isCredit = strtolower((string) $tx->type) === 'credit';

            $entries = [];
            if ($isCredit) {
                // Money in: Debit Asset (Bank), Credit Liability (Member Deposits)
                $entries = [
                    ['code' => '1100', 'debit' => $tx->amount], // Bank
                    ['code' => '2200', 'credit' => $tx->amount], // Member Deposits
                ];
            } else {
                // Money out: Debit Liability (Member Deposits), Credit Asset (Bank)
                $entries = [
                    ['code' => '2200', 'debit' => $tx->amount], // Member Deposits
                    ['code' => '1100', 'credit' => $tx->amount], // Bank
                ];
            }

            $journal = $this->ledgerService->recordByCode([
                'date' => $tx->created_at ?? now(),
                'reference' => 'WALLET-' . $tx->id,
                'description' => "Wallet {$tx->type}: {$tx->source} (Ref: {$tx->reference})",
                'created_by' => $tx->user_id,
            ], $entries);

            $tx->updateQuietly(['ledger_journal_id' => $journal->id]);

            // Suspicious action: Large transaction
            if ($tx->amount >= 500000) { // 500k threshold
                $causer = auth()->user() ?? $tx->user;
                activity('suspicious')
                    ->performedOn($tx)
                    ->causedBy($causer)
                    ->withProperties([
                        'amount' => $tx->amount,
                        'type' => $tx->type,
                        'source' => $tx->source,
                        'reference' => $tx->reference,
                        'ip' => request()->ip(),
                    ])
                    ->log("Large wallet transaction detected");
            }
        } catch (\Exception $e) {
            \Log::error("Failed to record wallet transaction in ledger: " . $e->getMessage());
        }
    }
}
