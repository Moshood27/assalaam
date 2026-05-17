<?php

namespace App\Filament\Resources\QardHasanResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Exports\LoanImportTemplate;
use Maatwebsite\Excel\Facades\Excel;
use App\Filament\Resources\QardHasanResource;
use App\Filament\Pages\QardHasanBranchReport;
use App\Services\CsvImportService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;

class ListQardHasans extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = QardHasanResource::class;

    public function getSubheading(): ?string
    {
        return 'Manage interest-free (Qard Hasan) loan applications and repayments.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
            Actions\Action::make('syncRepayments')
                ->label('Sync Repayments')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Sync and Balance Repayments')
                ->modalDescription('This will sync all "Loan Repayment" contributions with Qard Hasan loans and recalculate loan balances to ensure they match recorded repayments.')
                ->action(function (\App\Services\QardHasanService $service): void {
                    try {
                        $results = $service->fullSyncAndBalance();
                        $msg = sprintf(
                            'Processed: %d | Skipped: %d | Recalculated: %d',
                            $results['processed'],
                            $results['skipped'],
                            $results['recalculated']
                        );
                        Notification::make()
                            ->success()
                            ->title('Sync completed')
                            ->body($msg)
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('Sync failed')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
            Actions\CreateAction::make(),
            Actions\Action::make('branchReport')
                ->label('Branch Outstanding Report')
                ->icon('heroicon-o-document-chart-bar')
                ->color('info')
                ->url(fn () => QardHasanBranchReport::getUrl()),
            Actions\Action::make('importCsv')
                ->label('Import CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('Loans CSV file')
                        ->required()
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->maxSize(10240)
                        ->storeFiles(false),
                    Forms\Components\Placeholder::make('template_info')
                        ->content(new \Illuminate\Support\HtmlString('Download the template for the required format: <a href="/admin/templates/loans-template.xlsx" style="color:blue;text-decoration:underline;">Download Excel Template</a>')),
                ])
                ->modalHeading('Import Loans from CSV')
                ->modalDescription('Upload a CSV with required loan fields. Tip: Use the Excel template below, then Save As CSV before uploading.')
                ->action(function (array $data): void {
                    try {
                        /** @var CsvImportService $svc */
                        $svc = app(CsvImportService::class);
                        $path = $data['file']->getRealPath();
                        $res = $svc->importLoans($path);
                        $s = $res['summary'] ?? [];
                        $msg = sprintf('Processed: %d | Created: %d | Updated: %d | Failed: %d', $s['processed'] ?? 0, $s['created'] ?? 0, $s['updated'] ?? 0, $s['failed'] ?? 0);
                        Notification::make()->success()->title('Loans import completed')->body($msg)->send();
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('Import failed')->body($e->getMessage())->send();
                    }
                }),
            Actions\Action::make('downloadTemplate')
                ->label('Download Template (Excel)')
                ->icon('heroicon-o-document-arrow-down')
                ->action(fn () => Excel::download(new LoanImportTemplate, 'loans_import_template.xlsx')),
        ];
    }

    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\Action::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->extraAttributes(['onclick' => 'window.print()']),
        ];
    }
}
