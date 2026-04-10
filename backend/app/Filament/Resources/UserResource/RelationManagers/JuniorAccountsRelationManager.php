<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class JuniorAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'juniorAccounts';

    protected static ?string $recordTitleAttribute = 'child_name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('child_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('child_dob')
                    ->required(),
                Forms\Components\TextInput::make('balance')
                    ->numeric()
                    ->prefix('₦')
                    ->default(0)
                    ->disabled() // Admins should probably use a specialized transaction to adjust this if needed
                    ->dehydrated(false),
                Forms\Components\DatePicker::make('locked_until'),
                Forms\Components\TextInput::make('purpose')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('child_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('child_dob')
                    ->date(),
                Tables\Columns\TextColumn::make('balance')
                    ->money('ngn', true),
                Tables\Columns\TextColumn::make('locked_until')
                    ->date()
                    ->color(fn ($state) => $state && $state->isFuture() ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('purpose'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
