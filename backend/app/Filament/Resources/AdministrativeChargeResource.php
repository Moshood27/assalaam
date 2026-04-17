<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdministrativeChargeResource\Pages;
use App\Models\AdministrativeCharge;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AdministrativeChargeResource extends Resource
{
    protected static ?string $model = AdministrativeCharge::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Settings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->default(0)
                    ->prefix('₦'),
                Forms\Components\TextInput::make('percentage')
                    ->numeric()
                    ->suffix('%'),
                Forms\Components\TextInput::make('max_amount')
                    ->numeric()
                    ->prefix('₦')
                    ->helperText('Maximum amount for percentage-based charges'),
                Forms\Components\Select::make('frequency')
                    ->options([
                        'one-time' => 'One Time',
                        'monthly' => 'Monthly',
                        'annual' => 'Annual',
                    ])
                    ->default('one-time')
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('NGN')
                    ->sortable(),
                Tables\Columns\TextColumn::make('percentage')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('frequency')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdministrativeCharges::route('/'),
            'create' => Pages\CreateAdministrativeCharge::route('/create'),
            'edit' => Pages\EditAdministrativeCharge::route('/{record}/edit'),
        ];
    }
}
