<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SuspiciousActionResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Database\Eloquent\Builder;

class SuspiciousActionResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationGroup = 'Security & Logs';

    protected static ?string $navigationLabel = 'Suspicious Actions';

    protected static ?string $modelLabel = 'Suspicious Action';

    protected static ?string $pluralModelLabel = 'Suspicious Actions';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Incident')
                    ->searchable()
                    ->color('danger')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('causer.full_name')
                    ->label('Involved User')
                    ->placeholder('Guest/System'),
                Tables\Columns\TextColumn::make('properties.ip')
                    ->label('IP Address'),
                Tables\Columns\TextColumn::make('properties')
                    ->label('Details')
                    ->formatStateUsing(function ($state) {
                        if ($state instanceof \Illuminate\Support\Collection) {
                            $state = $state->toArray();
                        }
                        return collect($state)->except(['ip', 'user_agent'])->map(fn($v, $k) => "$k: " . (is_array($v) ? json_encode($v) : $v))->implode(', ');
                    })
                    ->wrap(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('log_name', 'suspicious');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuspiciousActions::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
