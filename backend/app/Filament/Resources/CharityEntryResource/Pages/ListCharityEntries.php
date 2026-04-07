<?php

namespace App\Filament\Resources\CharityEntryResource\Pages;

use App\Filament\Resources\CharityEntryResource;
use App\Models\ShariahAuditLog as ShariahAudit;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListCharityEntries extends ListRecords
{
    protected static string $resource = CharityEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->after(function ($record) {
                    ShariahAudit::log(auth()->user(), 'charity_entry_created', [
                        'id' => $record->id,
                        'source' => $record->source,
                        'amount' => $record->amount,
                    ]);
                }),
        ];
    }
}
