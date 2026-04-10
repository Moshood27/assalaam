<?php

namespace App\Filament\Pages;

use App\Services\AccountingReportService;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BalanceSheet extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Financial Reports';
    protected static ?string $navigationLabel = 'Balance Sheet';
    protected static ?int $navigationSort = 12;

    protected static string $view = 'filament.pages.balance-sheet';

    public function getSubheading(): ?string
    {
        return 'Financial snapshot of assets, liabilities, and equity at a specific time.';
    }

    public ?string $as_of = null;
    public array $report = [];

    public function mount(): void
    {
        $this->as_of = now()->toDateString();
        $this->refreshReport();
    }

    public function updated($name, $value): void
    {
        if ($name === 'as_of') {
            $this->refreshReport();
        }
    }

    public function refreshReport(): void
    {
        /** @var AccountingReportService $svc */
        $svc = app(AccountingReportService::class);
        $this->report = $svc->buildBalanceSheet($this->as_of);
    }

    public function exportCsv(): StreamedResponse
    {
        $data = $this->report;
        $headers = [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
            'Content-Disposition' => 'attachment; filename="balance-sheet.csv"',
        ];
        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Balance Sheet as of', $data['as_of'] ?? '']);
            fputcsv($out, []);
            fputcsv($out, ['Assets', 'Amount']);
            foreach ($data['assets'] as $line) {
                fputcsv($out, [$line['name'], number_format($line['amount'], 2, '.', '')]);
            }
            fputcsv($out, ['Total Assets', number_format($data['total_assets'], 2, '.', '')]);
            fputcsv($out, []);
            fputcsv($out, ['Liabilities & Equity', 'Amount']);
            foreach ($data['liabilities'] as $line) {
                fputcsv($out, [$line['name'], number_format($line['amount'], 2, '.', '')]);
            }
            fputcsv($out, ['Total Liabilities & Equity', number_format($data['total_liabilities_and_equity'], 2, '.', '')]);
            fclose($out);
        }, 'balance-sheet.csv', $headers);
    }
}
