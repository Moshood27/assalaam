<?php

namespace App\Filament\Resources\StoreOrderResource\Pages;

use App\Filament\Resources\StoreOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStoreOrder extends ViewRecord
{
    protected static string $resource = StoreOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
