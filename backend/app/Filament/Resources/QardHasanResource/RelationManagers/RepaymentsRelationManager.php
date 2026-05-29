<?php

namespace App\Filament\Resources\QardHasanResource\RelationManagers;

use App\Models\QardHasan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class RepaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'repayments';

    protected static ?string $recordTitleAttribute = 'reference';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('reference')
                    ->default(fn () => 'QH-REP-MAN-'.Str::upper(Str::random(10)))
                    ->required()
                    ->unique('qard_hasan_repayments', 'reference', ignoreRecord: true)
                    ->disabled(fn ($record) => $record !== null),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->prefix('â‚¦')
                    ->required()
                    ->disabled(fn ($record) => $record !== null),
                Forms\Components\DateTimePicker::make('paid_at')
                    ->label('Payment Date')
                    ->default(now())
                    ->required()
                    ->disabled(fn ($record) => $record !== null),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Success',
                        'failed' => 'Failed',
                    ])
                    ->default('success')
                    ->required()
                    ->disabled(fn ($record) => $record !== null),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('paid_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('amount')->money('ngn', true)->sortable(),
                TextColumn::make('reference')->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Record Repayment')
                    ->modalHeading('Record Manual Repayment')
                    ->after(function ($record) {
                        if ($record->status === 'success') {
                            $loan = $record->qardHasan;
                            $loan->paid_amount = (float) $loan->paid_amount + (float) $record->amount;
                            $loan->save();

                            Notification::make()
                                ->title('Repayment recorded and loan updated')
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->after(function ($record) {
                        if ($record->status === 'success' && $record->wasChanged('status')) {
                            $loan = $record->qardHasan;
                            $loan->paid_amount = (float) $loan->paid_amount + (float) $record->amount;
                            $loan->save();

                            Notification::make()
                                ->title('Repayment marked as success and loan updated')
                                ->success()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                //
            ]);
    }
}
