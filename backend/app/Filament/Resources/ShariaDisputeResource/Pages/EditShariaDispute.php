<?php

namespace App\Filament\Resources\ShariaDisputeResource\Pages;

use App\Filament\Resources\ShariaDisputeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShariaDispute extends EditRecord
{
    protected static string $resource = ShariaDisputeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
