<?php

namespace App\Filament\Resources\UtilityTransactionResource\Pages;

use App\Filament\Resources\UtilityTransactionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUtilityTransaction extends CreateRecord
{
    protected static string $resource = UtilityTransactionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
