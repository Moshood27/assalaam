<?php

namespace App\Filament\Resources\QardHasanResource\RelationManagers;

use App\Models\QardHasanRepayment;
use Filament\Forms;
use Filament\Forms\Form;
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
                    ->default(fn () => 'MANUAL-PAY-' . strtoupper(Str::random(6)))
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->prefix('₦')
                    ->required()
                    ->default(fn (RelationManager $livewire) => max(0, min(
                        $livewire->getOwnerRecord()->principal_amount - $livewire->getOwnerRecord()->paid_amount,
                        $livewire->getOwnerRecord()->next_installment_amount
                    )))
                    ->rules([
                        fn (RelationManager $livewire): \Closure => function (string $attribute, $value, \Closure $fail) use ($livewire) {
                            $remaining = $livewire->getOwnerRecord()->principal_amount - $livewire->getOwnerRecord()->paid_amount;
                            if ($value > $remaining + 0.01) { // allow small float difference
                                $fail("The amount cannot exceed the remaining balance of ₦" . number_format($remaining, 2));
                            }
                        },
                    ]),
                Forms\Components\DateTimePicker::make('paid_at')
                    ->default(now())
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Success',
                        'failed' => 'Failed',
                    ])
                    ->default('success')
                    ->required(),
                Forms\Components\TextInput::make('ledger_journal_id')
                    ->label('Ledger Journal ID')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('paid_at')->dateTime()->sortable(),
                TextColumn::make('amount')->money('ngn', true)->sortable(),
                TextColumn::make('reference')->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'success' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Success',
                        'failed' => 'Failed',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Record Repayment')
                    ->modalHeading('Record Manual Repayment')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['status'] = 'success';
                        return $data;
                    })
                    ->after(function (QardHasanRepayment $record) {
                        \App\Models\ShariahAuditLog::log(auth()->user(), 'record_qard_hasan_repayment_relation', [
                            'qard_id' => $record->qard_hasan_id,
                            'repayment_id' => $record->id,
                            'amount' => $record->amount,
                            'reference' => $record->reference,
                        ]);
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->hasRole('super_admin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->hasRole('super_admin')),
                ]),
            ]);
    }
}
