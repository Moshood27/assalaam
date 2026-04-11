<?php

namespace App\Filament\Resources\ShariaDisputeResource\Pages;

use App\Filament\Resources\ShariaDisputeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShariaDisputes extends ListRecords
{
    protected static string $resource = ShariaDisputeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(), // Disputes should be raised by members via API
        ];
    }
}
