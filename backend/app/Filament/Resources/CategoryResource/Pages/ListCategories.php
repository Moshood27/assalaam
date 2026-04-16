<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCategories extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = CategoryResource::class;

    public function getSubheading(): ?string
    {
        return 'Classify products, expenses, and items into various categories.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
            Actions\CreateAction::make(),
        ];
    }
}
