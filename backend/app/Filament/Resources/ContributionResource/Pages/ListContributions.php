<?php

namespace App\Filament\Resources\ContributionResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\ContributionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;

class ListContributions extends ListRecords
{
    use HasWipeAction;

    protected static string $resource = ContributionResource::class;

    public function getSubheading(): ?string
    {
        return 'Monitor and track periodic contributions and member subscriptions.';
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getWipeHeaderAction(),
            Actions\CreateAction::make(),
        ];
    }

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
