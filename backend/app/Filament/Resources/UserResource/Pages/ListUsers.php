<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Exports\MemberImportTemplate;
use App\Exports\MemberBalanceImportTemplate;
use Maatwebsite\Excel\Facades\Excel;
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
                    Forms\Components\Placeholder::make('template_info')
                        ->content(new \Illuminate\Support\HtmlString('Download Excel templates: <a href="/admin/templates/members-template.xlsx" style="color:blue;text-decoration:underline;">Full Member Import</a> · <a href="/admin/templates/member-balance-template.xlsx" style="color:blue;text-decoration:underline;">Balance-Only</a><br><small>Tip: Fill in Excel, then <strong>Save As CSV</strong> and upload here.</small>')),
                ])
                ->modalHeading('Import Members from CSV')
                ->modalDescription('Upload a CSV with required member fields. This will update existing members (by email/membership) or create new ones. Tip: Use the Excel templates above, then Save As CSV before uploading.')
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
            Actions\Action::make('downloadTemplate')
                ->label('Download Template (Excel)')
                ->icon('heroicon-o-document-arrow-down')
                ->action(fn () => Excel::download(new MemberImportTemplate, 'members_import_template.xlsx')),
            Actions\Action::make('downloadBalanceTemplate')
                ->label('Download Balance Template (Excel)')
                ->icon('heroicon-o-document-arrow-down')
                ->action(fn () => Excel::download(new MemberBalanceImportTemplate, 'member_balance_import_template.xlsx')),
        ];
    }

    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\Action::make('print_list')
                ->label('Print List')
                ->icon('heroicon-o-printer')
                ->url(fn () => route('admin.print.users'))
                ->openUrlInNewTab(),
        ];
    }
}
