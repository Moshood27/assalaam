<?php

namespace App\Filament\Resources\SavingsGroupResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\SavingsGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSavingsGroups extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = SavingsGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
            Actions\CreateAction::make(),
        ];
    }
}
