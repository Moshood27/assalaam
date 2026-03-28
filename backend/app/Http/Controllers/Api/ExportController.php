<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\QardHasan;
use App\Models\QardHasanRepayment;
use App\Services\AccountingReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExportController extends Controller
{
    public function downloadPassbook(Request $request)
    {
        // Ensure user is authenticated (Sanctum)
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated. Please login again.'], 401);
        }

        try {
            // Allow optional year filter to reduce payload size (defaults to current year)
            $year = (int) $request->integer('year', now()->year);

            $contributions = $user->contributions()
                ->with('scheme')
                ->where('status', 'success')
                ->when($year > 0, function ($q) use ($year) {
                    $q->whereYear('created_at', $year);
                })
                ->orderBy('created_at')
                ->get();

            $data = [
                'user' => $user,
                'branch' => optional($user->branch)->name,
                'year' => $year,
                'contributions' => $contributions,
            ];

            $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.passbook', $data);
            return $pdf->download('Coop_Statement_' . $user->membership_number . '.pdf');
        } catch (\Throwable $e) {
            \Log::error('downloadPassbook error', ['exception' => $e->getMessage()]);
            return response()->json(['message' => 'Unable to generate PDF at the moment. Please try again later.'], 422);
        }
    }

    public function downloadLoanSchedule(Request $request, int $id)
    {
        // Increase execution time to 2 minutes for this specific request
        set_time_limit(120);
        $user = $request->user();

        $loan = QardHasan::query()
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $repayments = QardHasanRepayment::query()
            ->where('qard_hasan_id', $loan->id)
            ->where('status', 'success')
            ->orderBy('paid_at')
            ->get();

        $paidTotal = (float) $repayments->sum('amount');

        // Build schedule (same logic as API schedule)
        $interval = strtolower((string) $loan->interval);
        $cursor = $loan->created_at ? $loan->created_at->copy() : now();
        $add = function ($date) use ($interval) {
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
            ];
            $balance -= $applied;
        }

        // Mark paid installments by applying repayments in order
        $remainingToApply = $paidTotal;
        foreach ($schedule as &$item) {
            if ($remainingToApply <= 0) {
                $item['status'] = 'pending';
                $item['paid_amount'] = 0.0;
                continue;
            }
            $apply = min($item['installment_amount'], $remainingToApply);
            $remainingToApply -= $apply;
            $item['paid_amount'] = round($apply, 2);
            $item['status'] = $apply >= $item['installment_amount'] ? 'paid' : 'partial';
        }
        unset($item);

        $data = [
            'user' => $user,
            'loan' => $loan,
            'schedule' => $schedule,
            'paid_total' => $paidTotal,
            'remaining_principal' => round(max(0.0, (float) $loan->principal_amount - $paidTotal), 2),
        ];

        $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.loan_schedule', $data);
        $filename = 'Loan_Schedule_' . $loan->qard_id_string . '.pdf';
        return $pdf->download($filename);
    }

    public function downloadDividend(Request $request, int $year)
    {
        try {
            $user = $request->user();
            $rate = (float) config('coop.dividend_rate', env('DIVIDEND_RATE', 0.05));

            $totalSavings = (float) Contribution::query()
                ->where('user_id', $user->id)
                ->where('status', 'success')
                ->whereYear('created_at', $year)
                ->sum('amount');

            $dividend = round($totalSavings * $rate, 2);

            $data = [
                'user' => $user,
                'year' => $year,
                'total_savings' => $totalSavings,
                'rate' => $rate,
                'dividend' => $dividend,
            ];

            $pdf = Pdf::loadView('pdfs.dividend', $data)->setOptions(['isHtml5ParserEnabled' => false]);
            $filename = 'Dividend_Statement_' . $year . '_' . $user->membership_number . '.pdf';
            return $pdf->download($filename);
        } catch (\Throwable $e) {
            \Log::error('downloadDividend error', ['exception' => $e->getMessage()]);
            return response()->json(['message' => 'Unable to generate Dividend PDF at the moment.'], 422);
        }
    }

    public function downloadAppropriation(Request $request, int $year)
    {
        /** @var AccountingReportService $svc */
        $svc = app(AccountingReportService::class);
        $from = Carbon::create($year, 1, 1)->toDateString();
        $to = Carbon::create($year, 12, 31)->toDateString();
        $data = $svc->buildAppropriationAccount($from, $to);
        $data['user'] = $request->user();
        $data['year'] = $year;
        $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.appropriation', $data);
        $filename = 'Appropriation_Account_' . $year . '.pdf';
        return $pdf->download($filename);
    }

    public function downloadFinancials(Request $request, int $year)
    {
        /** @var AccountingReportService $svc */
        $svc = app(AccountingReportService::class);
        $from = Carbon::create($year, 1, 1)->toDateString();
        $to = Carbon::create($year, 12, 31)->toDateString();
        $ie = $svc->buildIncomeAndExpenditure($from, $to);
        $bs = $svc->buildBalanceSheet($to);
        $data = [
            'user' => $request->user(),
            'year' => $year,
            'income_expenditure' => $ie,
            'balance_sheet' => $bs,
        ];
        $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.financials', $data);
        $filename = 'Financial_Statements_' . $year . '.pdf';
        return $pdf->download($filename);
    }
}
