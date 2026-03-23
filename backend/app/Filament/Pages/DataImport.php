<?php

namespace App\Filament\Pages;

use App\Services\CsvImportService;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Livewire\WithFileUploads;

class DataImport extends Page
{
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static ?string $navigationGroup = 'Admin Tools';
    protected static ?string $navigationLabel = 'Bulk Import';
    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.pages.data-import';

    public $membersFile;
    public $schemesFile;
    public $loansFile;

    public array $membersResult = [];
    public array $schemesResult = [];
    public array $loansResult = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && (bool) $user->is_admin;
    }

    protected function rules(): array
    {
        return [
            'membersFile' => ['nullable', 'file', 'mimetypes:text/plain,text/csv', 'max:10240'],
            'schemesFile' => ['nullable', 'file', 'mimetypes:text/plain,text/csv', 'max:10240'],
            'loansFile' => ['nullable', 'file', 'mimetypes:text/plain,text/csv', 'max:10240'],
        ];
    }

    public function importMembers(): void
    {
        $this->validateOnly('membersFile');
        if (!$this->membersFile) return;

        /** @var CsvImportService $svc */
        $svc = app(CsvImportService::class);
        $path = $this->membersFile->getRealPath();
        $res = $svc->importMembers($path);
        $this->membersResult = $res;
        Notification::make()->success()->title('Members import completed.')->send();
    }

    public function importSchemes(): void
    {
        $this->validateOnly('schemesFile');
        if (!$this->schemesFile) return;

        /** @var CsvImportService $svc */
        $svc = app(CsvImportService::class);
        $path = $this->schemesFile->getRealPath();
        $res = $svc->importSchemes($path);
        $this->schemesResult = $res;
        Notification::make()->success()->title('Schemes import completed.')->send();
    }

    public function importLoans(): void
    {
        $this->validateOnly('loansFile');
        if (!$this->loansFile) return;

        /** @var CsvImportService $svc */
        $svc = app(CsvImportService::class);
        $path = $this->loansFile->getRealPath();
        $res = $svc->importLoans($path);
        $this->loansResult = $res;
        Notification::make()->success()->title('Loans import completed.')->send();
    }
}
