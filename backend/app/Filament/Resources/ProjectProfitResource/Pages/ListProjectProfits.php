<?php

namespace App\Filament\Resources\ProjectProfitResource\Pages;

use App\Filament\Resources\ProjectProfitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProjectProfits extends ListRecords
{
    protected static string $resource = ProjectProfitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
