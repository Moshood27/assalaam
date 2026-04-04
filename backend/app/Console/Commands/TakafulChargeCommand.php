<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TakafulService;

class TakafulChargeCommand extends Command
{
    /**
     * The name and signature of the console command.
     * Options:
     *  --period=YYYY-MM
     *  --amount=NUMBER
     *  --user=ID
     *  --dry-run
     */
    protected $signature = 'takaful:charge {--period=} {--amount=} {--user=} {--dry-run}';

    /**
     * The console command description.
     */
    protected $description = 'Charge monthly Takaful (Mutual Protection) contributions';

    /**
     * Execute the console command.
     */
    public function handle(TakafulService $service)
    {
        $period = $this->option('period') ?: null;
        $amountOpt = $this->option('amount');
        $amount = $amountOpt !== null ? (float) $amountOpt : null;
        $userIdOpt = $this->option('user');
        $userId = $userIdOpt !== null ? (int) $userIdOpt : null;
        $dry = (bool) $this->option('dry-run');

        $this->info('Charging Takaful contributions' . ($period ? " for $period" : ' for current month'));
        $result = $service->chargeMonthly($period, $amount, $userId, $dry);

        $this->line('Processed: ' . $result['processed']);
        $this->line('Created  : ' . $result['created']);
        $this->line('Charged  : ₦' . number_format($result['charged'], 2));
        $this->line('Skipped (existing): ' . $result['skipped_existing']);
        $this->line('Insufficient funds: ' . $result['insufficient_funds']);
        $this->line('Pool balance after: ₦' . number_format($result['balance'], 2));

        $this->info('Done.');
        return 0;
    }
}
