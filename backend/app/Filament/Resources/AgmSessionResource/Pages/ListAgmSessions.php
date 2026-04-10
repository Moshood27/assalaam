<?php

namespace App\Filament\Resources\AgmSessionResource\Pages;

use App\Filament\Resources\AgmSessionResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListAgmSessions extends ListRecords
{
    protected static string $resource = AgmSessionResource::class;

    public function getSubheading(): ?string
    {
        return 'Oversee and track AGM sessions, agendas, and voting processes.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
