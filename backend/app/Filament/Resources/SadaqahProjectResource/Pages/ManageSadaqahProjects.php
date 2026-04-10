<?php

namespace App\Filament\Resources\SadaqahProjectResource\Pages;

use App\Filament\Resources\SadaqahProjectResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSadaqahProjects extends ManageRecords
{
    protected static string $resource = SadaqahProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
