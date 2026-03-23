<?php

namespace App\Filament\Resources\AgmCandidateResource\Pages;

use App\Filament\Resources\AgmCandidateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAgmCandidates extends ListRecords
{
    protected static string $resource = AgmCandidateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
