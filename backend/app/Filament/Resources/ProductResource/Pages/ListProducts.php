<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    public function getSubheading(): ?string
    {
        return 'Manage inventory of products available in the cooperative store.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
