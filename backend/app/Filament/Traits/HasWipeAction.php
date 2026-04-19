<?php

namespace App\Filament\Traits;

use Filament\Actions;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

trait HasWipeAction
{
    /**
     * @return Actions\Action
     */
    protected function getWipeHeaderAction(): Actions\Action
    {
        return Actions\Action::make('wipe')
            ->label('Wipe Module')
            ->color('danger')
            ->icon('heroicon-o-trash')
            ->requiresConfirmation()
            ->modalHeading('Wipe All Data')
            ->modalDescription('Are you absolutely sure you want to delete ALL records in this module? This action is irreversible.')
            ->action(function () {
                $resource = static::getResource();
                $model = $resource::getModel();

                try {
                    // Try to delete all records
                    $query = $model::query();

                    // If it's the User model, don't delete the current user
                    if ($model === \App\Models\User::class) {
                        $query->where('id', '!=', auth()->id());
                    }

                    // Use chunking to avoid memory issues and to trigger model events if any are defined
                    // Note: chunkById is better for deletions to avoid skipping records
                    $query->chunkById(100, function ($records) use ($model) {
                        // Use withoutEvents to bypass observers like the one in QardHasan
                        // that prevents deletion if history exists.
                        // This is a "Wipe" action, so we intend to delete everything.
                        $model::withoutEvents(function () use ($records) {
                            foreach ($records as $record) {
                                $record->delete();
                            }
                        });
                    });

                    Notification::make()
                        ->title('Module wiped successfully')
                        ->success()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Wipe failed')
                        ->body('This may be due to foreign key constraints: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            })
            ->visible(fn () => auth()->user()->hasRole('super_admin'));
    }
}
