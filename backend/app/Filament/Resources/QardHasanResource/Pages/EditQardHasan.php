<?php

namespace App\Filament\Resources\QardHasanResource\Pages;

use App\Filament\Resources\QardHasanResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditQardHasan extends EditRecord
{
    protected static string $resource = QardHasanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()->hasRole('super_admin')
                    && (float) $this->record->paid_amount <= 0
                    && ! $this->record->repayments()->exists())
                ->before(function (Actions\DeleteAction $action) {
                    if ($this->record->repayments()->exists() || (float) $this->record->paid_amount > 0) {
                        Notification::make()
                            ->danger()
                            ->title('Cannot delete loan')
                            ->body('Repayments have already started for this loan.')
                            ->send();

                        $action->halt();
                    }
                })
                ->requiresConfirmation()
                ->successNotificationTitle('Loan deleted successfully'),
        ];
    }
}
