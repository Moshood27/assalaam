<?php

namespace App\Console\Commands;

use App\Models\Contribution;
use App\Models\LedgerAccount;
use App\Models\LedgerJournal;
use App\Models\QardHasan;
use App\Models\QardHasanRepayment;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FixPastLoanRepayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-past-loan-repayments {--dry-run : Whether to only simulate the changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate past Loan Repayment scheme contributions to the Qard Hasan loan system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('DRY RUN - No changes will be saved to the database.');
        }

        $scheme = Scheme::where('name', 'Loan Repayment')->first();

        if (!$scheme) {
            $this->error('Scheme "Loan Repayment" not found.');
            return 1;
        }

        $contributions = Contribution::where('scheme_id', $scheme->id)
            ->where('status', 'success')
            ->orderBy('created_at', 'asc')
            ->get();

        $this->info("Found {$contributions->count()} successful Loan Repayment contributions.");

        $processedCount = 0;
        $skippedCount = 0;
        $ledgerUpdatedCount = 0;

        foreach ($contributions as $contribution) {
            try {
                DB::transaction(function () use ($contribution, $dryRun, &$processedCount, &$skippedCount, &$ledgerUpdatedCount) {
                    $user = $contribution->user;

                    // Check if already processed
                    if (QardHasanRepayment::where('reference', $contribution->reference)->exists()) {
                        $this->line("Contribution {$contribution->reference} already has a repayment record. Skipping.");
                        $skippedCount++;
                        return;
                    }

                    // Find active or defaulted loan
                    $loan = QardHasan::where('user_id', $user->id)
                        ->whereIn('status', ['active', 'defaulted'])
                        ->orderBy('created_at', 'asc')
                        ->lockForUpdate()
                        ->first();

                    if (!$loan) {
                        $this->warn("No active/defaulted Qard Hasan loan found for user {$user->name} ({$user->membership_number}) for contribution {$contribution->reference}. Skipping.");
                        $skippedCount++;
                        return;
                    }

                    if (!$dryRun) {
                        // Create QardHasanRepayment
                        $repayment = QardHasanRepayment::create([
                            'qard_hasan_id' => $loan->id,
                            'amount' => $contribution->amount,
                            'reference' => $contribution->reference,
                            'status' => 'success',
                            'paid_at' => $contribution->updated_at ?? $contribution->created_at,
                            'ledger_journal_id' => $contribution->ledger_journal_id, // Pass existing journal to prevent hook from creating new one
                        ]);

                        // Handle Ledger adjustment if needed
                        if ($contribution->ledger_journal_id) {
                            $journal = LedgerJournal::find($contribution->ledger_journal_id);
                            if ($journal) {
                                // Find the entry that credits Member Deposit (2200)
                                $memberDepositAccount = LedgerAccount::where('code', '2200')->first();
                                $loanAssetAccount = LedgerAccount::where('code', '1300')->first();

                                if ($memberDepositAccount && $loanAssetAccount) {
                                    $entry = $journal->entries()
                                        ->where('ledger_account_id', $memberDepositAccount->id)
                                        ->where('credit', '>', 0)
                                        ->first();

                                    if ($entry) {
                                        $entry->update([
                                            'ledger_account_id' => $loanAssetAccount->id,
                                            'description' => 'Loan Asset Reduction (Migrated)',
                                        ]);

                                        $journal->update([
                                            'description' => "Qard Hasan Repayment from {$user->name} (Migrated)",
                                        ]);

                                        // Optional: decouple contribution from journal to match new logic
                                        $contribution->updateQuietly(['ledger_journal_id' => null]);

                                        $ledgerUpdatedCount++;
                                    }
                                }
                            }
                        }

                        // Update Loan
                        $loan->paid_amount = (float) $loan->paid_amount + (float) $contribution->amount;
                        if ($loan->paid_amount >= $loan->principal_amount) {
                            $loan->status = 'completed';
                        }
                        $loan->save();
                    }

                    $this->line("Processed contribution {$contribution->reference} for user {$user->name}.");
                    $processedCount++;
                });
            } catch (\Exception $e) {
                $this->error("Error processing contribution {$contribution->reference}: " . $e->getMessage());
                Log::error("Error in FixPastLoanRepayments: " . $e->getMessage(), ['contribution' => $contribution->id]);
            }
        }

        // Reset all user loan_repayment_balance
        if (!$dryRun) {
            $this->info("Resetting loan_repayment_balance for all users...");
            User::where('loan_repayment_balance', '>', 0)->update(['loan_repayment_balance' => 0]);
        } else {
            $userCount = User::where('loan_repayment_balance', '>', 0)->count();
            $this->info("Would reset loan_repayment_balance for {$userCount} users.");
        }

        $this->info("Summary:");
        $this->info("- Processed: {$processedCount}");
        $this->info("- Skipped: {$skippedCount}");
        $this->info("- Ledger entries updated: {$ledgerUpdatedCount}");

        return 0;
    }
}
