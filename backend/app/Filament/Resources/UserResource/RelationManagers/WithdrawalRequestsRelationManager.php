<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WithdrawalRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'withdrawalRequests';

    protected static ?string $recordTitleAttribute = 'reference';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('reference')
                    ->disabled(),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->prefix('₦')
                    ->disabled(),
                Forms\Components\TextInput::make('bank_name')
                    ->disabled(),
                Forms\Components\TextInput::make('account_number')
                    ->password()
                    ->revealable(fn () => auth()->user()->hasRole('super_admin'))
                    ->disabled(),
                Forms\Components\TextInput::make('account_name')
                    ->disabled(),
                Forms\Components\TextInput::make('status')
                    ->disabled(),
                Forms\Components\TextInput::make('reason')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('processed_at')
                    ->disabled(),
                Forms\Components\KeyValue::make('meta')
                    ->formatStateUsing(function ($state) {
                        if (auth()->user()->hasRole('super_admin') || !is_array($state)) {
                            return $state;
                        }
                        $sensitive = ['bvn', 'membership_number', 'account_number', 'password'];
                        foreach ($sensitive as $key) {
                            if (isset($state[$key]) && is_string($state[$key])) {
                                $state[$key] = \Illuminate\Support\Str::mask($state[$key], '*', 2, -2);
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
                TextColumn::make('amount')->money('ngn', true)->sortable(),
                TextColumn::make('bank_name')->label('Bank'),
                TextColumn::make('account_number')
                    ->label('Account')
                    ->formatStateUsing(function ($state) {
                        if (auth()->user()->hasRole('super_admin')) {
                            return $state;
                        }
                        return \Illuminate\Support\Str::mask($state, '*', 2, -2);
                    }),
                TextColumn::make('status')->badge()->colors([
                    'warning' => ['pending'],
                    'success' => ['completed'],
                    'danger' => ['rejected', 'failed'],
                ]),
                TextColumn::make('reference')->searchable(),
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
