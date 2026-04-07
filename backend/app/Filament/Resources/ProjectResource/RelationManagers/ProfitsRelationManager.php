<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProfitsRelationManager extends RelationManager
{
    protected static string $relationship = 'profits';

    protected static ?string $recordTitleAttribute = 'id';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->since()->label('Time')->sortable(),
                TextColumn::make('gross_profit')->money('ngn', true)->sortable(),
                TextColumn::make('management_fee_amount')->label('Mgmt Fee')->money('ngn', true),
                TextColumn::make('net_distributable')->label('Net Distributable')->money('ngn', true),
                TextColumn::make('note')->searchable()->limit(30),
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
