<?php

namespace App\Filament\Resources\CharityEntryResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\CharityEntryResource;
use App\Models\ShariahAuditLog as ShariahAudit;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListCharityEntries extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = CharityEntryResource::class;

    public function getSubheading(): ?string
    {
        return 'Record and manage charitable donations and Sadaqah distributions.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
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
