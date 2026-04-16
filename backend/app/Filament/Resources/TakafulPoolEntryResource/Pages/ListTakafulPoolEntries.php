<?php

namespace App\Filament\Resources\TakafulPoolEntryResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\TakafulPoolEntryResource;
use Filament\Resources\Pages\ListRecords;

class ListTakafulPoolEntries extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = TakafulPoolEntryResource::class;

    public function getSubheading(): ?string
    {
        return 'Manage entries and distributions within the Takaful mutual pool.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),,
        ];
    }
}
