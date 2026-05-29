<?php

namespace App\Filament\Resources\SavingsGoalResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    protected static ?string $recordTitleAttribute = 'partner_name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('partner_name')->required()->maxLength(120),
                Forms\Components\TextInput::make('package')->maxLength(120),
                Forms\Components\TextInput::make('booking_amount')
                    ->label('Booking Amount (â‚¦)')
                    ->numeric()->minValue(0)->step('0.01')->prefix('â‚¦')->required(),
                Forms\Components\TextInput::make('commission_rate')
                    ->label('Commission Rate')
                    ->numeric()->minValue(0)->maxValue(1)->step('0.0001')
                    ->helperText('Fraction e.g. 0.05 = 5%'),
                Forms\Components\TextInput::make('commission_amount')
                    ->label('Commission Amount (â‚¦)')
                    ->numeric()->minValue(0)->step('0.01')->prefix('â‚¦'),
                Forms\Components\TextInput::make('reference')
                    ->disabled()
                    ->dehydrated(false)
                    ->label('Reference')
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options([
                        'booked' => 'Booked',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])->required(),
            ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('partner_name')->searchable()->wrap()->limit(30),
                TextColumn::make('package')->wrap()->limit(30)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('booking_amount')->label('Amount')->money('ngn', true)->sortable(),
                TextColumn::make('commission_rate')->label('Rate')->formatStateUsing(fn ($s) => number_format((float)$s * 100, 2) . '%'),
                TextColumn::make('commission_amount')->label('Commission')->money('ngn', true)->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('reference')->copyable()->label('Ref')->limit(18),
                TextColumn::make('created_at')->since()->label('Created'),
            ])
            ->headerActions([
                // Disable manual creation from relation to avoid inconsistent business flow
                // Actions\CreateAction::make(),
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
