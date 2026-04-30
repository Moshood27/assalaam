<?php

namespace App\Filament\Resources\LedgerJournalResource\Pages;

use App\Filament\Resources\LedgerJournalResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLedgerJournal extends ViewRecord
{
    protected static string $resource = LedgerJournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
