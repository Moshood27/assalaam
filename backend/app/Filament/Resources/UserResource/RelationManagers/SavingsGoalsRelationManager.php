<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SavingsGoalsRelationManager extends RelationManager
{
    protected static string $relationship = 'savingsGoals';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->disabled(),
                Forms\Components\TextInput::make('target_amount')
                    ->numeric()
                    ->prefix('â‚¦')
                    ->disabled(),
                Forms\Components\TextInput::make('saved_amount')
                    ->numeric()
                    ->prefix('â‚¦')
                    ->disabled(),
                Forms\Components\DatePicker::make('target_date')
                    ->disabled(),
                Forms\Components\TextInput::make('status')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('created_at')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('target_amount')->money('ngn', true)->sortable(),
                TextColumn::make('saved_amount')->money('ngn', true)->sortable(),
                TextColumn::make('target_date')->date()->sortable(),
                TextColumn::make('status')->badge()->colors([
                    'warning' => ['active'],
                    'success' => ['completed'],
                    'danger' => ['cancelled'],
                ]),
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
