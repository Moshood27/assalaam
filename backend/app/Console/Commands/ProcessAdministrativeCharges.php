<?php

namespace App\Console\Commands;

use App\Services\AdministrativeChargeService;
use Illuminate\Console\Command;

class ProcessAdministrativeCharges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin-charges:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process monthly administrative charges for all active members';

    /**
     * Execute the console command.
     */
    public function handle(AdministrativeChargeService $service)
    {
        $this->info('Starting administrative charge processing...');

        $stats = $service->processMonthlyCharges();

        $this->line('Total Users Processed: ' . $stats['total_users']);
        $this->line('Charges Accrued: ' . $stats['accrued']);
        $this->line('Auto-Deductions Successful: ' . $stats['auto_deducted']);
        $this->line('Auto-Deductions Failed (Insufficient Funds): ' . $stats['failed_auto_deduct']);
        $this->line('Total Deducted Amount: â‚¦' . number_format($stats['total_deducted_amount'], 2));

        $this->info('Administrative charge processing completed.');

        return 0;
    }
}
