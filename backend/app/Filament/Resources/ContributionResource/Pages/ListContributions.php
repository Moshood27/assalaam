<?php

namespace App\Filament\Resources\ContributionResource\Pages;

use App\Filament\Traits\HasWipeAction;

use App\Filament\Resources\ContributionResource;
use App\Filament\Pages\ContributionBranchReport;
use App\Filament\Pages\SchemeBranchReport;
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
            Actions\Action::make('branchSchemeReport')
                ->label('Branch Schemes Report')
                ->icon('heroicon-o-document-chart-bar')
                ->color('info')
                ->url(fn (): string => SchemeBranchReport::getUrl()),
            Actions\Action::make('branchReport')
                ->label('Branch Contribution Report')
                ->icon('heroicon-o-document-chart-bar')
                ->color('success')
                ->url(fn () => ContributionBranchReport::getUrl()),
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
