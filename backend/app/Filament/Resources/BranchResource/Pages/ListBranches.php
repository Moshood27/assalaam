<?php

namespace App\Filament\Resources\BranchResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\BranchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables; // For table header actions (beside search bar)

class ListBranches extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = BranchResource::class;

    public function getSubheading(): ?string
    {
        return 'Manage organizational branches, their locations, and basic settings.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
            Actions\CreateAction::make(),
        ];
    }

    // Show a Print button beside the table search bar
    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\Action::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->extraAttributes(['onclick' => 'window.print()']),
        ];
    }
}
