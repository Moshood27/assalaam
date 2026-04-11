<?php

namespace App\Filament\Resources\SavingsGroupResource\Pages;

use App\Filament\Resources\SavingsGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSavingsGroup extends EditRecord
{
    protected static string $resource = SavingsGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
