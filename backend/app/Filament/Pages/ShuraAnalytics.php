<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ShuraOverviewWidget;
use App\Filament\Widgets\ShuraParticipationChart;
use Filament\Pages\Page;

class ShuraAnalytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'AGM & Voting';
    protected static ?string $navigationLabel = 'Shura Analytics';
    protected static ?string $title = 'Shura & Governance Analytics';
    protected static ?int $navigationSort = 40;

    protected static string $view = 'filament.pages.shura-analytics';

    protected function getHeaderWidgets(): array
    {
        return [
            ShuraOverviewWidget::class,
            ShuraParticipationChart::class,
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_shura_analytics');
    }
}
