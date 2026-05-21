<?php

namespace App\Filament\Widgets;

use Spatie\Activitylog\Models\Activity;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class SecurityAlertsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return 'Recent Security Alerts & Auth Events';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Activity::query()
                    ->whereIn('log_name', ['security', 'auth'])
                    ->where(function($query) {
                        $query->where('log_name', 'security')
                              ->orWhere('description', 'like', '%Failed%')
                              ->orWhere('description', 'like', '%locked out%');
                    })
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Time')
                    ->since()
                    ->sortable(),
                TextColumn::make('log_name')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'security' => 'danger',
                        'auth' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('description')
                    ->label('Event'),
                TextColumn::make('properties.email')
                    ->label('Target User/Email')
                    ->placeholder('N/A'),
                TextColumn::make('properties.ip')
                    ->label('IP Address')
                    ->placeholder('Unknown'),
            ])
            ->paginated(false);
    }

    public static function canView(): bool
    {
        return auth()->user() && auth()->user()->hasRole('super_admin');
    }
}
