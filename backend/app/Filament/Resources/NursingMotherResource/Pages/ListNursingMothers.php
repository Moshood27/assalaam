<?php

namespace App\Filament\Resources\NursingMotherResource\Pages;

use App\Filament\Resources\NursingMotherResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNursingMothers extends ListRecords
{
    protected static string $resource = NursingMotherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
