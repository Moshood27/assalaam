<?php

namespace App\Observers;

use App\Models\ProjectProfit;
use App\Services\LedgerService;

class ProjectProfitObserver
{
    public function __construct(protected LedgerService $ledgerService)
    {}

    /**
     * Handle the ProjectProfit "created" event.
     */
    public function created(ProjectProfit $profit): void
    {
        try {
            // When profit is declared:
            // Dr Bank/Cash (1100) - Assuming the profit is realized in cash
            // Cr Management Fee Income (4500)
            // Cr Project Profits Payable (2300)

            $journal = $this->ledgerService->recordByCode([
                'date' => $profit->created_at ?? now(),
                'reference' => 'PROFIT-DECL-' . $profit->id,
                'description' => "Profit Declaration for project: " . ($profit->project->name ?? 'Project #' . $profit->project_id),
                'created_by' => auth()->id(),
            ], [
                ['code' => '1100', 'debit' => $profit->gross_profit], // Bank
                ['code' => '4500', 'credit' => $profit->management_fee_amount], // Management Fee
                ['code' => '2300', 'credit' => $profit->net_distributable], // Profits Payable
            ]);

            $profit->updateQuietly(['ledger_journal_id' => $journal->id]);
        } catch (\Exception $e) {
            \Log::error("Failed to record project profit in ledger: " . $e->getMessage());
        }
    }
}
