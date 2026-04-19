<?php

namespace App\Filament\Resources\MeetingResource\RelationManagers;

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
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'present' => 'Present',
                        'absent' => 'Absent',
                        'fine_paid' => 'Fine Paid',
                        'fine_pending' => 'Fine Pending',
                    ])
                    ->required(),
                Forms\Components\DateTimePicker::make('attended_at'),
                Forms\Components\DateTimePicker::make('fine_paid_at'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Member Name')
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
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('attended_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'present' => 'Present',
                        'absent' => 'Absent',
                        'fine_paid' => 'Fine Paid',
                        'fine_pending' => 'Fine Pending',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('markPresent')
                    ->label('Present')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->hidden(fn ($record) => $record->status === 'present')
                    ->action(fn ($record) => $record->update([
                        'status' => 'present',
                        'attended_at' => now(),
                    ])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('markPresentBulk')
                        ->label('Mark as Present')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update([
                            'status' => 'present',
                            'attended_at' => now(),
                        ])),
                ]),
            ]);
    }
}
