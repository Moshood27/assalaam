<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\QardHasan;
use App\Models\QardHasanRepayment;
use App\Models\ShariahAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdminReportsController extends Controller
{
    public function __construct()
    {
        // Ensure only admins can access
        $this->middleware(function ($request, $next) {
            $user = $request->user();
            if (! $user || ! (bool) $user->is_admin) {
                return response()->json(['message' => 'Admins only'], 403);
            }
            return $next($request);
        });
    }

    // 1) Branch Performance Report
    public function branchPerformance(Request $request)
    {
        $branches = DB::table('branches')->select('id', 'name')->orderBy('name')->get();

        $memberCounts = DB::table('users')
            ->select('branch_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('branch_id')
            ->pluck('cnt', 'branch_id');

        $collections = DB::table('contributions')
            ->join('users', 'users.id', '=', 'contributions.user_id')
            ->where('contributions.status', 'success')
            ->select('users.branch_id as branch_id', DB::raw('SUM(contributions.amount) as total'))
            ->groupBy('users.branch_id')
            ->pluck('total', 'branch_id');

        $rows = $branches->map(function ($b) use ($memberCounts, $collections) {
            return [
                'branch_id' => $b->id,
                'branch_name' => $b->name,
                'member_count' => (int) ($memberCounts[$b->id] ?? 0),
                'total_collections' => (float) ($collections[$b->id] ?? 0.0),
            ];
        })->values();

        // Sort by collections desc
        $rows = $rows->sortByDesc('total_collections')->values();

        return response()->json([
            'rows' => $rows,
            'totals' => [
                'members' => (int) array_sum(array_map(fn($r) => $r['member_count'], $rows->all())),
                'collections' => (float) array_sum(array_map(fn($r) => $r['total_collections'], $rows->all())),
            ],
        ]);
    }

    // 2) Scheme Popularity Report
    public function schemePopularity(Request $request)
    {
        $rows = Contribution::query()
            ->select('scheme_id', DB::raw('SUM(amount) as total'))
            ->where('status', 'success')
            ->groupBy('scheme_id')
            ->with('scheme')
            ->get();

        $total = (float) $rows->sum('total');
        $data = $rows->map(function ($r) use ($total) {
            $amount = (float) $r->total;
            $pct = $total > 0 ? round(($amount / $total) * 100, 2) : 0.0;
            return [
                'scheme_id' => $r->scheme_id,
                'scheme_name' => optional($r->scheme)->name ?? 'Unknown',
                'amount' => $amount,
                'percentage' => $pct,
            ];
        })->sortByDesc('amount')->values();

        return response()->json([
            'total' => $total,
            'breakdown' => $data,
        ]);
    }

    // 3) Defaulting Member Report (Delinquency)
    public function delinquency(Request $request)
    {
        $now = Carbon::now();
        $thresholdDays = (int) $request->query('threshold_days', 30);
        $thresholdDate = $now->copy()->subDays($thresholdDays);

        $loans = QardHasan::query()
            ->with(['user.branch'])
            ->where('status', 'active')
            ->get();

        $rows = [];

        foreach ($loans as $loan) {
            $paid = (float) QardHasanRepayment::query()
                ->where('qard_hasan_id', $loan->id)
                ->where('status', 'success')
                ->sum('amount');

            $remaining = max(0.0, (float) $loan->principal_amount - $paid);
            if ($remaining <= 0.0) {
                continue; // not delinquent if fully paid
            }

            $start = $loan->created_at ? $loan->created_at->copy() : $now->copy();
            $interval = strtolower((string) $loan->interval);

            $expectedIntervals = match ($interval) {
                'daily' => $start->diffInDays($now),
                'weekly' => intdiv($start->diffInDays($now), 7),
                'quarterly' => intdiv($start->diffInMonths($now), 3),
                'yearly' => $start->diffInYears($now),
                default => $start->diffInMonths($now), // monthly
            };

            $expectedInstallments = min((int) $loan->total_installments, max(0, (int) $expectedIntervals));
            $per = (float) $loan->per_installment ?: 0.0;
            $paidInstallments = $per > 0 ? (int) floor($paid / $per) : 0;

            // Determine the last due date (the next unpaid installment's due date)
            $lastDue = $start->copy();
            $steps = min($expectedInstallments, $loan->total_installments);
            for ($i = 0; $i < $steps; $i++) {
                $lastDue = $this->addInterval($lastDue, $interval);
            }

            $isOverdue = $lastDue->lt($thresholdDate) && ($expectedInstallments > $paidInstallments);
            if (! $isOverdue) {
                continue;
            }

            $arrearsInstallments = max(0, $expectedInstallments - $paidInstallments);
            $arrearsAmount = min($remaining, round($arrearsInstallments * $per, 2));

            $rows[] = [
                'loan_id' => $loan->id,
                'qard_id_string' => $loan->qard_id_string,
                'member' => [
                    'id' => $loan->user->id ?? null,
                    'name' => $loan->user->name ?? null,
                    'membership_number' => $loan->user->membership_number ?? null,
                    'branch' => $loan->user->branch->name ?? null,
                ],
                'principal_amount' => (float) $loan->principal_amount,
                'paid_total' => $paid,
                'remaining_principal' => $remaining,
                'per_installment' => $per,
                'interval' => $interval,
                'expected_installments' => $expectedInstallments,
                'paid_installments' => $paidInstallments,
                'arrears_installments' => $arrearsInstallments,
                'arrears_amount' => $arrearsAmount,
                'last_due_date' => $lastDue->toDateString(),
                'days_overdue' => $lastDue->isPast() ? $lastDue->diffInDays($now) : 0,
            ];
        }

        // Sort by arrears amount desc
        usort($rows, fn($a, $b) => $b['arrears_amount'] <=> $a['arrears_amount']);

        return response()->json([
            'count' => count($rows),
            'rows' => array_values($rows),
        ]);
    }

    // 4) Daily Transaction Reconciliation
    public function reconciliation(Request $request)
    {
        $date = $request->query('date');
        $day = $date ? Carbon::parse($date) : Carbon::today();
        $start = $day->copy()->startOfDay();
        $end = $day->copy()->endOfDay();

        $contrib = Contribution::query()
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'success')
            ->select(DB::raw('COUNT(*) as cnt'), DB::raw('SUM(amount) as sum'))
            ->first();

        $wallet = DB::table('wallet_transactions')
            ->whereBetween('created_at', [$start, $end])
            ->where(function($q) {
                $q->where('source', 'paystack')
                  ->orWhere('source', 'paystack_webhook')
                  ->orWhere('source', 'paystack-topup')
                  ->orWhere('source', 'like', '%paystack%');
            })
            ->select(DB::raw('COUNT(*) as cnt'), DB::raw('SUM(amount) as sum'))
            ->first();

        $contribSum = (float) ($contrib->sum ?? 0.0);
        $walletSum = (float) ($wallet->sum ?? 0.0);

        return response()->json([
            'date' => $day->toDateString(),
            'contributions' => [
                'count' => (int) ($contrib->cnt ?? 0),
                'sum' => $contribSum,
            ],
            'wallet_paystack' => [
                'count' => (int) ($wallet->cnt ?? 0),
                'sum' => $walletSum,
            ],
            'discrepancy' => [
                'sum' => round($walletSum - $contribSum, 2),
            ],
        ]);
    }

    // 5) Total Liquidity Report
    public function totalLiquidity(Request $request)
    {
        $total = (float) User::query()->sum('balance');
        return response()->json([
            'total_user_wallet_balance' => $total,
        ]);
    }

    // 6) Financial Audit Trail (recent)
    public function auditTrail(Request $request)
    {
        $limit = (int) $request->query('limit', 100);
        $logs = ShariahAuditLog::query()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        // Attach user names in a single query
        $userMap = User::query()
            ->whereIn('id', $logs->pluck('user_id')->filter()->unique()->values())
            ->pluck('name', 'id');

        $rows = $logs->map(function ($l) use ($userMap) {
            return [
                'id' => $l->id,
                'user_id' => $l->user_id,
                'user_name' => $userMap[$l->user_id] ?? null,
                'action' => $l->action,
                'payload' => $l->payload,
                'created_at' => optional($l->created_at)->toISOString(),
            ];
        });

        return response()->json([
            'rows' => $rows,
        ]);
    }

    // 7) User Growth Analytics
    public function userGrowth(Request $request)
    {
        $days = max(1, (int) $request->query('days', 30));
        $end = Carbon::today();
        $start = $end->copy()->subDays($days - 1);

        $counts = DB::table('users')
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('COUNT(*) as c'))
            ->groupBy('d')
            ->pluck('c', 'd');

        $series = [];
        $cumulative = 0;
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $d = $date->toDateString();
            $count = (int) ($counts[$d] ?? 0);
            $cumulative += $count;
            $series[] = [
                'date' => $d,
                'count' => $count,
                'cumulative' => $cumulative,
            ];
        }

        return response()->json([
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'series' => $series,
        ]);
    }

    // 8) System Health Report
    public function systemHealth(Request $request)
    {
        $days = max(1, (int) $request->query('days', 7));
        $end = Carbon::today();
        $start = $end->copy()->subDays($days - 1);

        $rows = Contribution::query()
            ->whereBetween('created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->select(DB::raw('DATE(created_at) as d'), 'status', DB::raw('COUNT(*) as c'))
            ->groupBy('d', 'status')
            ->get();

        $byDate = [];
        foreach ($rows as $r) {
            $d = $r->d;
            if (! isset($byDate[$d])) {
                $byDate[$d] = ['success' => 0, 'failed' => 0, 'other' => 0];
            }
            $status = strtolower((string) $r->status);
            if ($status === 'success') {
                $byDate[$d]['success'] += (int) $r->c;
            } elseif ($status === 'failed') {
                $byDate[$d]['failed'] += (int) $r->c;
            } else {
                $byDate[$d]['other'] += (int) $r->c;
            }
        }

        $series = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $d = $date->toDateString();
            $succ = $byDate[$d]['success'] ?? 0;
            $fail = $byDate[$d]['failed'] ?? 0;
            $other = $byDate[$d]['other'] ?? 0;
            $total = $succ + $fail + $other;
            $rate = $total > 0 ? round(($succ / $total) * 100, 2) : 0.0;
            $series[] = [
                'date' => $d,
                'success' => $succ,
                'failed' => $fail,
                'other' => $other,
                'total' => $total,
                'success_rate_pct' => $rate,
            ];
        }

        return response()->json([
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'series' => $series,
        ]);
    }

    // Helper to add a single interval to a Carbon date
    private function addInterval(Carbon $date, string $interval): Carbon
    {
        return match (strtolower($interval)) {
            'daily' => $date->copy()->addDay(),
            'weekly' => $date->copy()->addWeek(),
            'quarterly' => $date->copy()->addQuarter(),
            'yearly' => $date->copy()->addYear(),
            default => $date->copy()->addMonth(),
        };
    }
}
