<?php

namespace App\Filament\Resources\WhitelistedIpResource\Pages;

use App\Filament\Resources\WhitelistedIpResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWhitelistedIps extends ListRecords
{
    protected static string $resource = WhitelistedIpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
