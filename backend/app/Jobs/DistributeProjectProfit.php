<?php

namespace App\Jobs;

use App\Models\ProjectInvestment;
use App\Models\ProjectProfit;
use App\Models\ProjectProfitPayout;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Notifications\ProjectProfitDistributed;
use App\Services\PushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DistributeProjectProfit implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $projectProfitId)
    {
    }

    public function handle(): void
    {
        /** @var ProjectProfit $profit */
        $profit = ProjectProfit::with('project')->find($this->projectProfitId);
        if (!$profit) {
            return;
        }

        // Idempotency: if any payouts already exist for this profit, exit
        if (ProjectProfitPayout::where('project_profit_id', $profit->id)->exists()) {
            return;
        }

        $projectId = $profit->project_id;
        $cutoff = $profit->created_at;

        // Aggregate total invested per user up to the profit time
        $isUnitBased = (bool) ($profit->project->is_unit_based ?? false);

        $investments = ProjectInvestment::query()
            ->selectRaw('user_id, SUM(amount) as total_amount, SUM(units) as total_units')
            ->where('project_id', $projectId)
            ->where('created_at', '<=', $cutoff)
            ->groupBy('user_id')
            ->get();

        $totalForCalculation = $isUnitBased
            ? (float) $investments->sum('total_units')
            : (float) $investments->sum('total_amount');

        $net = (float) $profit->net_distributable;

        if ($totalForCalculation <= 0 || $net <= 0) {
            Log::info('No distribution due to zero totals', ['profit_id' => $profit->id, 'total_for_calc' => $totalForCalculation, 'net' => $net]);
            return;
        }

        // Work in kobo to avoid float rounding issues
        $netKobo = (int) round($net * 100);
        $alloc = [];
        $remainders = [];
        $baseSum = 0;

        foreach ($investments as $row) {
            $userId = (int) $row->user_id;
            $userTotal = $isUnitBased ? (float) $row->total_units : (float) $row->total_amount;
            $share = $userTotal / $totalForCalculation; // 0..1
            $rawKobo = $share * $netKobo;
            $floorKobo = (int) floor($rawKobo);
            $alloc[$userId] = $floorKobo;
            $baseSum += $floorKobo;
            $remainders[$userId] = $rawKobo - $floorKobo; // fractional remainder
        }

        // Distribute remaining kobo by largest fractional remainders
        $remaining = $netKobo - $baseSum;
        if ($remaining > 0) {
            arsort($remainders); // desc by remainder
            foreach (array_keys($remainders) as $userId) {
                if ($remaining <= 0) break;
                $alloc[$userId] += 1;
                $remaining--;
            }
        }

        DB::transaction(function () use ($profit, $projectId, $alloc) {
            foreach ($alloc as $userId => $kobo) {
                if ($kobo <= 0) continue;
                $amount = round($kobo / 100, 2);

                // Create payout row
                $payout = ProjectProfitPayout::create([
                    'project_profit_id' => $profit->id,
                    'project_id' => $projectId,
                    'user_id' => $userId,
                    'amount' => $amount,
                    'status' => 'success',
                ]);

                // Credit wallet balance safely
                /** @var User $user */
                $user = User::whereKey($userId)->lockForUpdate()->first();
                if (!$user) continue;

                $user->increment('balance', $amount);

                $reference = 'PROFIT-'.$profit->id.'-U'.$userId;
                WalletTransaction::create([
                    'user_id' => $userId,
                    'type' => 'credit',
                    'amount' => $amount,
                    'reference' => $reference,
                    'source' => 'project_profit',
                    'meta' => [
                        'project_id' => $projectId,
                        'project_profit_id' => $profit->id,
                    ],
                ]);

                // Notify user (database notification)
                try {
                    $user->notify(new ProjectProfitDistributed($profit, $amount));
                } catch (\Throwable $e) {
                    // Ignore notification failures but log
                    Log::warning('Failed to notify user of profit distribution', ['user_id' => $userId, 'error' => $e->getMessage()]);
                }

                // Best-effort push notification
                try {
                    $push = app(PushService::class);
                    $push->send($user->fcm_token ?: $user->device_token, 'Profit Distributed', 'You received ₦'.number_format($amount, 2).' from '.$profit->project->name, [
                        'type' => 'project_profit',
                        'project_id' => (string) $projectId,
                        'project_profit_id' => (string) $profit->id,
                    ]);
                } catch (\Throwable $e) {
                    // swallow push errors
                }

                // Mark notified time
                $payout->notified_at = now();
                $payout->save();
            }
        });
    }
}
