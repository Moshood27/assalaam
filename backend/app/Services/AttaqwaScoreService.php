<?php

namespace App\Services;

use App\Models\Contribution;
use App\Models\QardHasan;
use App\Models\UtilityTransaction;
use App\Models\User;
use Carbon\Carbon;

class AttaqwaScoreService
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

    public function calculateAndUpdateScore(User $user): int
    {
        $result = $this->scoreForUser($user);
        $score = (int) $result['score'];

        $user->attaqwa_score = $score;
        $user->save();

        $this->checkAndAwardBadges($user);

        return $score;
    }

    public function checkAndAwardBadges(User $user): void
    {
        $this->checkConsistencyBadge($user);
        $this->checkEarlyRepaymentBadge($user);
        $this->checkSavingsMilestoneBadge($user);
        $this->checkVtuPowerUserBadge($user);
        $this->checkLoanMasterBadge($user);
    }

    private function checkLoanMasterBadge(User $user): void
    {
        $badgeType = 'loan_master';
        if ($user->badges()->where('badge_type', $badgeType)->exists()) {
            return;
        }

        $loanCount = QardHasan::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        if ($loanCount >= 3) {
            \App\Models\UserBadge::create([
                'user_id' => $user->id,
                'badge_type' => $badgeType,
                'name' => 'Loan Master',
                'description' => 'Successfully completed 3 or more loans.',
                'earned_at' => now(),
            ]);

            $user->notifyMember('New Badge Earned!', 'Congratulations! You earned the "Loan Master" badge.');
        }
    }

    private function checkSavingsMilestoneBadge(User $user): void
    {
        $badgeType = 'savings_milestone_100k';
        if ($user->badges()->where('badge_type', $badgeType)->exists()) {
            return;
        }

        $totalContributions = Contribution::where('user_id', $user->id)
            ->where('status', 'success')
            ->sum('amount');

        if ($totalContributions >= 100000) {
            \App\Models\UserBadge::create([
                'user_id' => $user->id,
                'badge_type' => $badgeType,
                'name' => 'Savings Milestone: 100k',
                'description' => 'Successfully contributed a total of 100,000 or more.',
                'earned_at' => now(),
            ]);

            $user->notifyMember('New Badge Earned!', 'Congratulations! You earned the "Savings Milestone: 100k" badge.');
        }
    }

    private function checkVtuPowerUserBadge(User $user): void
    {
        $badgeType = 'vtu_power_user';
        if ($user->badges()->where('badge_type', $badgeType)->exists()) {
            return;
        }

        $vtuCount = UtilityTransaction::where('user_id', $user->id)
            ->where('status', 'success')
            ->count();

        if ($vtuCount >= 10) {
            \App\Models\UserBadge::create([
                'user_id' => $user->id,
                'badge_type' => $badgeType,
                'name' => 'VTU Power User',
                'description' => 'Successfully completed 10 or more VTU transactions.',
                'earned_at' => now(),
            ]);

            $user->notifyMember('New Badge Earned!', 'Congratulations! You earned the "VTU Power User" badge.');
        }
    }

    private function checkConsistencyBadge(User $user): void
    {
        $badgeType = 'consistency_savings_12';
        if ($user->badges()->where('badge_type', $badgeType)->exists()) {
            return;
        }

        // Find the latest successful contribution to start checking backwards
        $latest = Contribution::where('user_id', $user->id)
            ->where('status', 'success')
            ->orderByDesc('created_at')
            ->first();

        if (!$latest) return;

        // Check 12 consecutive months of savings (at least one successful contribution per month)
        // starting from the month of the latest contribution
        $has12Months = true;
        $curr = Carbon::parse($latest->created_at)->startOfMonth();

        for ($i = 0; $i < 12; $i++) {
            $month = (clone $curr)->subMonths($i);
            $exists = Contribution::where('user_id', $user->id)
                ->where('status', 'success')
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->exists();

            if (!$exists) {
                $has12Months = false;
                break;
            }
        }

        if ($has12Months) {
            \App\Models\UserBadge::create([
                'user_id' => $user->id,
                'badge_type' => $badgeType,
                'name' => '12 Months of Consistent Savings',
                'description' => 'Successfully made contributions for 12 consecutive months.',
                'earned_at' => now(),
            ]);

            $user->notifyMember('New Badge Earned!', 'Congratulations! You earned the "12 Months of Consistent Savings" badge.');
        }
    }

    private function checkEarlyRepaymentBadge(User $user): void
    {
        $badgeType = 'early_loan_repayment';
        if ($user->badges()->where('badge_type', $badgeType)->exists()) {
            return;
        }

        $hasEarlyRepayment = false;
        // A loan is "early repaid" if it was completed before the final due date in its schedule.
        $loans = $user->qardHasans()->where('status', 'completed')->get();
        foreach ($loans as $loan) {
            $schedule = $loan->generateInstallmentSchedule($loan->approved_at);
            if (empty($schedule)) continue;

            $lastDueDate = Carbon::parse(end($schedule)['due_date']);
            $actualCompletionDate = $loan->repayments()->where('status', 'success')->max('paid_at');

            if ($actualCompletionDate && Carbon::parse($actualCompletionDate)->lt($lastDueDate)) {
                $hasEarlyRepayment = true;
                break;
            }
        }

        if ($hasEarlyRepayment) {
            \App\Models\UserBadge::create([
                'user_id' => $user->id,
                'badge_type' => $badgeType,
                'name' => 'Early Loan Repayment',
                'description' => 'Successfully repaid a loan before the final due date.',
                'earned_at' => now(),
            ]);

            $user->notifyMember('New Badge Earned!', 'Congratulations! You earned the "Early Loan Repayment" badge.');
        }
    }

    public function getScoreTips(User $user): array
    {
        $tips = [];
        $breakdown = $this->scoreForUser($user)['breakdown'];

        if ($breakdown['contributions']['active_months'] < 6) {
            $tips[] = 'Make a contribution every month to increase your consistency score.';
        }

        if (($breakdown['repayment_speed']['average_on_time_ratio'] ?? 0) < 1.0) {
            $tips[] = 'Repay your loans on or before the due date to boost your repayment speed score.';
        }

        if ($breakdown['vtu_activity']['transactions'] < 5) {
            $tips[] = 'Use our VTU services for airtime and bills to improve your activity score.';
        }

        if (empty($tips)) {
            $tips[] = 'Keep up the great cooperative behavior! You are a top-tier member.';
        }

        return $tips;
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
                $elapsed = (int) $created->diffInDays(now());
            } elseif ($interval === 'weekly') {
                $elapsed = (int) $created->diffInWeeks(now());
            } else { // monthly default
                $elapsed = (int) $created->diffInMonths(now());
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
