<?php

namespace App\Filament\Resources\UtilityTransactionResource\Pages;

use App\Filament\Resources\UtilityTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewUtilityTransaction extends ViewRecord
{
    protected static string $resource = UtilityTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
