<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\StoreOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class StoreOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'storeOrders';

    protected static ?string $recordTitleAttribute = 'reference';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('reference')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('reference')->searchable(),
                TextColumn::make('total_amount')->money('ngn', true)->sortable(),
                TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) {
                    'pending' => 'warning',
                    'paid' => 'success',
                    'murabaha_pending' => 'info',
                    'murabaha_active' => 'success',
                    'processing' => 'info',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                    default => 'gray',
                }),
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
                    ->url(fn (StoreOrder $record): string => \App\Filament\Resources\StoreOrderResource::getUrl('view', ['record' => $record]))
                    ->icon('heroicon-o-arrow-top-right-on-square'),
            ])
            ->bulkActions([
                //
            ]);
    }
}
