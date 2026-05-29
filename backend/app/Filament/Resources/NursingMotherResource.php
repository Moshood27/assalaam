<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NursingMotherResource\Pages;
use App\Models\User;
use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class NursingMotherResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Nursing Mother';

    protected static ?string $slug = 'nursing-mother-requests';

    protected static ?string $navigationGroup = 'Members';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function ($query) {
                $query->whereNotNull('nursing_mother_status')
                    ->orWhere('is_nursing_mother', true)
                    ->orWhereNotNull('baby_birth_date')
                    ->orWhereNotNull('nursing_mother_grace_until');
            });
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('nursing_mother_status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Member')
                    ->searchable(['surname', 'name', 'other_names'])
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->orderBy("surname", $direction)
                            ->orderBy("name", $direction)
                            ->orderBy("other_names", $direction);
                    }),
                Tables\Columns\TextColumn::make('nursing_mother_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : 'Active (Manual)'),
                Tables\Columns\TextColumn::make('baby_birth_date')
                    ->label('Birth Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nursing_mother_grace_until')
                    ->label('Grace Until')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('nursing_mother_status')
                    ->label('Request Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->visible(fn ($record) => $record->nursing_mother_status === 'pending')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $months = (int) Setting::get('nursing_mother_grace_period_months', 3);
                        $record->update([
                            'nursing_mother_status' => 'approved',
                            'nursing_mother_grace_until' => now()->addMonths($months),
                        ]);

                        $record->notifyMember(
                            "�¤± Nursing Mother Grace Approved",
                            "Your nursing mother grace application has been approved. You are exempt from attendance fines until " . $record->nursing_mother_grace_until->toDateString() . ".",
                            ['type' => 'nursing_mother_grace_approved']
                        );

                        Notification::make()->title('Grace period approved for ' . $months . ' months.')->success()->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => $record->nursing_mother_status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->action(function ($record, array $data) {
                        $record->update(['nursing_mother_status' => 'rejected']);

                        $record->notifyMember(
                            "âŒ Nursing Mother Grace Rejected",
                            "Your nursing mother grace application was not approved. Reason: " . $data['reason'],
                            ['type' => 'nursing_mother_grace_rejected']
                        );

                        Notification::make()->title('Application rejected.')->danger()->send();
                    }),
                Tables\Actions\Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete Nursing Mother Grace Request?')
                    ->modalDescription('This will remove the nursing mother grace status and reset relevant fields for this member. The member record itself will NOT be deleted.')
                    ->action(function ($record) {
                        $record->update([
                            'nursing_mother_status' => null,
                            'nursing_mother_grace_until' => null,
                            'nursing_mother_proof_path' => null,
                            'baby_birth_date' => null,
                            'is_nursing_mother' => false,
                        ]);
                        Notification::make()->title('Nursing mother grace request deleted.')->success()->send();
                    }),
                Tables\Actions\ViewAction::make()
                    ->form([
                        Forms\Components\TextInput::make('full_name')
                            ->disabled(),
                        Forms\Components\TextInput::make('email')
                            ->disabled(),
                        Forms\Components\DatePicker::make('baby_birth_date')
                            ->disabled(),
                        Forms\Components\FileUpload::make('nursing_mother_proof_path')
                            ->label('Medical Proof')
                            ->disk('public')
                            ->directory('nursing_mother_proofs')
                            ->downloadable()
                            ->openable(),
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('deleteGrace')
                        ->label('Delete Selected')
                        ->icon('heroicon-m-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Nursing Mother Grace Requests?')
                        ->modalDescription('This will remove the nursing mother grace status from the selected members. The member records themselves will NOT be deleted.')
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $records->each(fn ($record) => $record->update([
                                'nursing_mother_status' => null,
                                'nursing_mother_grace_until' => null,
                                'nursing_mother_proof_path' => null,
                                'baby_birth_date' => null,
                                'is_nursing_mother' => false,
                            ]));
                            Notification::make()->title('Selected nursing mother grace requests deleted.')->success()->send();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNursingMothers::route('/'),
        ];
    }
}
