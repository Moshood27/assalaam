<?php

namespace App\Filament\Resources\AgmSessionResource\Pages;

use App\Filament\Resources\AgmSessionResource;
use Filament\Resources\Pages\EditRecord;

class EditAgmSession extends EditRecord
{
    protected static string $resource = AgmSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
