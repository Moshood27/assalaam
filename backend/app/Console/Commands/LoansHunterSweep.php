<?php

namespace App\Console\Commands;

use App\Jobs\AutoRecoverOverdueLoans;
use App\Models\QardHasan;
use App\Models\User;
use Illuminate\Console\Command;

class LoansHunterSweep extends Command
{
    protected $signature = 'loans:hunter-sweep {--dry-run : Show users that would be processed without dispatching jobs}';

    protected $description = 'Hourly sweep: for members with active overdue loans and wallet balances, dispatch auto-recovery.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $countUsers = 0;

        // Find users who have at least one active loan and positive wallet balance
        $query = User::query()
            ->where('balance', '>', 0)
            ->whereExists(function ($q) {
                $q->select('id')
                  ->from((new QardHasan)->getTable())
                  ->whereColumn('qard_hasans.user_id', 'users.id')
                  ->whereIn('qard_hasans.status', ['active', 'defaulted']);
            });

        $query->chunkById(500, function ($users) use (&$countUsers, $dry) {
            foreach ($users as $user) {
                $countUsers++;
                if ($dry) {
                    $this->info(sprintf('[DRY] Would process user %d (%s), balance=%.2f', $user->id, $user->name, (float) $user->balance));
                } else {
                    AutoRecoverOverdueLoans::dispatch((int) $user->id)->onQueue('default');
                }
            }
        });

        $this->info(sprintf('Sweep completed. Users queued: %d%s', $countUsers, $dry ? ' (dry-run)' : ''));
        return self::SUCCESS;
    }
}
