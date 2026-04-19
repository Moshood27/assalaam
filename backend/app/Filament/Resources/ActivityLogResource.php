<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationGroup = 'Security & Logs';

    protected static ?string $navigationLabel = 'Admin Activity Log';

    protected static ?string $modelLabel = 'Activity Log';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Logged At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('causer.full_name')
                    ->label('User/Admin')
                    ->searchable(['surname', 'name', 'other_names']),
                Tables\Columns\TextColumn::make('description')
                    ->label('Action')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Target Model')
                    ->formatStateUsing(fn (?string $state): ?string => $state ? class_basename($state) : null)
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_id')
                    ->label('Target ID'),
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Log Details')
                    ->schema([
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('description'),
                        TextEntry::make('causer.full_name')->label('Admin/User'),
                        TextEntry::make('subject_type')->label('Resource Type'),
                        TextEntry::make('subject_id')->label('Resource ID'),
                    ])->columns(2),

                Section::make('Changes')
                    ->schema([
                        KeyValueEntry::make('properties.old')
                            ->label('Before')
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->state(function ($state) {
                                if ($state instanceof \Illuminate\Support\Collection) {
                                    $state = $state->toArray();
                                }

                                if (!is_array($state)) {
                                    return [];
                                }

                                $user = auth()->user();
                                $isSuperAdmin = $user && $user->hasRole('super_admin');

                                $sensitive = ['bvn', 'membership_number', 'account_number', 'password'];

                                foreach ($state as $key => $value) {
                                    if (!$isSuperAdmin && in_array($key, $sensitive)) {
                                        $state[$key] = is_string($value) ? \Illuminate\Support\Str::mask($value, '*', 2, -2) : '*******';
                                    } elseif (is_array($value) || is_object($value)) {
                                        $state[$key] = json_encode($value);
                                    }
                                }

                                return $state;
                            }),
                        KeyValueEntry::make('properties.attributes')
                            ->label('After')
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->state(function ($state) {
                                if ($state instanceof \Illuminate\Support\Collection) {
                                    $state = $state->toArray();
                                }

                                if (!is_array($state)) {
                                    return [];
                                }

                                $user = auth()->user();
                                $isSuperAdmin = $user && $user->hasRole('super_admin');

                                $sensitive = ['bvn', 'membership_number', 'account_number', 'password'];

                                foreach ($state as $key => $value) {
                                    if (!$isSuperAdmin && in_array($key, $sensitive)) {
                                        $state[$key] = is_string($value) ? \Illuminate\Support\Str::mask($value, '*', 2, -2) : '*******';
                                    } elseif (is_array($value) || is_object($value)) {
                                        $state[$key] = json_encode($value);
                                    }
                                }

                                return $state;
                            }),
                    ])->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_activity');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
