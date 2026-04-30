<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LedgerJournalResource\Pages;
use App\Models\LedgerJournal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LedgerJournalResource extends Resource
{
    protected static ?string $model = LedgerJournal::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Financial Management';
    protected static ?string $navigationLabel = 'Journal Entries';
    protected static ?int $navigationSort = 51;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('date')
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('reference')
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),

                Forms\Components\Section::make('Entries')
                    ->schema([
                        Forms\Components\Repeater::make('entries')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('ledger_account_id')
                                    ->relationship('account', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\TextInput::make('debit')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),
                                Forms\Components\TextInput::make('credit')
                                    ->numeric()
                                    ->default(0)
                                    ->required(),
                                Forms\Components\TextInput::make('description')
                                    ->maxLength(255),
                            ])
                            ->columns(4)
                            ->minItems(2)
                            ->defaultItems(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')->date()->sortable(),
                TextColumn::make('reference')->searchable(),
                TextColumn::make('description')->limit(50)->searchable(),
                TextColumn::make('entries_sum_debit')
                    ->sum('entries', 'debit')
                    ->money('ngn', true)
                    ->label('Total Debit'),
                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn($q) => $q->whereDate('date', '>=', $data['from']))
                            ->when($data['until'], fn($q) => $q->whereDate('date', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListLedgerJournals::route('/'),
            'create' => Pages\CreateLedgerJournal::route('/create'),
            'edit' => Pages\EditLedgerJournal::route('/{record}/edit'),
            'view' => Pages\ViewLedgerJournal::route('/{record}'),
        ];
    }
}
