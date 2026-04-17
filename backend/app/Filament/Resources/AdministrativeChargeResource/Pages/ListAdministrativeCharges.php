<?php

namespace App\Filament\Resources\AdministrativeChargeResource\Pages;

use App\Filament\Resources\AdministrativeChargeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdministrativeCharges extends ListRecords
{
    protected static string $resource = AdministrativeChargeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
