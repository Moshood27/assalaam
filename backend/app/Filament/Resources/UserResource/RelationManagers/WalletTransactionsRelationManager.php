<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\WalletTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class WalletTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'walletTransactions';

    protected static ?string $recordTitleAttribute = 'reference';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('reference')
                    ->disabled(),
                Forms\Components\TextInput::make('type')
                    ->disabled(),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->prefix('₦')
                    ->disabled(),
                Forms\Components\TextInput::make('source')
                    ->disabled(),
                Forms\Components\KeyValue::make('meta')
                    ->formatStateUsing(function ($state) {
                        if (!is_array($state)) {
                            return [];
                        }

                        $user = auth()->user();
                        $isSuperAdmin = $user && $user->hasRole('super_admin');

                        $sensitive = ['bvn', 'membership_number', 'account_number', 'password'];

                        foreach ($state as $key => $value) {
                            if (!$isSuperAdmin && in_array($key, $sensitive)) {
                                $state[$key] = is_string($value) ? \Illuminate\Support\Str::mask($state[$key], '*', 2, -2) : '*******';
                            } elseif (is_array($value) || is_object($value)) {
                                $state[$key] = json_encode($value);
                            }
                        }

                        return $state;
                    })
                    ->disabled(),
                Forms\Components\DateTimePicker::make('created_at')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('type')->badge()->colors([
                    'success' => ['credit'],
                    'danger' => ['debit'],
                ]),
                TextColumn::make('amount')
                    ->label('Net Amount')
                    ->money('ngn', true)
                    ->sortable(),
                TextColumn::make('meta.gross_amount')
                    ->label('Gross')
                    ->money('ngn', true)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('meta.maintenance_charge')
                    ->label('Fee')
                    ->money('ngn', true)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reference')->searchable(),
                TextColumn::make('source')->label('Source'),
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
