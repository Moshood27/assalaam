<?php

namespace App\Filament\Resources\AgmSessionResource\RelationManagers;

use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VotesRelationManager extends RelationManager
{
    protected static string $relationship = 'votes';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        // Votes are not editable via admin; keep empty form or disabled fields
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('position')->badge()->sortable(),
                TextColumn::make('candidate.name')->label('Candidate')->searchable()->sortable(),
                TextColumn::make('user.full_name')
                    ->label('Voter')
                    ->searchable(['surname', 'name', 'other_names'])
                    ->sortable(),
                TextColumn::make('created_at')->since()->label('Voted At'),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
