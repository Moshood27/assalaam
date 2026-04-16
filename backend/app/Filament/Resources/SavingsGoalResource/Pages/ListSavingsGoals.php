<?php

namespace App\Filament\Resources\SavingsGoalResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\SavingsGoalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSavingsGoals extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = SavingsGoalResource::class;

    public function getSubheading(): ?string
    {
        return 'Define and manage target savings goals available for members.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
            Actions\CreateAction::make(),
        ];
    }
}
