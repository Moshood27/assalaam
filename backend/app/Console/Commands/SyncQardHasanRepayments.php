<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\QardHasanService;
use Illuminate\Console\Command;

class SyncQardHasanRepayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-qard-hasan-repayments {--user= : The membership number of a specific user to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync loan repayments from contributions and balance loan amounts';

    /**
     * Execute the console command.
     */
    public function handle(QardHasanService $service)
    {
        $membershipNumber = $this->option('user');
        $user = null;

        if ($membershipNumber) {
            $user = User::where('membership_number', $membershipNumber)->first();
            if (!$user) {
                $this->error("User with membership number {$membershipNumber} not found.");
                return 1;
            }
            $this->info("Syncing and balancing for user: {$user->name} ({$user->membership_number})");
        } else {
            $this->info("Syncing and balancing for all users...");
        }

        $results = $service->fullSyncAndBalance($user);

        if (isset($results['error'])) {
            $this->error($results['error']);
            return 1;
        }

        $this->info("Summary:");
        $this->info("- Contributions processed: {$results['processed']}");
        $this->info("- Contributions skipped/no loan: {$results['skipped']}");
        $this->info("- Loans with recalculated balances: {$results['recalculated']}");

        return 0;
    }
}
