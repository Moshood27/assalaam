<?php

namespace App\Filament\Resources\SchemeResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Pages\SchemeBranchReport;
use App\Filament\Resources\SchemeResource;
use App\Services\CsvImportService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;

class ListSchemes extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = SchemeResource::class;

    public function getSubheading(): ?string
    {
        return 'Manage various cooperative schemes, membership types, and their rules.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
            Actions\Action::make('branchSchemeReport')
                ->label('Branch Schemes Report')
                ->icon('heroicon-o-document-chart-bar')
                ->color('info')
                ->url(fn (): string => SchemeBranchReport::getUrl()),
            Actions\CreateAction::make(),
            Actions\Action::make('importCsv')
                ->label('Import CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('Schemes CSV file')
                        ->required()
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->maxSize(10240)
                        ->storeFiles(false),
                ])
                ->modalHeading('Import Schemes from CSV')
                ->modalDescription('Upload a CSV with columns: name, min_amount, active.')
                ->action(function (array $data): void {
                    try {
                        /** @var CsvImportService $svc */
                        $svc = app(CsvImportService::class);
                        $path = $data['file']->getRealPath();
                        $res = $svc->importSchemes($path);
                        $s = $res['summary'] ?? [];
                        $msg = sprintf('Processed: %d | Created: %d | Updated: %d | Failed: %d', $s['processed'] ?? 0, $s['created'] ?? 0, $s['updated'] ?? 0, $s['failed'] ?? 0);
                        Notification::make()->success()->title('Schemes import completed')->body($msg)->send();
                    } catch (\Throwable $e) {
                        Notification::make()->danger()->title('Import failed')->body($e->getMessage())->send();
                    }
                }),
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
