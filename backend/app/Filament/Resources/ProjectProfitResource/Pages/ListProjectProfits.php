<?php

namespace App\Filament\Resources\ProjectProfitResource\Pages;

use App\Filament\Resources\ProjectProfitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProjectProfits extends ListRecords
{
    protected static string $resource = ProjectProfitResource::class;

    public function getSubheading(): ?string
    {
        return 'Manage the distribution and tracking of profits from investment projects.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
