<?php

namespace App\Filament\Resources\LedgerJournalResource\Pages;

use App\Filament\Resources\LedgerJournalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLedgerJournal extends CreateRecord
{
    protected static string $resource = LedgerJournalResource::class;

    protected function beforeCreate(): void
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
