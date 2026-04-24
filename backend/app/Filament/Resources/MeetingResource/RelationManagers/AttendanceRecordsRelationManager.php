<?php

namespace App\Filament\Resources\MeetingResource\RelationManagers;

use App\Models\AttendanceRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class AttendanceRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'attendanceRecords';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Member')
                    ->relationship('user', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable(['surname', 'name', 'other_names'])
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'present' => 'Present',
                        'absent' => 'Absent',
                        'fine_paid' => 'Fine Paid',
                        'fine_pending' => 'Fine Pending',
                        'pending_excuse' => 'Pending Excuse',
                        'excused' => 'Excused',
                    ])
                    ->required(),
                Forms\Components\DateTimePicker::make('attended_at'),
                Forms\Components\TextInput::make('device_uuid')
                    ->label('Device ID')
                    ->readOnly(),
                Forms\Components\DateTimePicker::make('fine_paid_at'),
                Forms\Components\Toggle::make('lateness_fine_paid')
                    ->label('Lateness Fine Paid'),
                Forms\Components\TextInput::make('lateness_fine_amount')
                    ->numeric()
                    ->prefix('₦'),
                Forms\Components\Section::make('Excuse Details')
                    ->schema([
                        Forms\Components\Select::make('excuse_type')
                            ->options([
                                'medical' => 'Medical',
                                'travel' => 'Travel',
                                'official' => 'Official',
                                'other' => 'Other',
                            ]),
                        Forms\Components\Textarea::make('excuse_reason')
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('excuse_proof_path')
                            ->label('Excuse Proof')
                            ->disk('public')
                            ->directory('excuse_proofs')
                            ->downloadable()
                            ->openable(),
                        Forms\Components\DateTimePicker::make('excused_at')
                            ->readOnly(),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('user.surname')
                    ->label('Member Name')
                    ->formatStateUsing(fn ($record) => $record->user?->full_name ?? '-')
                    ->searchable(['surname', 'name', 'other_names'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.membership_number')
                    ->label('ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'present' => 'success',
                        'absent' => 'danger',
                        'fine_paid' => 'warning',
                        'fine_pending' => 'danger',
                        'pending_excuse' => 'warning',
                        'excused' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('excuse_type')
                    ->label('Type')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('excuse_reason')
                    ->label('Excuse')
                    ->limit(20)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('attended_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('device_uuid')
                    ->label('Device ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('lateness_fine_paid')
                    ->label('Late Fine')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lateness_fine_amount')
                    ->label('Late Amount')
                    ->money('NGN')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'present' => 'Present',
                        'absent' => 'Absent',
                        'fine_paid' => 'Fine Paid',
                        'fine_pending' => 'Fine Pending',
                        'pending_excuse' => 'Pending Excuse',
                        'excused' => 'Excused',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(function ($record) {
                        if ($record->status === 'present' && $record->attended_at) {
                            $meeting = $this->getOwnerRecord();
                            $attendanceService = app(\App\Services\AttendanceService::class);
                            if ($attendanceService->isLate($meeting, $record->attended_at)) {
                                $attendanceService->chargeLatenessFine($record->user, $meeting);
                            }
                        }
                    }),
                Tables\Actions\Action::make('syncMembers')
                    ->label('Sync Branch Members')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function () {
                        $meeting = $this->getOwnerRecord();
                        $query = \App\Models\User::where('is_admin', false);
                        if ($meeting->branches()->exists()) {
                            $query->whereIn('branch_id', $meeting->branches()->pluck('branches.id'));
                        }
                        $userIds = $query->pluck('id');

                        $count = 0;
                        foreach ($userIds as $userId) {
                            $created = \App\Models\AttendanceRecord::firstOrCreate([
                                'meeting_id' => $meeting->id,
                                'user_id' => $userId,
                            ], [
                                'status' => 'absent',
                            ]);
                            if ($created->wasRecentlyCreated) $count++;
                        }

                        \Filament\Notifications\Notification::make()
                            ->title("Synced {$count} new members as absent.")
                            ->success()
                            ->send();
                    })
            ])
            ->actions([
                Tables\Actions\Action::make('approveExcuse')
                    ->label('Approve Excuse')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending_excuse')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'excused',
                            'excused_at' => now(),
                        ]);

                        $record->user->notifyMember(
                            "🙏 Excuse Approved",
                            "Your excuse for meeting '{$record->meeting->name}' has been approved. You will not be charged any fine.",
                            [
                                'type' => 'excuse_approved',
                                'meeting_id' => (string) $record->meeting_id,
                            ]
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Excuse approved for ' . $record->user->full_name)
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('rejectExcuse')
                    ->label('Reject Excuse')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'pending_excuse')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'absent',
                        ]);

                        $record->user->notifyMember(
                            "❌ Excuse Rejected",
                            "Your excuse for meeting '{$record->meeting->name}' was not approved. You may be charged an absence fine if the meeting is audited.",
                            [
                                'type' => 'excuse_rejected',
                                'meeting_id' => (string) $record->meeting_id,
                            ]
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Excuse rejected for ' . $record->user->full_name)
                            ->danger()
                            ->send();

                        // Note: Fines will be charged next time audit runs,
                        // or admin can manually trigger audit.
                    }),
                Tables\Actions\EditAction::make()
                    ->after(function (AttendanceRecord $record, array $data, AttendanceRecord $oldRecord) {
                        // Handle Absence Fine status change
                        $oldStatus = $oldRecord->status;
                        $newStatus = $record->status;
                        $fineAmount = (float)($record->meeting->fine_amount ?? config('cooperative.attendance.default_fine', 500));

                        if ($oldStatus === 'fine_pending' && in_array($newStatus, ['fine_paid', 'present', 'excused'])) {
                            $record->user->decrement('outstanding_fines', $fineAmount);
                        } elseif (in_array($oldStatus, ['absent', null]) && $newStatus === 'fine_pending') {
                            $record->user->increment('outstanding_fines', $fineAmount);
                        }

                        // If manually excused, also handle lateness fine waiving if it was pending
                        if ($newStatus === 'excused' && $oldStatus !== 'excused') {
                            if ($record->lateness_fine_paid === false && (float)$record->lateness_fine_amount > 0) {
                                $record->user->decrement('outstanding_fines', (float)$record->lateness_fine_amount);
                                $record->update(['lateness_fine_paid' => true]);
                            }
                        }

                        // Handle Lateness Fine change
                        if ($oldRecord->lateness_fine_paid === false && $record->lateness_fine_paid === true) {
                            $record->user->decrement('outstanding_fines', (float) $record->lateness_fine_amount);
                        } elseif ($oldRecord->lateness_fine_paid === true && $record->lateness_fine_paid === false) {
                            $record->user->increment('outstanding_fines', (float) $record->lateness_fine_amount);
                        }

                        if ($record->status === 'present' && $record->attended_at) {
                            $meeting = $this->getOwnerRecord();
                            $attendanceService = app(\App\Services\AttendanceService::class);
                            if ($attendanceService->isLate($meeting, $record->attended_at)) {
                                $attendanceService->chargeLatenessFine($record->user, $meeting);
                            }
                        }
                    }),
                Tables\Actions\Action::make('markPresent')
                    ->label('Present')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->hidden(fn ($record) => $record->status === 'present')
                    ->action(function ($record) {
                        $meeting = $this->getOwnerRecord();
                        $attendanceService = app(\App\Services\AttendanceService::class);

                        $record->update([
                            'status' => 'present',
                            'attended_at' => now(),
                        ]);

                        if ($attendanceService->isLate($meeting, $record->attended_at)) {
                            $attendanceService->chargeLatenessFine($record->user, $meeting);
                            \Filament\Notifications\Notification::make()
                                ->title('Lateness fine charged for ' . $record->user->full_name)
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('markPresentBulk')
                        ->label('Mark as Present')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $meeting = $this->getOwnerRecord();
                            $attendanceService = app(\App\Services\AttendanceService::class);

                            $records->each(function ($record) use ($meeting, $attendanceService) {
                                $record->update([
                                    'status' => 'present',
                                    'attended_at' => now(),
                                ]);

                                if ($attendanceService->isLate($meeting, $record->attended_at)) {
                                    $attendanceService->chargeLatenessFine($record->user, $meeting);
                                }
                            });

                            \Filament\Notifications\Notification::make()
                                ->title('Selected members marked as present.')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}
