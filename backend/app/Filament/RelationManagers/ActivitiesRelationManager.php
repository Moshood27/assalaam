<?php

namespace App\Filament\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Activity Log';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('description')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Logged At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('Admin/User'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Action'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('created_at', 'desc');
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Log Details')
                    ->schema([
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('description'),
                        TextEntry::make('causer.name')->label('Admin/User'),
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

                                foreach ($state as $key => $value) {
                                    if (is_array($value) || is_object($value)) {
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

                                foreach ($state as $key => $value) {
                                    if (is_array($value) || is_object($value)) {
                                        $state[$key] = json_encode($value);
                                    }
                                }

                                return $state;
                            }),
                    ])->columns(2),
            ]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }
}
