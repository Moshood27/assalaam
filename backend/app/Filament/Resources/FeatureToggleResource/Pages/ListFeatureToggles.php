<?php

namespace App\Filament\Resources\FeatureToggleResource\Pages;

use App\Filament\Resources\FeatureToggleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListFeatureToggles extends ListRecords
{
    protected static string $resource = FeatureToggleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'global' => Tab::make('Global Toggles')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('scope', 'global')),
            'members' => Tab::make('Member Toggles')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('scope', '!=', 'global')),
            'all' => Tab::make('All Toggles'),
        ];
    }
}
