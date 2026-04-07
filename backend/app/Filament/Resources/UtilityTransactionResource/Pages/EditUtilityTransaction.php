<?php

namespace App\Filament\Resources\UtilityTransactionResource\Pages;

use App\Filament\Resources\UtilityTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUtilityTransaction extends EditRecord
{
    protected static string $resource = UtilityTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
