<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SavingsGroupService;

class SavingsGroupChargeCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'savings-groups:charge {--period=}';

    /**
     * The console command description.
     */
    protected $description = 'Charge monthly contributions for all active savings groups';

    /**
     * Execute the console command.
     */
    public function handle(SavingsGroupService $service)
    {
        $period = $this->option('period') ?: null;

        $this->info('Charging Savings Group contributions' . ($period ? " for $period" : ' for current month'));
        $result = $service->chargeMonthly($period);

        if (isset($result['error'])) {
            $this->error($result['error']);
            return 1;
        }

        $this->line('Groups processed: ' . $result['groups_processed']);
        $this->line('Members processed: ' . $result['members_processed']);
        $this->line('Successful charges: ' . $result['successful_charges']);
        $this->line('Failed (insufficient): ' . $result['failed_insufficient']);
        $this->line('Total amount: ₦' . number_format($result['total_amount'], 2));

        $this->info('Done.');
        return 0;
    }
}
