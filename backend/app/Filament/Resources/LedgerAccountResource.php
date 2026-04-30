<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LedgerAccountResource\Pages;
use App\Models\LedgerAccount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LedgerAccountResource extends Resource
{
    protected static ?string $model = LedgerAccount::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Financial Management';
    protected static ?string $navigationLabel = 'Ledger Accounts';
    protected static ?int $navigationSort = 50;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->required()
                    ->options([
                        'asset' => 'Asset',
                        'liability' => 'Liability',
                        'equity' => 'Equity',
                        'income' => 'Income',
                        'expense' => 'Expense',
                    ]),
                Forms\Components\Select::make('parent_id')
                    ->label('Parent Account')
                    ->relationship('parent', 'name')
                    ->searchable()
                    ->placeholder('None'),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->default(true),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->sortable()->searchable(),
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('parent.name')->label('Parent')->toggleable(),
                TextColumn::make('balance')
                    ->money('ngn', true)
                    ->label('Balance'),
                TextColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'asset' => 'Asset',
                        'liability' => 'Liability',
                        'equity' => 'Equity',
                        'income' => 'Income',
                        'expense' => 'Expense',
                    ]),
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
            'index' => Pages\ListLedgerAccounts::route('/'),
            'create' => Pages\CreateLedgerAccount::route('/create'),
            'edit' => Pages\EditLedgerAccount::route('/{record}/edit'),
        ];
    }
}
