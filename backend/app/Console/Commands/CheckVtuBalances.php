<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\VtuBalanceService;
use App\Services\PushService;
use App\Services\SmsService;
use App\Models\User;

class CheckVtuBalances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vtu:check-balances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks VTU providers balances and notifies admins if below threshold';

    /**
     * Execute the console command.
     */
    public function handle(VtuBalanceService $service, PushService $push, SmsService $sms): int
    {
        $threshold = (float) config('services.vtu.low_balance_threshold', 10000);
        $balances = $service->getBalances();

        if (empty($balances)) {
            $this->info('No VTU providers configured.');
            return self::SUCCESS;
        }

        $low = [];
        foreach ($balances as $name => $b) {
            if (!is_array($b) || ($b['ok'] ?? false) !== true) {
                continue;
            }
            $avail = (float) ($b['available'] ?? 0);
            if ($avail < $threshold) {
                $low[$name] = $avail;
            }
        }

        if (empty($low)) {
            $this->info('All VTU balances are healthy.');
            Log::info('VTU balance check OK', ['threshold' => $threshold, 'balances' => $balances]);
            return self::SUCCESS;
        }

        // Build human-readable message
        $pairs = [];
        foreach ($low as $name => $amt) {
            $pairs[] = strtoupper($name) . ': ' . number_format($amt, 2);
        }
        $msg = 'Low VTU balance alert — ' . implode(', ', $pairs) . '. Threshold: ' . number_format($threshold, 2);

        Log::warning('VTU balances below threshold', ['threshold' => $threshold, 'low' => $low, 'all' => $balances]);
        $this->warn($msg);

        // Notify admins via Push (and SMS best-effort)
        try {
            $admins = User::query()->where('is_admin', true)->get(['id','name','phone','device_token','fcm_token']);
            foreach ($admins as $a) {
                $token = $a->fcm_token ?: $a->device_token;
                if (!empty($token)) {
                    $push->send($token, 'Low VTU Balance', $msg, [ 'type' => 'vtu_balance_low' ]);
                }
                // Optional SMS fallback
                $sms->send($a->phone ?? null, $msg);
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to notify admins for VTU balance', ['error' => $e->getMessage()]);
        }

        return self::SUCCESS;
    }
}
