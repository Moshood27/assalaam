<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\ProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProjects extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = ProjectResource::class;

    public function getSubheading(): ?string
    {
        return 'Oversee investment projects, their timelines, and current status.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
            Actions\CreateAction::make(),
        ];
    }
}
