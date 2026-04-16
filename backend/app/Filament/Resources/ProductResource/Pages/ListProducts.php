<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListProducts extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = ProductResource::class;

    public function getSubheading(): ?string
    {
        return 'Manage inventory of products available in the cooperative store.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
            Actions\CreateAction::make(),
        ];
    }
}
