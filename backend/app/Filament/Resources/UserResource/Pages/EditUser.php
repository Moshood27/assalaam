<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ActionGroup::make([
                Actions\Action::make('chargeFine')
                    ->label('Charge Manual Fine')
                    ->icon('heroicon-o-plus-circle')
                    ->color('warning')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('amount')
                            ->label('Fine Amount')
                            ->numeric()
                            ->prefix('₦')
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('note')
                            ->label('Reason')
                            ->required()
                            ->placeholder('e.g. Conduct unbecoming'),
                    ])
                    ->action(function (array $data) {
                        $record = $this->getRecord();
                        $record->increment('outstanding_fines', (float) $data['amount']);

                        \App\Models\ShariahAuditLog::log(auth()->user(), 'manual_fine_charged', [
                            'user_id' => $record->id,
                            'amount' => (float) $data['amount'],
                            'reason' => $data['note'],
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Fine charged successfully')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                Actions\Action::make('payFines')
                    ->label('Record Fine Payment')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn () => (float)$this->getRecord()->outstanding_fines > 0)
                    ->form([
                        \Filament\Forms\Components\TextInput::make('amount')
                            ->label('Amount Paid')
                            ->numeric()
                            ->prefix('₦')
                            ->default(fn () => (float)$this->getRecord()->outstanding_fines)
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('note')
                            ->label('Note')
                            ->placeholder('e.g. Paid in cash at the office'),
                    ])
                    ->action(function (array $data) {
                        $record = $this->getRecord();
                        \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
                            $amount = (float) $data['amount'];

                            \App\Models\Contribution::create([
                                'user_id' => $record->id,
                                'amount' => $amount,
                                'category' => 'fine',
                                'status' => 'success',
                                'reference' => 'MANUAL_FINE_' . \Illuminate\Support\Str::random(8),
                            ]);

                            \App\Models\ShariahAuditLog::log(auth()->user(), 'manual_fine_payment_recorded', [
                                'user_id' => $record->id,
                                'amount' => $amount,
                                'note' => $data['note'] ?? '',
                            ]);
                        });

                        \Filament\Notifications\Notification::make()
                            ->title('Fine payment recorded')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                Actions\Action::make('waiveFines')
                    ->label('Waive All Fines')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn () => (float)$this->getRecord()->outstanding_fines > 0)
                    ->action(function () {
                        $record = $this->getRecord();
                        app(\App\Services\AttendanceService::class)->waiveAllFines($record);

                        \App\Models\ShariahAuditLog::log(auth()->user(), 'manual_fine_waiver', [
                            'user_id' => $record->id,
                            'waived_amount' => (float) $record->getOriginal('outstanding_fines'),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Fines waived successfully')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
                ->label('Fines')
                ->icon('heroicon-m-banknotes')
                ->color('warning')
                ->button(),

            Actions\ActionGroup::make([
                Actions\Action::make('clearPaystackDVA')
                    ->label('Clear Paystack Record')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Clear Paystack Record')
                    ->modalDescription('Are you sure you want to clear the Paystack record and disable Autosave for this user? They will need to re-generate it if needed.')
                    ->visible(fn () => $this->getRecord()->virtualAccount?->paystack_customer_code !== null || $this->getRecord()->virtualAccount?->dva_account_number !== null || $this->getRecord()->autosave_enabled)
                    ->action(function () {
                        $record = $this->getRecord();

                        // Clear User fields
                        $userUpdate = ['autosave_enabled' => false];
                        foreach (['paystack_customer_code', 'paystack_authorization_code', 'dva_account_number', 'dva_bank_name', 'dva_account_name'] as $col) {
                            if (\Illuminate\Support\Facades\Schema::hasColumn('users', $col)) {
                                $userUpdate[$col] = null;
                            }
                        }
                        $record->update($userUpdate);

                        // Clear Virtual Account fields
                        if ($record->virtualAccount) {
                            $record->virtualAccount->update([
                                'paystack_customer_code' => null,
                                'paystack_authorization_code' => null,
                                'dva_account_number' => null,
                                'dva_bank_name' => null,
                                'dva_account_name' => null,
                                'dva_verification_meta' => null,
                            ]);
                        }

                        \App\Models\ShariahAuditLog::log(auth()->user(), 'paystack_dva_cleared', [
                            'user_id' => $record->id,
                            'details' => 'Paystack record and autosave cleared',
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Paystack record cleared')
                            ->success()
                            ->send();
                    }),
            ])
                ->label('Account Settings')
                ->icon('heroicon-m-user-circle')
                ->color('gray')
                ->button(),

            Actions\DeleteAction::make(),
        ];
    }
}
