<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Services\CsvImportService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function getSubheading(): ?string
    {
        return 'Manage system users, their roles, permissions, and basic profiles.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('importCsv')
                ->label('Import CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('Members CSV file')
                        ->required()
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->maxSize(10240)
                        ->storeFiles(false),
                ])
                ->modalHeading('Import Members from CSV')
                ->modalDescription('Upload a CSV with columns: name, email, membership_number, branch_id, balance, is_defaulter. Template: /templates/members-template.csv')
                ->action(function (array $data): void {
                    try {
                        /** @var CsvImportService $svc */
                        $svc = app(CsvImportService::class);
                        $path = $data['file']->getRealPath();
                        $res = $svc->importMembers($path);
                        $s = $res['summary'] ?? [];
                        $msg = sprintf('Processed: %d | Created: %d | Updated: %d | Failed: %d', $s['processed'] ?? 0, $s['created'] ?? 0, $s['updated'] ?? 0, $s['failed'] ?? 0);
                        Notification::make()->success()->title('Members import completed')->body($msg)->send();
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
