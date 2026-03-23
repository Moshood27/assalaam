<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\QardHasan;
use App\Models\QardHasanRepayment;
use App\Models\Scheme;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    // Member: Contribution Mix Report
    public function contributionMix(Request $request)
    {
        $user = $request->user();

        $rows = Contribution::query()
            ->select('scheme_id', DB::raw('SUM(amount) as total'))
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->groupBy('scheme_id')
            ->with('scheme')
            ->get();

        $total = (float) $rows->sum('total');
        $data = $rows->map(function ($r) use ($total) {
            $pct = $total > 0 ? round(((float)$r->total / $total) * 100, 2) : 0.0;
            return [
                'scheme_id' => $r->scheme_id,
                'scheme_name' => optional($r->scheme)->name ?? 'Unknown',
                'amount' => (float) $r->total,
                'percentage' => $pct,
            ];
        })->values();

        return response()->json([
            'total' => $total,
            'breakdown' => $data,
        ]);
    }

    // Member: Loan Amortization Schedule (for Qard Hasan)
    public function loanSchedule(Request $request, int $id)
    {
        $user = $request->user();
        $loan = QardHasan::query()->where('id', $id)->where('user_id', $user->id)->firstOrFail();
        $repayments = QardHasanRepayment::query()
            ->where('qard_hasan_id', $loan->id)
            ->where('status', 'success')
            ->orderBy('paid_at')
            ->get();

        $paidTotal = (float) $repayments->sum('amount');
        $remaining = max(0.0, (float)$loan->principal_amount - $paidTotal);

        // Build schedule based on interval and per_installment
        $interval = strtolower((string) $loan->interval);
        $cursor = $loan->created_at ? $loan->created_at->copy() : now();
        $add = function($date) use ($interval) {
            return match ($interval) {
                'weekly' => $date->copy()->addWeek(),
                'daily' => $date->copy()->addDay(),
                'quarterly' => $date->copy()->addQuarter(),
                'yearly' => $date->copy()->addYear(),
                default => $date->copy()->addMonth(),
            };
        };

        $schedule = [];
        $balance = (float) $loan->principal_amount;
        $installment = (float) $loan->per_installment;
        $totalInstallments = (int) $loan->total_installments;

        for ($i = 1; $i <= $totalInstallments; $i++) {
            $cursor = $add($cursor);
            $applied = min($installment, $balance);
            $schedule[] = [
                'sequence' => $i,
                'due_date' => $cursor->toDateString(),
                'installment_amount' => round($applied, 2),
                'balance_after' => round(max(0.0, $balance - $applied), 2),
            ];
            $balance -= $applied;
        }

        // Mark paid installments by applying repayments in order
        $remainingToApply = $paidTotal;
        foreach ($schedule as &$item) {
            if ($remainingToApply <= 0) {
                $item['status'] = 'pending';
                continue;
            }
            $apply = min($item['installment_amount'], $remainingToApply);
            $remainingToApply -= $apply;
            $item['paid_amount'] = round($apply, 2);
            $item['status'] = $apply >= $item['installment_amount'] ? 'paid' : 'partial';
        }
        unset($item);

        return response()->json([
            'loan' => $loan,
            'repayments' => $repayments,
            'schedule' => $schedule,
            'paid_total' => $paidTotal,
            'remaining_principal' => round($remaining, 2),
        ]);
    }

    // Member: Annual Dividend Statement
    public function dividend(Request $request, int $year)
    {
        $user = $request->user();
        $rate = (float) config('coop.dividend_rate', env('DIVIDEND_RATE', 0.05));

        $totalSavings = Contribution::query()
            ->where('user_id', $user->id)
            ->where('status', 'success')
            ->whereYear('created_at', $year)
            ->sum('amount');

        $dividend = round((float)$totalSavings * $rate, 2);

        return response()->json([
            'year' => $year,
            'total_savings' => (float) $totalSavings,
            'rate' => $rate,
            'dividend' => $dividend,
        ]);
    }
}
