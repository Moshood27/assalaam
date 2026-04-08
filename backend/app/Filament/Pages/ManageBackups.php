<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Backup\BackupDestination\BackupDestinationFactory;
use Spatie\Backup\Config\Config;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManageBackups extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationGroup = 'System';
    protected static ?string $navigationLabel = 'Backups';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.manage-backups';

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('runFullBackup')
                ->label('Run Full Backup')
                ->color('primary')
                ->action(fn () => $this->createBackup()),
            \Filament\Actions\Action::make('runDbOnlyBackup')
                ->label('Run Database Only Backup')
                ->color('info')
                ->action(fn () => $this->createBackup('only-db')),
            \Filament\Actions\Action::make('cleanBackups')
                ->label('Clean Old Backups')
                ->color('warning')
                ->requiresConfirmation()
                ->action(fn () => $this->cleanBackups()),
        ];
    }

    public static function canAccess(): bool
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        return $user && ($user->is_admin === true || $user->hasRole('super_admin'));
    }

    public function table(Table $table): Table
    {
        return $table
            ->records($this->getBackupRecords())
            ->columns([
                TextColumn::make('disk')
                    ->label('Disk')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('filename')
                    ->label('Filename')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('size')
                    ->label('Size'),
                TextColumn::make('date')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->action(fn ($record) => $this->downloadBackup($record->disk, $record->path)),
                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($record) => $this->deleteBackup($record->disk, $record->path)),
            ])
            ->emptyStateHeading('No backups found')
            ->emptyStateDescription('Try running a backup or check your configuration.')
            ->emptyStateIcon('heroicon-o-circle-stack');
    }

    protected function getBackupRecords(): Collection
    {
        return BackupDestinationFactory::createFromArray(app(Config::class))
            ->flatMap(function (BackupDestination $backupDestination) {
                return $backupDestination->backups()
                    ->map(function ($backup) use ($backupDestination) {
                        return (object) [
                            'id' => $backupDestination->diskName() . ':' . $backup->path(),
                            'disk' => $backupDestination->diskName(),
                            'path' => $backup->path(),
                            'filename' => basename($backup->path()),
                            'date' => $backup->date(),
                            'size' => $this->formatBytes($backup->sizeInBytes()),
                        ];
                    });
            })
            ->sortByDesc('date');
    }

    public function createBackup(string $option = ''): void
    {
        try {
            if ($option === 'only-db') {
                Artisan::queue('backup:run', ['--only-db' => true]);
            } else {
                Artisan::queue('backup:run');
            }

            Notification::make()
                ->success()
                ->title('Backup job has been dispatched to the queue.')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Failed to run backup.')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function cleanBackups(): void
    {
        try {
            Artisan::queue('backup:clean');
            Notification::make()
                ->success()
                ->title('Cleanup job has been dispatched to the queue.')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Failed to run cleanup.')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function downloadBackup(string $disk, string $path): StreamedResponse|Notification
    {
        if (!Storage::disk($disk)->exists($path)) {
            return Notification::make()
                ->danger()
                ->title('File not found.')
                ->send();
        }

        return Storage::disk($disk)->download($path);
    }

    public function deleteBackup(string $disk, string $path): void
    {
        try {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
                Notification::make()
                    ->success()
                    ->title('Backup deleted successfully.')
                    ->send();
            } else {
                Notification::make()
                    ->warning()
                    ->title('Backup file not found.')
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Failed to delete backup.')
                ->body($e->getMessage())
                ->send();
        }
    }

    protected function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
