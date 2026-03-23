<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncomeEntryResource\Pages;
use App\Models\IncomeEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IncomeEntryResource extends Resource
{
    protected static ?string $model = IncomeEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Financial Management';
    protected static ?string $navigationLabel = 'Manual Incomes';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->closeOnDateSelection()
                    ->native(false),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('category')
                    ->maxLength(255)
                    ->placeholder('e.g., Donations, Grants, Others'),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->prefix('₦'),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull()
                    ->rows(3),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')->date('Y-m-d')->sortable(),
                TextColumn::make('title')->searchable()->wrap(),
                TextColumn::make('category')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('amount')->money('ngn', true)->sortable(),
                TextColumn::make('creator.name')->label('Entered By')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->since()->label('Created')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Minimal for now
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIncomeEntries::route('/'),
            'create' => Pages\CreateIncomeEntry::route('/create'),
            'edit' => Pages\EditIncomeEntry::route('/{record}/edit'),
        ];
    }
}
