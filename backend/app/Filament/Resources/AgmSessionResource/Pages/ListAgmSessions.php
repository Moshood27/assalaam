<?php

namespace App\Filament\Resources\AgmSessionResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\AgmSessionResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListAgmSessions extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = AgmSessionResource::class;

    public function getSubheading(): ?string
    {
        return 'Oversee and track AGM sessions, agendas, and voting processes.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
            Actions\CreateAction::make(),
        ];
    }
}
