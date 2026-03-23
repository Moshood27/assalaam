<?php

namespace App\Filament\Resources\SchemeResource\Pages;

use App\Filament\Resources\SchemeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditScheme extends EditRecord
{
    protected static string $resource = SchemeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
