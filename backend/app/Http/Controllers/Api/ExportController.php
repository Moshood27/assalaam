<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\QardHasan;
use App\Models\QardHasanRepayment;
use App\Models\StoreOrder;
use App\Models\WalletTransaction;
use App\Services\AccountingReportService;
use App\Models\Scheme;
use App\Services\ZakatService;
use App\Services\GoldSilverPriceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ExportController extends Controller
{
    protected $zakatService;
    protected $accountingService;
    protected $goldPriceService;

    public function __construct(ZakatService $zakatService, AccountingReportService $accountingService, GoldSilverPriceService $goldPriceService)
    {
        $this->zakatService = $zakatService;
        $this->accountingService = $accountingService;
        $this->goldPriceService = $goldPriceService;
    }

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

            // Build Matrix data for the PDF (matching the UI)
            $startOfYear = Carbon::create($year, 1, 1, 0, 0, 0);
            $yearContributions = $user->contributions()
                ->whereYear('created_at', $year)
                ->where('status', 'success')
                ->get();
            $bfContributions = $user->contributions()
                ->where('created_at', '<', $startOfYear)
                ->where('status', 'success')
                ->get();

            $schemes = Scheme::orderBy('name')->get();
            $matrix = $schemes->map(function ($scheme) use ($yearContributions, $bfContributions) {
                $row = [
                    'scheme_name' => $scheme->name,
                    'months' => array_fill(1, 12, 0),
                    'bf' => 0.0,
                    'total' => 0.0,
                ];
                foreach ($bfContributions as $con) {
                    if ($con->scheme_id === $scheme->id) {
                        $row['bf'] += (float) $con->amount;
                    }
                }
                foreach ($yearContributions as $con) {
                    if ($con->scheme_id === $scheme->id) {
                        $month = $con->created_at->month;
                        $row['months'][$month] += (float) $con->amount;
                        $row['total'] += (float) $con->amount;
                    }
                }
                $row['total'] += $row['bf']; // Include BF in total
                return $row;
            });

            // Combine Savings and Shares into "Passbook" for the member-facing view
            $savingsRowIdx = $matrix->search(fn($r) => $r['scheme_name'] === 'Savings');
            $sharesRowIdx = $matrix->search(fn($r) => $r['scheme_name'] === 'Shares');

            if ($savingsRowIdx !== false && $sharesRowIdx !== false) {
                $savings = $matrix[$savingsRowIdx];
                $shares = $matrix[$sharesRowIdx];

                $passbookRow = [
                    'scheme_name' => 'Passbook (Savings + Shares)',
                    'bf' => $savings['bf'] + $shares['bf'],
                    'months' => array_fill(1, 12, 0),
                    'total' => $savings['total'] + $shares['total'],
                ];
                for ($m = 1; $m <= 12; $m++) {
                    $passbookRow['months'][$m] = $savings['months'][$m] + $shares['months'][$m];
                }

                $matrix->forget($savingsRowIdx);
                $matrix->forget($sharesRowIdx);
                $matrix = collect([$passbookRow])->concat($matrix->values());
            }

            $data = [
                'user' => $user,
                'branch' => optional($user->branch)->name,
                'year' => $year,
                'contributions' => $contributions,
                'matrix' => $matrix,
                'grand_total' => $matrix->sum('total'),
                'bf_total' => $matrix->sum('bf'),
            ];

            $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.passbook', $data);
            return $pdf->download('Coop_Statement_' . $user->membership_number . '.pdf');
        } catch (\Throwable $e) {
            \Log::error('downloadPassbook error', ['exception' => $e->getMessage()]);
            return response()->json(['message' => 'Unable to generate PDF at the moment. Please try again later.'], 422);
        }
    }

    public function downloadPassbookCsv(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        try {
            $year = (int) $request->integer('year', now()->year);
            $contributions = $user->contributions()
                ->with('scheme')
                ->where('status', 'success')
                ->whereYear('created_at', $year)
                ->orderBy('created_at')
                ->get();

            $filename = 'Passbook_' . $year . '_' . $user->membership_number . '.csv';
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];

            $callback = function () use ($contributions) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Date', 'Scheme', 'Reference', 'Amount']);
                foreach ($contributions as $c) {
                    fputcsv($file, [
                        $c->created_at->format('Y-m-d H:i'),
                        optional($c->scheme)->name ?? '-',
                        $c->reference,
                        number_format((float)$c->amount, 2, '.', ''),
                    ]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Throwable $e) {
            \Log::error('downloadPassbookCsv error', ['exception' => $e->getMessage()]);
            return response()->json(['message' => 'Unable to generate CSV.'], 422);
        }
    }

    public function downloadStatement(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        try {
            $format = strtolower((string) $request->query('format', 'pdf'));
            $sixMonthsAgo = now()->subMonths(6)->startOfDay();

            // Calculate opening balance
            $openingBalance = (float) WalletTransaction::where('user_id', $user->id)
                ->where('created_at', '<', $sixMonthsAgo)
                ->selectRaw("SUM(CASE WHEN type = 'credit' THEN amount ELSE -amount END) as balance")
                ->value('balance') ?? 0.0;

            $transactions = WalletTransaction::where('user_id', $user->id)
                ->where('created_at', '>=', $sixMonthsAgo)
                ->orderBy('created_at', 'asc')
                ->get();

            $data = [
                'user' => $user,
                'branch' => optional($user->branch)->name,
                'transactions' => $transactions,
                'opening_balance' => $openingBalance,
                'period' => [
                    'from' => $sixMonthsAgo->format('Y-m-d'),
                    'to' => now()->format('Y-m-d'),
                ],
            ];

            if ($format === 'csv') {
                return $this->generateStatementCsv($data);
            }

            $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.bank_statement', $data);
            return $pdf->download('Statement_' . $user->membership_number . '.pdf');
        } catch (\Throwable $e) {
            \Log::error('downloadStatement error', ['exception' => $e->getMessage()]);
            return response()->json(['message' => 'Unable to generate export at the moment.'], 422);
        }
    }

    private function generateStatementCsv(array $data)
    {
        $user = $data['user'];
        $transactions = $data['transactions'];
        $openingBalance = $data['opening_balance'];

        $filename = 'Statement_' . $user->membership_number . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($transactions, $openingBalance, $data) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Date', 'Description', 'Reference', 'Credit', 'Debit', 'Balance']);

            $currentBalance = (float) $openingBalance;
            fputcsv($file, [$data['period']['from'], 'OPENING BALANCE', '-', '-', '-', number_format($currentBalance, 2, '.', '')]);

            foreach ($transactions as $tx) {
                $isCredit = strtolower((string) $tx->type) === 'credit';
                $amt = (float) $tx->amount;
                $currentBalance += ($isCredit ? $amt : -$amt);

                $desc = ucwords(str_replace('_', ' ', (string) $tx->source));
                $meta = is_array($tx->meta) ? $tx->meta : json_decode((string)$tx->meta, true);

                if ($tx->source === 'p2p_transfer') {
                    if ($isCredit && isset($meta['from_name'])) {
                        $desc .= " from " . $meta['from_name'];
                    } elseif (!$isCredit && isset($meta['to_name'])) {
                        $desc .= " to " . $meta['to_name'];
                    }
                }

                if (!empty($meta['maintenance_charge'])) {
                    $desc .= " (Net of ₦" . number_format((float)$meta['maintenance_charge'], 2) . " fee)";
                }

                fputcsv($file, [
                    $tx->created_at->format('Y-m-d H:i'),
                    $desc,
                    $tx->reference,
                    $isCredit ? number_format($amt, 2, '.', '') : '',
                    ! $isCredit ? number_format($amt, 2, '.', '') : '',
                    number_format($currentBalance, 2, '.', ''),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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

    public function downloadLoanAgreement(Request $request, int $id)
    {
        set_time_limit(120);
        $user = $request->user();

        $q = QardHasan::query()->where('id', $id);

        // If not admin, restrict to own loan
        if (!$user->is_admin) {
            $q->where('user_id', $user->id);
        }

        $loan = $q->firstOrFail();
        $borrower = $loan->user;

        // Generate schedule (simplified for agreement)
        $interval = strtolower((string) $loan->interval);
        $cursor = ($loan->approved_at ?: ($loan->created_at ?: now()))->copy();

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

        $data = [
            'user' => $borrower,
            'loan' => $loan,
            'schedule' => $schedule,
        ];

        $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.loan_agreement', $data);
        $filename = 'Loan_Agreement_' . $loan->qard_id_string . '.pdf';
        return $pdf->download($filename);
    }

    public function downloadMurabahahAgreement(Request $request, int $id)
    {
        set_time_limit(120);
        $user = $request->user();

        $q = StoreOrder::with('items')->where('id', $id);

        // If not admin, restrict to own order
        if (!$user->is_admin) {
            $q->where('user_id', $user->id);
        }

        $order = $q->firstOrFail();

        $meta = is_array($order->meta) ? $order->meta : [];
        if (($meta['financing']['type'] ?? null) !== 'murabaha') {
            return response()->json(['message' => 'This order is not under Murabahah financing'], 422);
        }

        $data = [
            'user' => $order->user,
            'order' => $order,
        ];

        $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.murabahah_agreement', $data);
        $filename = 'Murabahah_Agreement_' . $order->reference . '.pdf';
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
        $from = Carbon::create($year, 1, 1)->toDateString();
        $to = Carbon::create($year, 12, 31)->toDateString();
        $data = $this->accountingService->buildAppropriationAccount($from, $to);
        $data['user'] = $request->user();
        $data['year'] = $year;
        $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.appropriation', $data);
        $filename = 'Appropriation_Account_' . $year . '.pdf';
        return $pdf->download($filename);
    }

    public function downloadFinancials(Request $request, int $year)
    {
        $from = Carbon::create($year, 1, 1)->toDateString();
        $to = Carbon::create($year, 12, 31)->toDateString();
        $ie = $this->accountingService->buildIncomeAndExpenditure($from, $to);
        $bs = $this->accountingService->buildBalanceSheet($to);
        $cf = $this->accountingService->buildStatementOfCashFlows($from, $to);
        $data = [
            'user' => $request->user(),
            'year' => $year,
            'income_expenditure' => $ie,
            'balance_sheet' => $bs,
            'cash_flow' => $cf,
        ];
        $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.financials', $data);
        $filename = 'Financial_Statements_' . $year . '.pdf';
        return $pdf->download($filename);
    }

    public function downloadCashFlow(Request $request, int $year)
    {
        $from = Carbon::create($year, 1, 1)->toDateString();
        $to = Carbon::create($year, 12, 31)->toDateString();
        $data = $this->accountingService->buildStatementOfCashFlows($from, $to);
        $data['user'] = $request->user();
        $data['year'] = $year;
        $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.cash_flow', $data);
        $filename = 'Cash_Flow_Statement_' . $year . '.pdf';
        return $pdf->download($filename);
    }

    public function downloadCharityReport(Request $request, int $year)
    {
        $from = Carbon::create($year, 1, 1)->toDateString();
        $to = Carbon::create($year, 12, 31)->toDateString();
        $data = $this->accountingService->buildCharityFundReport($from, $to);
        $data['user'] = $request->user();
        $data['year'] = $year;
        $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.charity_report', $data);
        $filename = 'Charity_Fund_Report_' . $year . '.pdf';
        return $pdf->download($filename);
    }

    public function downloadProjectRoiReport(Request $request)
    {
        $data = [
            'projects' => $this->accountingService->buildProjectRoiReport(),
            'user' => $request->user(),
            'date' => now()->toDateString(),
        ];
        $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.project_roi', $data);
        $filename = 'Project_ROI_Report_' . now()->format('Ymd') . '.pdf';
        return $pdf->download($filename);
    }

    public function downloadVendorSettlementReport(Request $request)
    {
        $data = [
            'vendors' => $this->accountingService->buildVendorSettlementReport(),
            'user' => $request->user(),
            'date' => now()->toDateString(),
        ];
        $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.vendor_settlement', $data);
        $filename = 'Vendor_Settlement_Report_' . now()->format('Ymd') . '.pdf';
        return $pdf->download($filename);
    }

    public function downloadAttendanceReport(Request $request, int $year)
    {
        $from = Carbon::create($year, 1, 1)->toDateString();
        $to = Carbon::create($year, 12, 31)->toDateString();
        $data = [
            'meetings' => $this->accountingService->buildAttendanceReport($from, $to),
            'user' => $request->user(),
            'year' => $year,
        ];
        $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.attendance_report', $data);
        $filename = 'Attendance_Fine_Summary_' . $year . '.pdf';
        return $pdf->download($filename);
    }

    public function downloadShariaAuditReport(Request $request, int $year)
    {
        $from = Carbon::create($year, 1, 1)->toDateString();
        $to = Carbon::create($year, 12, 31)->toDateString();
        $data = $this->accountingService->buildShariaAuditReport($from, $to);
        $data['user'] = $request->user();
        $data['year'] = $year;
        $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.sharia_audit', $data);
        $filename = 'Sharia_Audit_Report_' . $year . '.pdf';
        return $pdf->download($filename);
    }

    public function downloadLoanAgingReport(Request $request)
    {
        $data = [
            'loans' => $this->accountingService->buildLoanAgingReport(),
            'user' => $request->user(),
            'date' => now()->toDateString(),
        ];
        $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.loan_aging', $data);
        $filename = 'Loan_Aging_Report_' . now()->format('Ymd') . '.pdf';
        return $pdf->download($filename);
    }

    public function downloadTakafulReport(Request $request)
    {
        $data = $this->accountingService->buildTakafulPoolReport();
        $data['user'] = $request->user();
        $data['date'] = now()->toDateString();
        $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.takaful_summary', $data);
        $filename = 'Takaful_Pool_Report_' . now()->format('Ymd') . '.pdf';
        return $pdf->download($filename);
    }

    public function downloadGoldReport(Request $request)
    {
        $goldPrice = $this->goldPriceService->getGoldPrice() ?: (float)$request->query('gold_price', 100000);
        $data = $this->accountingService->buildGoldSavingsReport($goldPrice);
        $data['user'] = $request->user();
        $data['date'] = now()->toDateString();
        $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.gold_valuation', $data);
        $filename = 'Gold_Savings_Report_' . now()->format('Ymd') . '.pdf';
        return $pdf->download($filename);
    }

    public function downloadCoopZakatReport(Request $request)
    {
        $goldPrice = $this->goldPriceService->getGoldPrice() ?: (float)$request->query('gold_price', 100000);
        $data = $this->accountingService->buildZakatReport($goldPrice);
        $data['user'] = $request->user();
        $data['date'] = now()->toDateString();
        $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.zakat_coop_report', $data);
        $filename = 'Cooperative_Zakat_Report_' . now()->format('Ymd') . '.pdf';
        return $pdf->download($filename);
    }

    public function downloadAuditTrail(Request $request)
    {
        $days = (int)$request->query('days', 30);
        $activities = \Spatie\Activitylog\Models\Activity::where('created_at', '>=', now()->subDays($days))
            ->with('causer')
            ->orderBy('created_at', 'desc')
            ->get();

        $data = [
            'activities' => $activities,
            'user' => $request->user(),
            'days' => $days,
            'date' => now()->toDateString(),
        ];
        $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.audit_trail', $data);
        $filename = 'Audit_Trail_Report_' . now()->format('Ymd') . '.pdf';
        return $pdf->download($filename);
    }

    public function downloadWalletReceipt(Request $request, int $id)
    {
        $user = $request->user();
        // Only allow member to download their own receipts
        $tx = WalletTransaction::where('id', $id)->where('user_id', $user->id)->first();
        if (!$tx) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        // Build data for receipt
        $branch = optional($user->branch)->name;
        $data = [
            'user' => $user,
            'branch' => $branch,
            'tx' => $tx,
        ];

        try {
            $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.wallet_receipt', $data);
            $filename = 'Wallet_Receipt_' . ($tx->reference ?: ('TX'.$tx->id)) . '.pdf';
            return $pdf->download($filename);
        } catch (\Throwable $e) {
            \Log::error('downloadWalletReceipt error', ['exception' => $e->getMessage(), 'tx_id' => $id]);
            return response()->json(['message' => 'Unable to generate receipt at the moment. Please try again later.'], 422);
        }
    }

    public function downloadOrderReceipt(Request $request, int $id)
    {
        $user = $request->user();
        // Only allow member to download their own receipts
        $order = StoreOrder::with('items')->where('id', $id)->where('user_id', $user->id)->first();
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Build data for receipt
        $branch = optional($user->branch)->name;
        $data = [
            'user' => $user,
            'branch' => $branch,
            'order' => $order,
        ];

        try {
            $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.order_receipt', $data);
            $filename = 'Order_Receipt_' . ($order->reference ?: ('ORD'.$order->id)) . '.pdf';
            return $pdf->download($filename);
        } catch (\Throwable $e) {
            \Log::error('downloadOrderReceipt error', ['exception' => $e->getMessage(), 'order_id' => $id]);
            return response()->json(['message' => 'Unable to generate receipt at the moment. Please try again later.'], 422);
        }
    }

    public function downloadZakatReport(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        try {
            $data = $this->zakatService->getEstimate($user);
            $data['branch'] = optional($user->branch)->name;

            $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.zakat_report', $data);
            $filename = 'Zakat_Report_' . $user->membership_number . '_' . now()->format('Ymd') . '.pdf';
            return $pdf->download($filename);
        } catch (\Throwable $e) {
            \Log::error('downloadZakatReport error', ['exception' => $e->getMessage(), 'user_id' => $user->id]);
            return response()->json(['message' => 'Unable to generate Zakat report at the moment.'], 422);
        }
    }

    public function downloadMemberZakatPortfolio(Request $request)
    {
        $year = (int)$request->query('year', now()->year);
        $from = Carbon::create($year, 1, 1)->toDateString();
        $to = Carbon::create($year, 12, 31)->toDateString();

        $data = $this->accountingService->buildMemberZakatPortfolio($from, $to);
        $data['user'] = $request->user();
        $data['year'] = $year;

        $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.zakat_portfolio', $data);
        $filename = 'Member_Zakat_Portfolio_' . $year . '.pdf';
        return $pdf->download($filename);
    }

    public function downloadProjectDistribution(Request $request, int $id)
    {
        $data = $this->accountingService->buildProjectDistributionReport($id);
        $data['user'] = $request->user();
        $data['date'] = now()->toDateString();

        $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.project_distribution', $data);
        $filename = 'Project_Distribution_Report_' . $id . '_' . now()->format('Ymd') . '.pdf';
        return $pdf->download($filename);
    }

    public function downloadMemberSavingsLedger(Request $request, ?int $userId = null)
    {
        $user = $request->user();
        $targetId = $userId ?: $user->id;

        if (!$user->is_admin && $user->id != $targetId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $this->accountingService->buildMemberSavingsLedger($targetId);
        $data['admin_user'] = $user;
        $data['date'] = now()->toDateString();

        $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.savings_ledger', $data);
        $filename = 'Savings_Ledger_' . $data['membership_number'] . '.pdf';
        return $pdf->download($filename);
    }

    public function downloadMembershipForm(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        try {
            $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.membership_application', ['application' => $user]);
            $filename = 'Membership_Form_' . $user->membership_number . '.pdf';
            return $pdf->download($filename);
        } catch (\Throwable $e) {
            \Log::error('downloadMembershipForm error', ['exception' => $e->getMessage(), 'user_id' => $user->id]);
            return response()->json(['message' => 'Unable to generate Membership Form at the moment.'], 422);
        }
    }

    public function downloadImamAttestation(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        try {
            $pdf = Pdf::setOptions(['isHtml5ParserEnabled' => false])->loadView('pdfs.imam_attestation', ['application' => $user]);
            $filename = 'Imam_Attestation_' . $user->membership_number . '.pdf';
            return $pdf->download($filename);
        } catch (\Throwable $e) {
            \Log::error('downloadImamAttestation error', ['exception' => $e->getMessage(), 'user_id' => $user->id]);
            return response()->json(['message' => 'Unable to generate Imam Attestation at the moment.'], 422);
        }
    }
}
