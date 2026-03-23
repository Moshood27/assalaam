<?php

namespace App\Services;

use App\Models\Contribution;
use App\Models\QardHasan;
use App\Models\UtilityTransaction;
use App\Models\User;
use Carbon\Carbon;

class CoopScoreService
{
    public const WEIGHT_CONTRIBUTIONS = 40; // %
    public const WEIGHT_REPAYMENT = 40;     // %
    public const WEIGHT_VTU = 20;           // %

    public const INSTANT_THRESHOLD = 80;    // >= gets instant approval
    public const LOW_THRESHOLD = 40;        // < requires more guarantors

    public function scoreForUser(User|int $user): array
    {
        $user = $user instanceof User ? $user : User::find($user);
        if (!$user) {
            return [
                'score' => 0,
                'band' => 'unknown',
                'breakdown' => [],
                'thresholds' => [
                    'instant' => self::INSTANT_THRESHOLD,
                    'low' => self::LOW_THRESHOLD,
                ],
            ];
        }

        $contrib = $this->scoreContributions($user);
        $repay = $this->scoreRepaymentSpeed($user);
        $vtu = $this->scoreVtuActivity($user);

        $score = round($contrib['score'] + $repay['score'] + $vtu['score'], 1);
        $band = $this->band($score);

        return [
            'score' => $score,
            'band' => $band,
            'breakdown' => [
                'contributions' => $contrib,
                'repayment_speed' => $repay,
                'vtu_activity' => $vtu,
            ],
            'thresholds' => [
                'instant' => self::INSTANT_THRESHOLD,
                'low' => self::LOW_THRESHOLD,
            ],
        ];
    }

    protected function band(float $score): string
    {
        if ($score >= 90) return 'excellent';
        if ($score >= 80) return 'very_good';
        if ($score >= 70) return 'good';
        if ($score >= 60) return 'fair';
        if ($score >= 40) return 'low';
        return 'very_low';
    }

    protected function scoreContributions(User $user): array
    {
        // Consider last 6 calendar months including current
        $since = Carbon::now()->startOfMonth()->subMonths(5);
        $cons = Contribution::where('user_id', $user->id)
            ->where('status', 'success')
            ->where('created_at', '>=', $since)
            ->get(['id', 'amount', 'created_at']);

        // Count unique months with at least one contribution
        $months = [];
        $totalAmount = 0.0;
        foreach ($cons as $c) {
            $m = Carbon::parse($c->created_at)->format('Y-m');
            $months[$m] = true;
            $totalAmount += (float) $c->amount;
        }
        $activeMonths = count($months); // 0..6

        // Score: 6/6 months -> full weight; linear scale
        $ratio = $activeMonths > 6 ? 1.0 : ($activeMonths / 6.0);
        $score = round($ratio * self::WEIGHT_CONTRIBUTIONS, 1);

        return [
            'score' => $score,
            'active_months' => $activeMonths,
            'period_months' => 6,
            'total_amount' => round($totalAmount, 2),
            'since' => $since->toDateString(),
        ];
    }

    protected function scoreRepaymentSpeed(User $user): array
    {
        // Evaluate how much of expected repayments have been made compared to elapsed schedule
        $loans = QardHasan::where('user_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->get(['id', 'principal_amount', 'per_installment', 'total_installments', 'interval', 'paid_amount', 'created_at', 'status']);

        if ($loans->isEmpty()) {
            // No loan history: neutral half of weight
            return [
                'score' => round(self::WEIGHT_REPAYMENT * 0.5, 1),
                'average_on_time_ratio' => null,
                'loans_count' => 0,
            ];
        }

        $ratios = [];
        foreach ($loans as $l) {
            $created = Carbon::parse($l->created_at);
            $elapsed = 0;
            $interval = strtolower((string) $l->interval);
            if ($interval === 'daily') {
                $elapsed = $created->diffInDays(now());
            } elseif ($interval === 'weekly') {
                $elapsed = $created->diffInWeeks(now());
            } else { // monthly default
                $elapsed = $created->diffInMonths(now());
            }
            $elapsed = min(max($elapsed, 0), (int) $l->total_installments);
            $expected = max((float) $l->per_installment * $elapsed, 0.0);
            $actual = max((float) $l->paid_amount, 0.0);

            $ratio = $expected > 0 ? min($actual / $expected, 1.2) : ($l->status === 'completed' ? 1.0 : 0.0);
            // Cap at 1.2 to reward early completion slightly
            $ratios[] = $ratio;
        }

        $avg = count($ratios) ? array_sum($ratios) / count($ratios) : 0.0;
        // Map 0..1.2 avg to 0..100% of weight (values >1 treated as 1)
        $normalized = min($avg, 1.0);
        $score = round($normalized * self::WEIGHT_REPAYMENT, 1);

        return [
            'score' => $score,
            'average_on_time_ratio' => round($avg, 3),
            'loans_count' => count($ratios),
        ];
    }

    protected function scoreVtuActivity(User $user): array
    {
        $since = Carbon::now()->subDays(90);
        $q = UtilityTransaction::where('user_id', $user->id)
            ->where('status', 'success')
            ->where('created_at', '>=', $since);
        $count = (int) $q->count();
        $amount = (float) $q->sum('amount');

        // Simple heuristic: 0 tx => 0; 5+ tx => full weight (20)
        $ratio = min($count / 5.0, 1.0);
        $score = round($ratio * self::WEIGHT_VTU, 1);

        return [
            'score' => $score,
            'transactions' => $count,
            'total_amount' => round($amount, 2),
            'since' => $since->toDateString(),
        ];
    }
}
