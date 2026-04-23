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
            Actions\DeleteAction::make(),
        ];
    }
}
