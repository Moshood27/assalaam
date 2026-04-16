<?php

namespace App\Filament\Resources\ShariaDisputeResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\ShariaDisputeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShariaDisputes extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = ShariaDisputeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
            // Actions\CreateAction::make(), // Disputes should be raised by members via API
        ];
    }
}
