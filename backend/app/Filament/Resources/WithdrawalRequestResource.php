<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WithdrawalRequestResource\Pages;
use App\Models\WithdrawalRequest;
use App\Models\User;
use App\Models\WalletTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class WithdrawalRequestResource extends Resource
{
    protected static ?string $model = WithdrawalRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Withdrawals';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Read-only; admin processes from the table/actions
                Forms\Components\TextInput::make('user_id')->disabled()->dehydrated(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Requested')->since()->sortable(),
                TextColumn::make('user.name')->label('Member')->searchable(),
                TextColumn::make('reference')->label('Ref')->copyable()->searchable(),
                TextColumn::make('amount')->money('ngn', true)->sortable(),
                TextColumn::make('bank_name')->label('Bank')->toggleable(),
                TextColumn::make('bank_code')->label('Code')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('account_number')->label('Acct #')->toggleable(),
                TextColumn::make('account_name')->label('Acct Name')->toggleable(),
                TextColumn::make('status')->badge()->colors([
                    'warning' => ['pending'],
                    'success' => ['paid'],
                    'danger' => ['declined'],
                ])->sortable(),
                TextColumn::make('processed_at')->label('Processed')->dateTime()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'declined' => 'Declined',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->extraAttributes(['onclick' => 'window.print()']),
            ])
            ->actions([
                Action::make('mark_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (WithdrawalRequest $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (WithdrawalRequest $record) {
                        DB::transaction(function () use ($record) {
                            // Lock user and ensure sufficient balance
                            $user = User::where('id', $record->user_id)->lockForUpdate()->first();
                            if ((float)$user->balance < (float)$record->amount) {
                                throw new \RuntimeException('Insufficient member wallet balance to fulfill withdrawal.');
                            }
                            $user->decrement('balance', (float)$record->amount);

                            // Create wallet transaction (bank withdrawal debit)
                            WalletTransaction::create([
                                'user_id' => $user->id,
                                'type' => 'debit',
                                'amount' => (float)$record->amount,
                                'reference' => $record->reference,
                                'source' => 'bank_withdrawal',
                                'meta' => [
                                    'withdrawal_request_id' => $record->id,
                                    'bank_code' => $record->bank_code,
                                    'bank_name' => $record->bank_name,
                                    'account_number' => $record->account_number,
                                    'account_name' => $record->account_name,
                                ],
                            ]);

                            $record->status = 'paid';
                            $record->processed_at = now();
                            $record->save();
                        });

                        // Best-effort notify member via SMS
                        try {
                            $user = $record->user?->fresh();
                            if ($user) {
                                $sms = app(\App\Services\SmsService::class);
                                $msg = 'Withdrawal paid: ₦'.number_format((float)$record->amount, 2).' to bank '.$record->bank_name.' (Acct '.$record->account_number.'). Ref: '.$record->reference;
                                $sms->send($user->phone ?? null, $msg);
                            }
                        } catch (\Throwable $e) {}

                        Notification::make()
                            ->title('Withdrawal marked as paid')
                            ->success()
                            ->send();
                    }),
                Action::make('decline')
                    ->label('Decline')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (WithdrawalRequest $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason for decline')
                            ->rows(3)
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (WithdrawalRequest $record, array $data) {
                        $reason = trim((string)($data['reason'] ?? ''));
                        $record->status = 'declined';
                        $record->reason = $reason;
                        $record->processed_at = now();
                        $record->save();

                        // Best-effort notify member via SMS
                        try {
                            $user = $record->user?->fresh();
                            if ($user) {
                                $sms = app(\App\Services\SmsService::class);
                                $msg = 'Withdrawal declined: ₦'.number_format((float)$record->amount, 2).'. Reason: '.$reason.' Ref: '.$record->reference;
                                $sms->send($user->phone ?? null, $msg);
                            }
                        } catch (\Throwable $e) {}

                        Notification::make()
                            ->title('Withdrawal declined')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWithdrawalRequests::route('/'),
        ];
    }
}
