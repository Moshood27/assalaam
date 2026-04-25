<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceExcuseResource\Pages;
use App\Models\AttendanceRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class AttendanceExcuseResource extends Resource
{
    protected static ?string $model = AttendanceRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope-open';

    protected static ?string $navigationLabel = 'Review Excuses';

    protected static ?string $slug = 'attendance-excuses';

    protected static ?string $navigationGroup = 'Meetings';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('excuse_reason');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending_excuse')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.surname')
                    ->label('Member')
                    ->formatStateUsing(fn ($record) => $record->user?->full_name ?? '-')
                    ->searchable(['surname', 'name', 'other_names'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('meeting.name')
                    ->label('Meeting')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending_excuse' => 'warning',
                        'excused' => 'success',
                        'absent' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('excuse_type')
                    ->label('Type')
                    ->badge(),
                Tables\Columns\TextColumn::make('excuse_reason')
                    ->label('Reason')
                    ->limit(50)
                    ->wrap(),
                Tables\Columns\TextColumn::make('excused_at')
                    ->label('Submitted At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending_excuse' => 'Pending',
                        'excused' => 'Approved',
                        'absent' => 'Rejected',
                    ]),
                Tables\Filters\SelectFilter::make('excuse_type')
                    ->options([
                        'medical' => 'Medical',
                        'pregnancy' => 'Pregnancy',
                        'travel' => 'Travel',
                        'official' => 'Official',
                        'other' => 'Other',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
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
                            "Your excuse for meeting '{$record->meeting->name}' has been approved.",
                            ['type' => 'excuse_approved', 'meeting_id' => (string) $record->meeting_id]
                        );

                        Notification::make()->title('Excuse approved.')->success()->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'pending_excuse')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->action(function ($record, array $data) {
                        $record->update(['status' => 'absent']);

                        $record->user->notifyMember(
                            "❌ Excuse Rejected",
                            "Your excuse for meeting '{$record->meeting->name}' was not approved. Reason: " . $data['reason'],
                            ['type' => 'excuse_rejected', 'meeting_id' => (string) $record->meeting_id]
                        );

                        Notification::make()->title('Excuse rejected.')->danger()->send();
                    }),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ViewAction::make()
                    ->form([
                        Forms\Components\TextInput::make('user.full_name')
                            ->label('Member')
                            ->disabled()
                            ->formatStateUsing(fn ($record) => $record->user?->full_name),
                        Forms\Components\TextInput::make('meeting.name')
                            ->label('Meeting')
                            ->disabled(),
                        Forms\Components\TextInput::make('excuse_type')
                            ->label('Excuse Type')
                            ->disabled(),
                        Forms\Components\Textarea::make('excuse_reason')
                            ->label('Reason')
                            ->disabled()
                            ->rows(4),
                        Forms\Components\FileUpload::make('excuse_proof_path')
                            ->label('Proof Attachment')
                            ->disk('public')
                            ->directory('excuse_proofs')
                            ->downloadable()
                            ->openable(),
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendanceExcuses::route('/'),
        ];
    }
}
