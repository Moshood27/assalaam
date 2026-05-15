<?php

namespace App\Filament\Resources\FeatureToggleResource\Pages;

use App\Filament\Resources\FeatureToggleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFeatureToggles extends ListRecords
{
    protected static string $resource = FeatureToggleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
