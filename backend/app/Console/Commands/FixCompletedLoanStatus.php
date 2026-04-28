<?php

namespace App\Console\Commands;

use App\Models\QardHasan;
use Illuminate\Console\Command;

class FixCompletedLoanStatus extends Command
{
    protected $signature = 'loans:fix-completed-status {--dry-run : Only show what would be fixed}';

    protected $description = 'Repair loan records that are fully paid but still marked as active or defaulted.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $fixedCount = 0;

        $loans = QardHasan::whereIn('status', ['active', 'defaulted', 'pending'])
            ->whereColumn('paid_amount', '>=', 'principal_amount')
            ->where('principal_amount', '>', 0)
            ->get();

        if ($loans->isEmpty()) {
            $this->info("No stuck loans found.");
            return self::SUCCESS;
        }

        $this->info("Found {$loans->count()} loans to fix.");

        foreach ($loans as $loan) {
            if (!$dry) {
                // Saving will trigger the model's auto-completion logic (now fixed)
                $loan->save();
                $this->info("Fixed loan {$loan->qard_id_string}: Status changed to completed and defaulted_at cleared.");
            } else {
                $this->info("[DRY] Would fix loan {$loan->qard_id_string} (Paid: {$loan->paid_amount}, Principal: {$loan->principal_amount})");
            }
            $fixedCount++;
        }

        $this->info("Total loans " . ($dry ? "to be fixed" : "fixed") . ": {$fixedCount}");

        return self::SUCCESS;
    }
}
