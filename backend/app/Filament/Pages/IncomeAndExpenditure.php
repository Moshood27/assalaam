<?php

namespace App\Filament\Pages;

use App\Services\AccountingReportService;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Filament\Actions\Action;

class IncomeAndExpenditure extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Financial Reports';
    protected static ?string $navigationLabel = 'Income and Expenditure';
    protected static ?int $navigationSort = 11;

    protected static string $view = 'filament.pages.income-and-expenditure';

    public function getHeaderActions(): array
    {
        return [
            Action::make('downloadPdf')
                ->label('Download PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('amber')
                ->url(fn () => route('download-financials', [
                    'year' => (int)date('Y', strtotime($this->to)),
                    'token' => auth()->user()->createToken('FilamentReport', ['*'], now()->addMinutes(5))->plainTextToken
                ])),
        ];
    }

    public function getSubheading(): ?string
    {
        return 'Detailed view of organization\'s income sources and expenditure items.';
    }

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
        $this->report = $svc->buildIncomeAndExpenditure($this->from, $this->to);
    }

    public function exportCsv(): StreamedResponse
    {
        $data = $this->report;
        $headers = [
            'Content-Type' => 'text/csv',
            'Cache-Control' => 'no-store, no-cache',
            'Content-Disposition' => 'attachment; filename="income-expenditure.csv"',
        ];
        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Income & Expenditure', $data['from'] . ' to ' . $data['to']]);
            fputcsv($out, []);
            fputcsv($out, ['Income', 'Amount']);
            foreach ($data['income'] as $line) {
                fputcsv($out, [$line['name'], number_format($line['amount'], 2, '.', '')]);
            }
            fputcsv($out, ['Total Income', number_format($data['total_income'], 2, '.', '')]);
            fputcsv($out, []);
            fputcsv($out, ['Expenses', 'Amount']);
            foreach ($data['expenses'] as $line) {
                fputcsv($out, [$line['name'], number_format($line['amount'], 2, '.', '')]);
            }
            fputcsv($out, ['Total Expenses', number_format($data['total_expense'], 2, '.', '')]);
            fputcsv($out, []);
            fputcsv($out, ['Surplus / (Deficit)', number_format($data['surplus'], 2, '.', '')]);
            fclose($out);
        }, 'income-expenditure.csv', $headers);
    }
}
