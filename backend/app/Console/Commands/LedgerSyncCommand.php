<?php

namespace App\Console\Commands;

use App\Models\Contribution;
use App\Models\ExpenseEntry;
use App\Models\IncomeEntry;
use App\Models\QardHasan;
use App\Models\QardHasanRepayment;
use App\Models\TakafulContribution;
use App\Models\WalletTransaction;
use App\Services\LedgerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LedgerSyncCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'ledger:sync {--force : Force the sync even if it might be slow}';

    /**
     * @var string
     */
    protected $description = 'Retroactively populate the double-entry ledger for existing successful records.';

    public function handle(LedgerService $ledger)
    {
        $this->info('Starting Ledger Synchronization...');

        $count = 0;
        $errors = 0;

        DB::transaction(function () use ($ledger, &$count, &$errors) {
            // 1. Contributions
            $this->processRecords(
                Contribution::whereNull('ledger_journal_id')->where('status', 'success'),
                'Contributions',
                function ($record) use ($ledger) {
                    return $record->category === 'fine'
                        ? $ledger->recordFine($record)
                        : $ledger->recordContribution($record);
                },
                $count, $errors
            );

            // 2. Wallet Transactions
            $this->processRecords(
                WalletTransaction::whereNull('ledger_journal_id'),
                'Wallet Transactions',
                function ($record) use ($ledger) {
                    return strtolower((string) $record->type) === 'credit'
                        ? $ledger->recordWalletCredit($record)
                        : $ledger->recordWalletDebit($record);
                },
                $count, $errors
            );

            // 3. Takaful Contributions
            $this->processRecords(
                TakafulContribution::whereNull('ledger_journal_id')->where('status', 'success'),
                'Takaful Contributions',
                function ($record) use ($ledger) {
                    return $ledger->recordTakafulContribution($record);
                },
                $count, $errors
            );

            // 4. Loans
            $this->processRecords(
                QardHasan::whereNull('ledger_journal_id')->whereIn('status', ['active', 'completed', 'defaulted']),
                'Loans (Disbursements)',
                function ($record) use ($ledger) {
                    return $ledger->recordLoanDisbursement($record);
                },
                $count, $errors
            );

            // 5. Loan Repayments
            $this->processRecords(
                QardHasanRepayment::whereNull('ledger_journal_id')->where('status', 'success'),
                'Loan Repayments',
                function ($record) use ($ledger) {
                    return $ledger->recordLoanRepayment($record);
                },
                $count, $errors
            );

            // 6. Income & Expenses
            $this->processRecords(
                IncomeEntry::whereNull('ledger_journal_id'),
                'Income Entries',
                function ($record) use ($ledger) {
                    return $ledger->recordIncome($record);
                },
                $count, $errors
            );

            $this->processRecords(
                ExpenseEntry::whereNull('ledger_journal_id')->where('status', 'processed'),
                'Expense Entries',
                function ($record) use ($ledger) {
                    return $ledger->recordExpense($record);
                },
                $count, $errors
            );
        });

        $this->info("\nLedger Sync Complete! Processed {$count} records. Errors: {$errors}");
    }

    private function processRecords($query, $label, $callback, &$count, &$errors)
    {
        $total = $query->count();
        if ($total === 0) {
            $this->line("Skipping {$label}: No records found.");
            return;
        }

        $this->info("Processing {$total} {$label}...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById(100, function ($records) use ($callback, &$count, &$errors, $bar) {
            foreach ($records as $record) {
                try {
                    $journal = $callback($record);
                    $record->updateQuietly(['ledger_journal_id' => $journal->id]);
                    $count++;
                } catch (\Exception $e) {
                    $this->error("\nError processing record ID {$record->id}: " . $e->getMessage());
                    $errors++;
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->line('');
    }
}
