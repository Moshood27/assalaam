<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\QardHasan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class QardHasansRelationManager extends RelationManager
{
    protected static string $relationship = 'qardHasans';

    protected static ?string $recordTitleAttribute = 'qard_id_string';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('qard_id_string')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('qard_id_string')->label('Loan ID')->searchable(),
                TextColumn::make('principal_amount')->money('ngn', true)->sortable(),
                TextColumn::make('paid_amount')->money('ngn', true)->sortable(),
                TextColumn::make('status')->badge()->colors([
                    'warning' => ['pending'],
                    'success' => ['active', 'completed'],
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
                Tables\Actions\Action::make('go_to_resource')
                    ->label('Manage')
                    ->url(fn (QardHasan $record): string => \App\Filament\Resources\QardHasanResource::getUrl('edit', ['record' => $record]))
                    ->icon('heroicon-o-arrow-top-right-on-square'),
            ])
            ->bulkActions([
                //
            ]);
    }
}
