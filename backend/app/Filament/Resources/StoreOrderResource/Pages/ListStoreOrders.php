<?php

namespace App\Filament\Resources\StoreOrderResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\StoreOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStoreOrders extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = StoreOrderResource::class;

    public function getSubheading(): ?string
    {
        return 'Process and track customer orders from the cooperative\'s e-commerce store.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
            Actions\CreateAction::make(),
        ];
    }
}
