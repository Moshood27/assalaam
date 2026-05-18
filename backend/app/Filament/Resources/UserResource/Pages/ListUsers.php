<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Traits\HasWipeAction;
use App\Exports\MemberImportTemplate;
use App\Exports\MemberBalanceImportTemplate;
use App\Filament\Pages\MemberBalancesBranchReport;
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
    use HasWipeAction;

    protected static string $resource = UserResource::class;

    public function getSubheading(): ?string
    {
        return 'Manage system users, their roles, permissions, and basic profiles.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ActionGroup::make([
                $this->getWipeHeaderAction(),
                Actions\Action::make('clearAllPaystackDVAs')
                    ->label('Clear ALL Paystack Records')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Clear ALL Paystack Data')
                    ->modalDescription('Are you sure you want to clear Paystack records and disable Autosave for ALL users in the database? This action cannot be undone.')
                    ->action(function () {
                        // Clear User fields
                        $userUpdate = ['autosave_enabled' => false];
                        foreach (['paystack_customer_code', 'paystack_authorization_code', 'dva_account_number', 'dva_bank_name', 'dva_account_name'] as $col) {
                            if (\Illuminate\Support\Facades\Schema::hasColumn('users', $col)) {
                                $userUpdate[$col] = null;
                            }
                        }
                        \App\Models\User::query()->update($userUpdate);

                        // Clear Virtual Account fields
                        $count = \App\Models\UserVirtualAccount::query()->update([
                            'paystack_customer_code' => null,
                            'paystack_authorization_code' => null,
                            'dva_account_number' => null,
                            'dva_bank_name' => null,
                            'dva_account_name' => null,
                            'dva_verification_meta' => null,
                        ]);

                        \App\Models\ShariahAuditLog::log(auth()->user(), 'all_paystack_records_cleared', [
                            'count' => $count,
                            'details' => 'All Paystack records and autosave cleared for all users',
                        ]);

                        Notification::make()
                            ->title("Successfully cleared Paystack records and disabled Autosave for all members.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => auth()->user()->hasRole('super_admin')),
            ])
                ->label('Maintenance')
                ->icon('heroicon-m-cog-6-tooth')
                ->color('gray')
                ->button(),

            Actions\ActionGroup::make([
                Actions\Action::make('printByBranch')
                    ->label('Print Users by Branch')
                    ->icon('heroicon-o-printer')
                    ->url(fn () => \App\Filament\Pages\UsersByBranchReport::getUrl()),
                Actions\Action::make('branchReport')
                    ->label('Branch Balances Report')
                    ->icon('heroicon-o-banknotes')
                    ->url(fn () => MemberBalancesBranchReport::getUrl()),
            ])
                ->label('Reports')
                ->icon('heroicon-m-document-text')
                ->color('info')
                ->button(),

            Actions\ActionGroup::make([
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
            ])
                ->label('Import/Export')
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->button(),

            Actions\CreateAction::make(),
        ];
    }

    protected function getTableHeaderActions(): array
    {
        return [];
    }
}
