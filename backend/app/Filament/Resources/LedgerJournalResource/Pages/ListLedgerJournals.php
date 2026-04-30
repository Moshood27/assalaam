<?php

namespace App\Filament\Resources\LedgerJournalResource\Pages;

use App\Filament\Resources\LedgerJournalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLedgerJournals extends ListRecords
{
    protected static string $resource = LedgerJournalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
