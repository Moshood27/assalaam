<?php

namespace App\Filament\Resources\AgmCandidateResource\Pages;

use App\Filament\Resources\AgmCandidateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAgmCandidates extends ListRecords
{
    protected static string $resource = AgmCandidateResource::class;

    public function getSubheading(): ?string
    {
        return 'Manage candidates and nominees for the Annual General Meeting elections.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
