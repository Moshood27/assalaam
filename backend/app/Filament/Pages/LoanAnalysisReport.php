<?php

namespace App\Filament\Pages;

use App\Services\AccountingReportService;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Branch;

class LoanAnalysisReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Financial Reports';
    protected static ?string $navigationLabel = 'Loan Analysis Report';
    protected static ?int $navigationSort = 17;

    protected static string $view = 'filament.pages.loan-analysis-report';

    public function getSubheading(): ?string
    {
        return 'Comprehensive loan analysis report including repayments, defaults, and savings balances.';
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('view_any_qard_hasan');
    }

    public array $report = [
        'rows' => [],
        'totals' => [
            'loan_granted' => 0.0,
            'amount_repaid' => 0.0,
            'expected_amount_to_pay' => 0.0,
            'amount_defaulted' => 0.0,
            'loan_balance' => 0.0,
            'savings_balance' => 0.0,
        ],
        'month' => '',
        'year' => '',
        'cooperative_name' => 'AS-SALAAM C.I.C.S.',
    ];

    public ?int $branchId = null;
    public ?string $date = null;
    public ?string $search = null;

    public function mount(): void
    {
        $this->date = now()->toDateString();
        $user = auth()->user();
        if (!$user->hasRole('super_admin')) {
            $this->branchId = $user->branch_id;
        }
        $this->refreshReport();
    }

    public function updated($name): void
    {
        if (in_array($name, ['branchId', 'date', 'search'])) {
            $this->refreshReport();
        }
    }

    public function refreshReport(): void
    {
        /** @var AccountingReportService $svc */
        $svc = app(AccountingReportService::class);
        $user = auth()->user();

        $targetBranchId = $user->hasRole('super_admin') ? $this->branchId : $user->branch_id;
        $this->report = $svc->buildLoanAnalysisReport($targetBranchId, $this->date, $this->search);
    }

    public function exportCsv(): StreamedResponse
    {
        $data = $this->report;
        $filename = 'loan-analysis-report-' . ($this->date ?: now()->toDateString()) . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [$data['cooperative_name'] . ' LOAN ANALYSIS REPORT AS AT MONTH OF ' . strtoupper($data['month']) . ' ' . $data['year']]);
            fputcsv($out, ['S/N', 'NAME OF MEMBERS AND BRANCH', 'DATE GRANTED', 'LOAN GRANTED', 'AMOUNT REPAID', 'EXPECTED AMOUNT TO PAY', 'AMOUNT DEFAULTED', 'LOAN BALANCE', 'SHARE/SAVINGS BALANCE', 'PHONE NUMBER', 'PERIOD OF DEFAULT']);

            foreach ($data['rows'] as $row) {
                fputcsv($out, [
                    $row['sn'],
                    $row['member_name'] . ' (' . $row['branch_name'] . ')',
                    $row['date_granted'] instanceof \Carbon\Carbon ? $row['date_granted']->format('d-m-Y') : $row['date_granted'],
                    number_format($row['loan_granted'], 2, '.', ''),
                    number_format($row['amount_repaid'], 2, '.', ''),
                    number_format($row['expected_amount_to_pay'], 2, '.', ''),
                    number_format($row['amount_defaulted'], 2, '.', ''),
                    number_format($row['loan_balance'], 2, '.', ''),
                    number_format($row['savings_balance'], 2, '.', ''),
                    $row['phone_number'],
                    $row['period_of_default'],
                ]);
            }

            fputcsv($out, [
                'TOTAL',
                '',
                '',
                number_format($data['totals']['loan_granted'], 2, '.', ''),
                number_format($data['totals']['amount_repaid'], 2, '.', ''),
                number_format($data['totals']['expected_amount_to_pay'], 2, '.', ''),
                number_format($data['totals']['amount_defaulted'], 2, '.', ''),
                number_format($data['totals']['loan_balance'], 2, '.', ''),
                number_format($data['totals']['savings_balance'], 2, '.', ''),
                '',
                ''
            ]);

            fclose($out);
        }, $filename, $headers);
    }
}
