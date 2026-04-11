<?php

namespace App\Filament\Resources\SavingsGroupResource\Pages;

use App\Filament\Resources\SavingsGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSavingsGroups extends ListRecords
{
    protected static string $resource = SavingsGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
