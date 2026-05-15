<?php

namespace App\Filament\Resources\FeatureToggleResource\Pages;

use App\Filament\Resources\FeatureToggleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFeatureToggle extends EditRecord
{
    protected static string $resource = FeatureToggleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
