<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FineResource\Pages;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Models\ShariahAuditLog;
use App\Services\AttendanceService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FineResource extends Resource
{
    protected static ?string $model = AttendanceRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?string $navigationLabel = 'Fine Management';
    protected static ?string $modelLabel = 'Pending Fine';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function ($query) {
                $query->where('status', 'fine_pending')
                    ->orWhere(function ($q) {
                        $q->where('lateness_fine_paid', false)
                            ->where('lateness_fine_amount', '>', 0);
                    });
            });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->getOptionLabelFromRecordUsing(fn (User $record) => $record->full_name)
                    ->disabled(),
                Forms\Components\Select::make('meeting_id')
                    ->relationship('meeting', 'name')
                    ->disabled(),
                Forms\Components\Select::make('status')
                    ->options([
                        'present' => 'Present',
                        'absent' => 'Absent',
                        'fine_paid' => 'Fine Paid',
                        'fine_pending' => 'Fine Pending',
                    ]),
                Forms\Components\Toggle::make('lateness_fine_paid')
                    ->label('Lateness Fine Paid'),
                Forms\Components\TextInput::make('lateness_fine_amount')
                    ->numeric()
                    ->prefix('â‚¦'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.surname')
                    ->label('Member')
                    ->formatStateUsing(fn ($record) => $record->user?->full_name ?? '-')
                    ->searchable(['name', 'surname', 'other_names'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('meeting.name')
                    ->label('Meeting')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('meeting.date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->getStateUsing(fn (AttendanceRecord $record): string =>
                        ($record->status === 'fine_pending' ? 'Absence' : '') .
                        ($record->status === 'fine_pending' && !$record->lateness_fine_paid && $record->lateness_fine_amount > 0 ? ' & ' : '') .
                        (!$record->lateness_fine_paid && $record->lateness_fine_amount > 0 ? 'Lateness' : '')
                    )
                    ->badge()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Fine Amount')
                    ->getStateUsing(fn (AttendanceRecord $record): float =>
                        ($record->status === 'fine_pending' ? (float)($record->meeting->fine_amount ?? 500) : 0) +
                        (!$record->lateness_fine_paid ? (float)$record->lateness_fine_amount : 0)
                    )
                    ->money('NGN'),
                Tables\Columns\TextColumn::make('user.outstanding_fines')
                    ->label('Total User Debt')
                    ->money('NGN')
                    ->color('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('meeting')
                    ->relationship('meeting', 'name'),
                Tables\Filters\SelectFilter::make('branch')
                    ->relationship('user.branch', 'name'),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Member')
                    ->relationship('user', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable(['surname', 'name', 'other_names'])
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('markAsPaid')
                    ->label('Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark Fine as Paid')
                    ->modalDescription('This will reduce the user\'s total outstanding fines by the amount of this specific fine.')
                    ->action(function (AttendanceRecord $record) {
                        $absenceFine = $record->status === 'fine_pending' ? (float)($record->meeting->fine_amount ?? 500) : 0;
                        $latenessFine = !$record->lateness_fine_paid ? (float)$record->lateness_fine_amount : 0;
                        $total = $absenceFine + $latenessFine;

                        \Illuminate\Support\Facades\DB::transaction(function () use ($record, $total) {
                            $record->update([
                                'status' => 'fine_paid',
                                'fine_paid_at' => now(),
                                'lateness_fine_paid' => true,
                            ]);

                            $record->user->decrement('outstanding_fines', $total);

                            ShariahAuditLog::log(auth()->user(), 'manual_fine_settlement', [
                                'user_id' => $record->user_id,
                                'meeting_id' => $record->meeting_id,
                                'amount' => $total,
                            ]);
                        });

                        $record->user?->notifyMember(
                            'Fine Paid',
                            'Your fine of â‚¦'.number_format($total, 2).' for meeting "'.$record->meeting->name.'" has been marked as paid.',
                            ['type' => 'fine_paid']
                        );

                        Notification::make()
                            ->title('Fine marked as paid')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('waive')
                    ->label('Waive')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Waive Fine')
                    ->modalDescription('This will remove this fine from the user\'s debt without requiring payment.')
                    ->action(function (AttendanceRecord $record) {
                        $absenceFine = $record->status === 'fine_pending' ? (float)($record->meeting->fine_amount ?? 500) : 0;
                        $latenessFine = !$record->lateness_fine_paid ? (float)$record->lateness_fine_amount : 0;
                        $total = $absenceFine + $latenessFine;

                        \Illuminate\Support\Facades\DB::transaction(function () use ($record, $total) {
                            $record->update([
                                'status' => 'fine_paid', // Mark as paid to remove from pending
                                'fine_paid_at' => now(),
                                'lateness_fine_paid' => true,
                            ]);

                            $record->user->decrement('outstanding_fines', $total);

                            ShariahAuditLog::log(auth()->user(), 'manual_fine_waived', [
                                'user_id' => $record->user_id,
                                'meeting_id' => $record->meeting_id,
                                'amount_waived' => $total,
                            ]);
                        });

                        $record->user?->notifyMember(
                            'Fine Waived',
                            'Your fine of â‚¦'.number_format($total, 2).' for meeting "'.$record->meeting->name.'" has been waived.',
                            ['type' => 'fine_waived']
                        );

                        Notification::make()
                            ->title('Fine waived successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('markAsPaidBulk')
                        ->label('Mark Selected as Paid')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            \Illuminate\Support\Facades\DB::transaction(function () use ($records) {
                                foreach ($records as $record) {
                                    $absenceFine = $record->status === 'fine_pending' ? (float)($record->meeting->fine_amount ?? 500) : 0;
                                    $latenessFine = !$record->lateness_fine_paid ? (float)$record->lateness_fine_amount : 0;
                                    $total = $absenceFine + $latenessFine;

                                    $record->update([
                                        'status' => 'fine_paid',
                                        'fine_paid_at' => now(),
                                        'lateness_fine_paid' => true,
                                    ]);

                                    $record->user->decrement('outstanding_fines', $total);

                                    $record->user?->notifyMember(
                                        'Fine Paid',
                                        'Your fine of â‚¦'.number_format($total, 2).' for meeting "'.$record->meeting->name.'" has been marked as paid.',
                                        ['type' => 'fine_paid']
                                    );
                                }
                            });

                            Notification::make()
                                ->title('Fines marked as paid')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('waiveBulk')
                        ->label('Waive Selected')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            \Illuminate\Support\Facades\DB::transaction(function () use ($records) {
                                foreach ($records as $record) {
                                    $absenceFine = $record->status === 'fine_pending' ? (float)($record->meeting->fine_amount ?? 500) : 0;
                                    $latenessFine = !$record->lateness_fine_paid ? (float)$record->lateness_fine_amount : 0;
                                    $total = $absenceFine + $latenessFine;

                                    $record->update([
                                        'status' => 'fine_paid',
                                        'fine_paid_at' => now(),
                                        'lateness_fine_paid' => true,
                                    ]);

                                    $record->user->decrement('outstanding_fines', $total);

                                    $record->user?->notifyMember(
                                        'Fine Waived',
                                        'Your fine of â‚¦'.number_format($total, 2).' for meeting "'.$record->meeting->name.'" has been waived.',
                                        ['type' => 'fine_waived']
                                    );
                                }
                            });

                            Notification::make()
                                ->title('Fines waived successfully')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('wipeAll')
                    ->label('Wipe All Fines')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Wipe ALL Fines from the System')
                    ->modalDescription('This will permanently clear ALL outstanding fines for ALL members and mark ALL pending attendance records as paid. This action cannot be undone.')
                    ->action(function () {
                        app(AttendanceService::class)->wipeAllSystemFines();

                        ShariahAuditLog::log(auth()->user(), 'system_fine_wipe', [
                            'note' => 'Admin initiated a full system fine wipe',
                        ]);

                        Notification::make()
                            ->title('All system fines have been wiped')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFines::route('/'),
        ];
    }
}
