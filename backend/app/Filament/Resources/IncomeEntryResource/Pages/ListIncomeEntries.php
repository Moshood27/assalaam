<?php

namespace App\Filament\Resources\IncomeEntryResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\IncomeEntryResource;
use Filament\Resources\Pages\ListRecords;

class ListIncomeEntries extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = IncomeEntryResource::class;

    public function getSubheading(): ?string
    {
        return 'Record all sources of revenue and income received by the organization.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
        ];
    }
}
