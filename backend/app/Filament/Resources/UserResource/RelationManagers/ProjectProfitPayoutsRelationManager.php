<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectProfitPayoutsRelationManager extends RelationManager
{
    protected static string $relationship = 'projectProfitPayouts';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('project.title')
                    ->label('Project')
                    ->disabled(),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->prefix('₦')
                    ->disabled(),
                Forms\Components\TextInput::make('status')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('created_at')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('notified_at')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('project.title')->sortable()->searchable(),
                TextColumn::make('amount')->money('ngn', true)->sortable(),
                TextColumn::make('status')->badge()->colors([
                    'warning' => ['pending'],
                    'success' => ['success', 'paid'],
                    'danger' => ['failed', 'rejected'],
                ]),
                TextColumn::make('notified_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
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
            ]);
    }
}
