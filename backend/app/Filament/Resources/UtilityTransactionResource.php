<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UtilityTransactionResource\Pages;
use App\Models\UtilityTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UtilityTransactionResource extends Resource
{
    protected static ?string $model = UtilityTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Utility Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Member')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->options([
                                'airtime' => 'Airtime',
                                'data' => 'Data',
                                'electricity' => 'Electricity',
                                'cable' => 'Cable TV',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('network')
                            ->placeholder('e.g. MTN, Airtel, IKEDC')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('phone_number')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->prefix('₦')
                            ->required(),
                        Forms\Components\TextInput::make('cost_price')
                            ->numeric()
                            ->prefix('₦'),
                        Forms\Components\TextInput::make('profit')
                            ->numeric()
                            ->prefix('₦'),
                        Forms\Components\TextInput::make('reference')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('status')
                            ->placeholder('success, failed, pending')
                            ->maxLength(20),
                        Forms\Components\KeyValue::make('provider_response')
                            ->formatStateUsing(function ($state) {
                                if (auth()->user()->hasRole('super_admin') || !is_array($state)) {
                                    return $state;
                                }
                                $sensitive = ['bvn', 'membership_number', 'account_number', 'password', 'token', 'pin'];
                                foreach ($sensitive as $key) {
                                    if (isset($state[$key]) && is_string($state[$key])) {
                                        $state[$key] = \Illuminate\Support\Str::mask($state[$key], '*', 2, -2);
                                    }
                                }
                                return $state;
                            })
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Time')->dateTime()->sortable(),
                TextColumn::make('user.name')->label('Member')->searchable()->sortable(),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('network')->searchable(),
                TextColumn::make('phone_number')->searchable(),
                TextColumn::make('amount')->money('ngn', true)->sortable(),
                TextColumn::make('profit')->money('ngn', true)->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'success',
                        'warning' => 'pending',
                        'danger' => 'failed',
                    ]),
                TextColumn::make('reference')->searchable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'airtime' => 'Airtime',
                        'data' => 'Data',
                        'electricity' => 'Electricity',
                        'cable' => 'Cable TV',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'success' => 'Success',
                        'pending' => 'Pending',
                        'failed' => 'Failed',
                    ]),
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

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_utility_transaction');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_utility_transaction');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update_utility_transaction');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_utility_transaction');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->when(
                auth()->user()->hasRole('Branch Manager'),
                fn ($query) => $query->whereHas('user', fn ($q) => $q->where('branch_id', auth()->user()->branch_id))
            );
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUtilityTransactions::route('/'),
            'create' => Pages\CreateUtilityTransaction::route('/create'),
            'view' => Pages\ViewUtilityTransaction::route('/{record}'),
            'edit' => Pages\EditUtilityTransaction::route('/{record}/edit'),
        ];
    }
}
