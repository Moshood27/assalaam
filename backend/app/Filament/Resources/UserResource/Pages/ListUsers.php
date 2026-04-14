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
                    Forms\Components\Placeholder::make('template_info')
                        ->content(new \Illuminate\Support\HtmlString('Download the template for the required CSV format: <a href="/templates/members-template.csv" style="color:blue;text-decoration:underline;">Download Template</a>')),
                ])
                ->modalHeading('Import Members from CSV')
                ->modalDescription('Upload a CSV with all required member fields. This will update existing members (by email/membership) or create new ones.')
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
                ->label('Download Template')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    $headers = [
                        'name', 'surname', 'other_names', 'email', 'phone', 'gender', 'dob', 'marital_status',
                        'occupation', 'secondary_phone', 'residential_address', 'permanent_address',
                        'nature_of_business', 'business_address', 'has_other_cooperatives', 'other_cooperative_details',
                        'nok_name', 'nok_address', 'nok_phone', 'nok_relationship',
                        'guarantor_name', 'guarantor_address', 'guarantor_phone', 'guarantor_occupation',
                        'religious_society_name', 'imam_name', 'mosque_address', 'imam_phone', 'duration_of_jamma_membership',
                        'spouse_father_name', 'spouse_father_phone', 'spouse_father_address', 'spouse_father_business_address',
                        'admission_form_number', 'admission_date', 'admission_officer_name', 'approval_status',
                        'branch_id', 'membership_number', 'balance', 'is_defaulter'
                    ];
                    $callback = function() use ($headers) {
                        $file = fopen('php://output', 'w');
                        fputcsv($file, $headers);
                        fclose($file);
                    };
                    return response()->stream($callback, 200, [
                        "Content-type"        => "text/csv",
                        "Content-Disposition" => "attachment; filename=members_template.csv",
                    ]);
                }),
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
