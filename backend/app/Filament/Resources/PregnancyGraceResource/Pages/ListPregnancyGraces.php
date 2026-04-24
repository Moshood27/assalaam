<?php

namespace App\Filament\Resources\PregnancyGraceResource\Pages;

use App\Filament\Resources\PregnancyGraceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPregnancyGraces extends ListRecords
{
    protected static string $resource = PregnancyGraceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
