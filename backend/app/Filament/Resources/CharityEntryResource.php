<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CharityEntryResource\Pages;
use App\Models\CharityEntry;
use App\Models\ShariahAuditLog as ShariahAudit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CharityEntryResource extends Resource
{
    protected static ?string $model = CharityEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Financials';

    protected static ?string $label = 'Charity Ledger';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->placeholder('General / Anonymous if null'),
                Forms\Components\TextInput::make('source')
                    ->required()
                    ->placeholder('e.g. Loan Penalties, Non-Shariah Profit, Direct Donation'),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->prefix('₦'),
                Forms\Components\Textarea::make('note')
                    ->maxLength(255)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('user.name')->label('Member')->searchable(),
                TextColumn::make('source')->searchable(),
                TextColumn::make('amount')->money('ngn', true)->sortable(),
                TextColumn::make('note')->limit(50),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(function ($record) {
                        ShariahAudit::log(auth()->user(), 'charity_entry_updated', [
                            'id' => $record->id,
                            'source' => $record->source,
                            'amount' => $record->amount,
                        ]);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->before(function ($record) {
                        ShariahAudit::log(auth()->user(), 'charity_entry_deleted', [
                            'id' => $record->id,
                            'source' => $record->source,
                            'amount' => $record->amount,
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_charity_entry');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_charity_entry');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update_charity_entry');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_charity_entry');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->when(
                auth()->user()->hasRole('Branch Manager'),
                fn ($query) => $query->where(function ($q) {
                    $q->whereHas('user', fn ($uq) => $uq->where('branch_id', auth()->user()->branch_id))
                      ->orWhereNull('user_id');
                })
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCharityEntries::route('/'),
        ];
    }
}
