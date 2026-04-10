<?php

namespace App\Filament\Resources\TakafulPoolEntryResource\Pages;

use App\Filament\Resources\TakafulPoolEntryResource;
use Filament\Resources\Pages\ListRecords;

class ListTakafulPoolEntries extends ListRecords
{
    protected static string $resource = TakafulPoolEntryResource::class;

    public function getSubheading(): ?string
    {
        return 'Manage entries and distributions within the Takaful mutual pool.';
    }
}
