<?php

namespace App\Filament\Pages;

use App\Services\AccountingReportService;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AppropriationAccount extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static ?string $navigationGroup = 'Financial Reports';
    protected static ?string $navigationLabel = 'Appropriation Account';
    protected static ?int $navigationSort = 13;

    protected static string $view = 'filament.pages.appropriation-account';

    public ?string $from = null;
    public ?string $to = null;

    public array $report = [];

    public function mount(): void
    {
        $this->from = now()->startOfYear()->toDateString();
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
        $this->report = $svc->buildAppropriationAccount($this->from, $this->to);
    }

    public function exportCsv(): StreamedResponse
    {
        $data = $this->report;
        $headers = [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
            'Content-Disposition' => 'attachment; filename="appropriation-account.csv"',
        ];
        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Appropriation Account', ($data['from'] ?? '') . ' to ' . ($data['to'] ?? '')]);
            fputcsv($out, []);
            fputcsv($out, ['Surplus for the Period', number_format((float)($data['surplus'] ?? 0), 2, '.', '')]);
            fputcsv($out, []);
            fputcsv($out, ['Appropriations', 'Amount']);
            foreach (($data['appropriations'] ?? []) as $line) {
                $name = $line['name'] ?? '';
                $amt = (float)($line['amount'] ?? 0);
                $pct = isset($line['percent']) ? ' (' . number_format((float)$line['percent'], 2, '.', '') . '%)' : '';
                fputcsv($out, [$name . $pct, number_format($amt, 2, '.', '')]);
            }
            fputcsv($out, ['Total Appropriations', number_format((float)($data['total_appropriated'] ?? 0), 2, '.', '')]);
            fputcsv($out, ['Carried Forward', number_format((float)($data['carried_forward'] ?? 0), 2, '.', '')]);
            fclose($out);
        }, 'appropriation-account.csv', $headers);
    }
}
