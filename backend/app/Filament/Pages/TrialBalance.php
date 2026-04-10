<?php

namespace App\Filament\Pages;

use App\Services\AccountingReportService;
use Filament\Pages\Page;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrialBalance extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static ?string $navigationGroup = 'Financial Reports';
    protected static ?string $navigationLabel = 'Trial Balance';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.trial-balance';

    public function getSubheading(): ?string
    {
        return 'Comprehensive list of all ledger balances for auditing and verification.';
    }

    public ?string $from = null;
    public ?string $to = null;

    public array $report = [];

    public function mount(): void
    {
        $this->to = now()->toDateString();
        $this->refreshReport();
    }

    public function updated($name, $value): void
    {
        if (in_array($name, ['from', 'to'])) {
            $this->refreshReport();
        }
    }

    public function refreshReport(): void
    {
        /** @var AccountingReportService $svc */
        $svc = app(AccountingReportService::class);
        $this->report = $svc->buildTrialBalance($this->from, $this->to);
    }

    public function exportCsv(): StreamedResponse
    {
        $data = $this->report;
        $headers = [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
            'Content-Disposition' => 'attachment; filename="trial-balance.csv"',
        ];
        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Account', 'Debit', 'Credit']);
            foreach ($data['accounts'] as $name => $row) {
                fputcsv($out, [$name, number_format($row['debit'], 2, '.', ''), number_format($row['credit'], 2, '.', '')]);
            }
            fputcsv($out, ['TOTAL', number_format($data['total_debit'], 2, '.', ''), number_format($data['total_credit'], 2, '.', '')]);
            fclose($out);
        }, 'trial-balance.csv', $headers);
    }
}
