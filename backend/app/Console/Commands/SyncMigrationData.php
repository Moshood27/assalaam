<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\QardHasan;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SyncMigrationData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-migration-data {--dry-run : Only show what would be updated}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync migrated_at and received_at for imported members and loans to ensure new policies take effect.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info("--- Syncing Migration Data ---");

        // 1. Sync Users: Set migrated_at for anyone who looks like they were imported
        $this->info("\nStep 1: Syncing Users...");
        $userCount = 0;

        // Criteria: Has membership_number OR has MIG- contributions
        User::whereNull('migrated_at')
            ->where(function($q) {
                $q->whereNotNull('membership_number')
                  ->orWhereHas('contributions', function($sq) {
                      $sq->where('reference', 'like', 'MIG-%');
                  });
            })
            ->chunkById(100, function($users) use ($dryRun, &$userCount) {
                foreach ($users as $user) {
                    if (!$dryRun) {
                        // Use created_at as the migration date if it's recent, otherwise just use now()
                        $user->update(['migrated_at' => $user->created_at ?: now()]);
                    }
                    $this->line(" [USER] Synced: {$user->name} (#{$user->membership_number})");
                    $userCount++;
                }
            });

        $this->info("Total users synced: $userCount");

        // 2. Sync Loans: Set received_at for migrated loans
        $this->info("\nStep 2: Syncing Loans...");
        $loanCount = 0;

        QardHasan::whereNull('received_at')
            ->where(function($q) {
                $q->where('qard_id_string', 'like', 'MIG-%')
                  ->orWhere('qard_id_string', 'like', 'MGR-%');
            })
            ->chunkById(100, function($loans) use ($dryRun, &$loanCount) {
                foreach ($loans as $loan) {
                    if (!$dryRun) {
                        // Fallback order: approved_at -> created_at -> now
                        $receivedAt = $loan->approved_at ?: $loan->created_at ?: now();
                        $loan->update(['received_at' => $receivedAt]);
                    }
                    $this->line(" [LOAN] Synced: {$loan->qard_id_string} (User ID: {$loan->user_id})");
                    $loanCount++;
                }
            });

        $this->info("Total loans synced: $loanCount");

        // 3. Enforce Loan Duration Rules for Migrated Loans
        $this->info("\nStep 3: Enforcing Loan Duration Rules for Migrated Loans...");
        $durationCount = 0;
        QardHasan::where(function($q) {
                $q->where('qard_id_string', 'like', 'MIG-%')
                  ->orWhere('qard_id_string', 'like', 'MGR-%');
            })
            ->chunkById(100, function($loans) use ($dryRun, &$durationCount) {
                foreach ($loans as $loan) {
                    $allowedDuration = \App\Support\DurationHelper::getLoanDuration($loan->principal_amount, $loan->received_at);

                    if ($loan->total_installments > $allowedDuration) {
                        if (!$dryRun) {
                            $loan->update([
                                'total_installments' => $allowedDuration,
                                'per_installment' => round($loan->principal_amount / $allowedDuration, 2)
                            ]);
                        }
                        $this->line(" [LOAN] Updated duration: {$loan->qard_id_string} ({$loan->total_installments} -> {$allowedDuration})");
                        $durationCount++;
                    }
                }
            });

        $this->info("Total loans with corrected duration: $durationCount");

        // 4. Status Maintenance
        $this->info("\nStep 4: Running Status Maintenance...");
        if (!$dryRun) {
            $this->call('loans:fix-completed-status');
            $this->call('loans:sync-penalties');
        } else {
            $this->info(" [DRY] Skipping status maintenance.");
        }

        $this->info("\n--- Sync Complete ---");

        if ($dryRun) {
            $this->warn("This was a DRY RUN. No data was actually changed.");
        }

        return self::SUCCESS;
    }
}
