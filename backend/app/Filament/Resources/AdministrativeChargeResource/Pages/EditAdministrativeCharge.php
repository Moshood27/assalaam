<?php

namespace App\Filament\Resources\AdministrativeChargeResource\Pages;

use App\Filament\Resources\AdministrativeChargeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdministrativeCharge extends EditRecord
{
    protected static string $resource = AdministrativeChargeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
