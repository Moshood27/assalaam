<?php

namespace App\Filament\Resources\LedgerJournalResource\Pages;

use App\Filament\Resources\LedgerJournalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLedgerJournal extends EditRecord
{
    protected static string $resource = LedgerJournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $debits = collect($this->data['entries'])->sum('debit');
        $credits = collect($this->data['entries'])->sum('credit');

        if (round((float)$debits, 2) !== round((float)$credits, 2)) {
            \Filament\Notifications\Notification::make()
                ->title('Journal is not balanced')
                ->body("Total debits ({$debits}) must equal total credits ({$credits}).")
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
