<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuthLogResource\Pages;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Database\Eloquent\Builder;

class AuthLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-finger-print';

    protected static ?string $navigationGroup = 'Security & Logs';

    protected static ?string $navigationLabel = 'Authentication Logs';

    protected static ?string $modelLabel = 'Auth Log';

    protected static ?string $pluralModelLabel = 'Authentication Logs';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Event')
                    ->searchable(),
                Tables\Columns\TextColumn::make('causer.full_name')
                    ->label('User')
                    ->placeholder('Guest/System'),
                Tables\Columns\TextColumn::make('properties.ip')
                    ->label('IP Address'),
                Tables\Columns\TextColumn::make('properties.user_agent')
                    ->label('User Agent')
                    ->limit(50)
                    ->tooltip(fn ($state) => $state),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('description')
                    ->options([
                        'User logged in' => 'Login',
                        'User logged out' => 'Logout',
                        'Failed login attempt' => 'Failed Login',
                        'Password reset' => 'Password Reset',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('log_name', 'auth');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuthLogs::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
