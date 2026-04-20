<?php

namespace App\Filament\Pages;

use App\Services\AccountingReportService;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QardHasanBranchReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Financial Reports';
    protected static ?string $navigationLabel = 'Qard Hasan Branch Report';
    protected static ?int $navigationSort = 15;

    protected static string $view = 'filament.pages.qard-hasan-branch-report';

    public function getTitle(): string
    {
        return $this->onlyDefaulted ? 'Defaulted Qard Hasan Report' : 'Qard Hasan Branch Report';
    }

    public function getSubheading(): ?string
    {
        return $this->onlyDefaulted
            ? 'Detailed list of defaulted Qard Hasan loans grouped by branch.'
            : 'Detailed list of outstanding Qard Hasan loans grouped by branch.';
    }

    public static function canAccess(): bool
    {
        return auth()->user()->can('view_any_qard_hasan');
    }

    public array $report = [
        'branches' => [],
        'grand_total_principal' => 0,
        'grand_total_paid' => 0,
        'grand_total_outstanding' => 0,
        'grand_total_loans_count' => 0,
    ];

    public ?int $branchId = null;
    public ?string $from = null;
    public ?string $to = null;
    public bool $onlyDefaulted = false;

    public function mount(): void
    {
        $this->to = now()->toDateString();
        $user = auth()->user();
        if (!$user->hasRole('super_admin')) {
            $this->branchId = $user->branch_id;
        }
        $this->refreshReport();
    }

    public function updated($name): void
    {
        if (in_array($name, ['branchId', 'from', 'to', 'onlyDefaulted'])) {
            $this->refreshReport();
        }
    }

    public function refreshReport(): void
    {
        /** @var AccountingReportService $svc */
        $svc = app(AccountingReportService::class);
        $user = auth()->user();

        $targetBranchId = $user->hasRole('super_admin') ? $this->branchId : $user->branch_id;
        $this->report = $svc->buildBranchQardHasanReport($targetBranchId, $this->from, $this->to, $this->onlyDefaulted);
    }

    public function exportCsv(): StreamedResponse
    {
        $data = $this->report;
        $filename = $this->onlyDefaulted ? 'qard-hasan-default-report.csv' : 'qard-hasan-branch-report.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Branch', 'Member', 'Loan ID', 'Principal', 'Paid', 'Overdue', 'Outstanding', 'Last Payment', 'Status']);

            foreach ($data['branches'] as $branch) {
                foreach ($branch['loans'] as $loan) {
                    fputcsv($out, [
                        $branch['branch_name'],
                        $loan['member_name'],
                        $loan['loan_id'],
                        number_format($loan['principal'], 2, '.', ''),
                        number_format($loan['paid'], 2, '.', ''),
                        number_format($loan['overdue'], 2, '.', ''),
                        number_format($loan['outstanding'], 2, '.', ''),
                        $loan['last_payment_date'] ?? 'N/A',
                        $loan['status'],
                    ]);
                }
                // Branch total
                fputcsv($out, [
                    $branch['branch_name'] . ' TOTAL',
                    '',
                    '',
                    number_format($branch['total_principal'], 2, '.', ''),
                    number_format($branch['total_paid'], 2, '.', ''),
                    number_format($branch['total_outstanding'], 2, '.', ''),
                    '',
                    '',
                ]);
                fputcsv($out, []); // Empty line between branches
            }

            // Grand total
            if (count($data['branches']) > 1) {
                fputcsv($out, [
                    'GRAND TOTAL',
                    $data['grand_total_loans_count'] . ' loans',
                    '',
                    number_format($data['grand_total_principal'], 2, '.', ''),
                    number_format($data['grand_total_paid'], 2, '.', ''),
                    number_format($data['grand_total_outstanding'], 2, '.', ''),
                    '',
                    '',
                ]);
            }

            fclose($out);
        }, $filename, $headers);
    }
}
