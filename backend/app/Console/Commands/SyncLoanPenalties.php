<?php

namespace App\Console\Commands;

use App\Models\LoanPenalty;
use App\Models\QardHasan;
use Illuminate\Console\Command;

class SyncLoanPenalties extends Command
{
    protected $signature = 'loans:sync-penalties {--dry-run : Only show what would be done}';

    protected $description = 'Retroactively create missing penalty records for defaulted loans and complete records for cleared defaults.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $createdCount = 0;
        $completedCount = 0;

        // 1. Ensure all currently defaulted loans have an open penalty record
        $defaultedLoans = QardHasan::whereNotNull('defaulted_at')->get();
        foreach ($defaultedLoans as $loan) {
            $exists = LoanPenalty::where('qard_hasan_id', $loan->id)
                ->whereNull('default_cleared_at')
                ->exists();

            if (!$exists) {
                if (!$dry) {
                    $loan->startPenaltyRecord();
                    $this->info("Created missing penalty record for loan {$loan->qard_id_string} (Defaulted since {$loan->defaulted_at})");
                } else {
                    $this->info("[DRY] Would create missing penalty record for loan {$loan->qard_id_string}");
                }
                $createdCount++;
            }
        }

        // 2. Ensure all cleared defaults have their penalty records completed
        $activePenalties = LoanPenalty::whereNull('default_cleared_at')->get();
        foreach ($activePenalties as $penalty) {
            $loan = $penalty->qardHasan;
            if (!$loan || !$loan->defaulted_at) {
                if (!$dry) {
                    // Loan exists but defaulted_at is null, or loan was deleted
                    if ($loan) {
                        $loan->completePenaltyRecord();
                        $this->info("Completed stray penalty record for loan {$loan->qard_id_string}");
                    } else {
                        // Loan deleted, we should probably mark penalty as cleared now or delete it
                        $penalty->update(['default_cleared_at' => now(), 'penalty_until' => now()]);
                        $this->info("Closed penalty record for deleted loan ID {$penalty->qard_hasan_id}");
                    }
                } else {
                    $this->info("[DRY] Would complete stray penalty record for loan " . ($loan ? $loan->qard_id_string : "ID {$penalty->qard_hasan_id} (Deleted)"));
                }
                $completedCount++;
            }
        }

        $this->info("Sync completed. Created: {$createdCount}, Completed: {$completedCount}");

        // 3. Fix records where penalty_until is null but default_cleared_at is set
        $incompletePenalties = LoanPenalty::whereNotNull('default_cleared_at')
            ->whereNull('penalty_until')
            ->get();
        $fixedCount = 0;
        foreach ($incompletePenalties as $penalty) {
            $start = $penalty->default_started_at;
            $end = $penalty->default_cleared_at;
            if ($start && $end) {
                $penaltyUntil = $end->copy()->add($start->diff($end));
                if (!$dry) {
                    $penalty->update(['penalty_until' => $penaltyUntil]);
                    $this->info("Calculated penalty_until for record ID {$penalty->id}: {$penaltyUntil}");
                } else {
                    $this->info("[DRY] Would calculate penalty_until for record ID {$penalty->id}: {$penaltyUntil}");
                }
                $fixedCount++;
            }
        }

        if ($fixedCount > 0) {
            $this->info("Calculated missing penalty dates for {$fixedCount} records.");
        }

        return self::SUCCESS;
    }
}
