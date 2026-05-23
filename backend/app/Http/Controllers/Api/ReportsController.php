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

        $paidTotal = max((float) $loan->paid_amount, (float) $repayments->sum('amount'));
        $remaining = max(0.0, (float)$loan->principal_amount - $paidTotal);

        $baseSchedule = $loan->generateInstallmentSchedule();
        $schedule = [];
        $balance = (float) $loan->principal_amount;

        foreach ($baseSchedule as $item) {
            $applied = min((float)$item['amount'], $balance);
            $schedule[] = [
                'sequence' => $item['index'],
                'due_date' => $item['due_date'],
                'installment_amount' => round($applied, 2),
                'balance_after' => round(max(0.0, $balance - $applied), 2),
            ];
            $balance -= $applied;
        }

        // Ensure schedule is ascending by due_date BEFORE applying repayments
        usort($schedule, fn($a, $b) => strcmp($a['due_date'], $b['due_date']));

        // Mark paid installments by applying repayments in order
        $remainingToApply = $paidTotal;
        $now = now();
        foreach ($schedule as &$item) {
            if ($remainingToApply <= 0.01) {
                $isOverdue = Carbon::parse($item['due_date'])->lessThan($now);
                $item['status'] = $isOverdue ? 'overdue' : 'pending';
                $item['paid_amount'] = 0.0;
                continue;
            }
            $apply = min($item['installment_amount'], $remainingToApply);
            $remainingToApply -= $apply;
            $item['paid_amount'] = round($apply, 2);

            if ($apply >= $item['installment_amount'] - 0.01) {
                $item['status'] = 'paid';
            } else {
                $item['status'] = 'partial';
            }
        }
        unset($item);

        // Determine next due installment helper
        $nextDue = null;
        foreach ($schedule as $it) {
            if (($it['status'] ?? 'pending') !== 'paid') {
                $dueAmt = (float) $it['installment_amount'] - (float) ($it['paid_amount'] ?? 0.0);
                if ($dueAmt > 0.0) {
                    $nextDue = [
                        'sequence' => $it['sequence'],
                        'due_date' => $it['due_date'],
                        'amount_due' => round($dueAmt, 2),
                    ];
                    break;
                }
            }
        }

        return response()->json([
            'loan' => $loan,
            'repayments' => $repayments,
            'schedule' => $schedule,
            'paid_total' => $paidTotal,
            'remaining_principal' => round($remaining, 2),
            'next_due' => $nextDue,
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
