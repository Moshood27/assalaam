<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MeetingResource\Pages;
use App\Filament\Resources\MeetingResource\RelationManagers;
use App\Models\Meeting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MeetingResource extends Resource
{
    protected static ?string $model = Meeting::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Operations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('branches')
                            ->relationship('branches', 'name')
                            ->multiple()
                            ->preload()
                            ->hint('Leave empty to apply to all branches'),
                        Forms\Components\DatePicker::make('date')
                            ->required(),
                        Forms\Components\TimePicker::make('start_time')
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $get, $set) {
                                $duration = $get('duration_minutes');
                                if ($state && $duration) {
                                    $start = \Carbon\Carbon::parse($state);
                                    $set('end_time', $start->addMinutes((int) $duration)->toTimeString());
                                }
                            }),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Duration (Minutes)')
                            ->numeric()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $get, $set) {
                                $start = $get('start_time');
                                if ($start && $state) {
                                    $startTime = \Carbon\Carbon::parse($start);
                                    $set('end_time', $startTime->addMinutes((int) $state)->toTimeString());
                                }
                            })
                            ->helperText('Use this to automatically set end time based on duration'),
                        Forms\Components\TimePicker::make('end_time')
                            ->required(),
                        Forms\Components\TextInput::make('pin')
                            ->required()
                            ->maxLength(10),
                        Forms\Components\Select::make('status')
                            ->options([
                                'scheduled' => 'Scheduled',
                                'ongoing' => 'Ongoing',
                                'completed' => 'Completed',
                                'audited' => 'Audited',
                            ])->default('scheduled')
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Venue & Location')
                    ->schema([
                        Forms\Components\View::make('filament.components.map-picker')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('venue_lat')
                            ->numeric()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, $set) => $set('venue_lat', $state))
                            ->step('0.00000001'),
                        Forms\Components\TextInput::make('venue_lng')
                            ->numeric()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, $set) => $set('venue_lng', $state))
                            ->step('0.00000001'),
                        Forms\Components\TextInput::make('radius_meters')
                            ->numeric()
                            ->default(config('cooperative.attendance.radius_meters', 50)),
                    ])->columns(2),

                Forms\Components\Section::make('Fines & Fees')
                    ->schema([
                        Forms\Components\TextInput::make('fine_amount')
                            ->numeric()
                            ->prefix('₦')
                            ->default(fn() => app(\App\Services\AdministrativeChargeService::class)->getCharge('attendance_fine', config('cooperative.attendance.default_fine', 500))),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(function (Builder $query): Builder {
                return $query->orderByRaw('LENGTH(name) ASC')->orderBy('name', 'asc');
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->orderByRaw("LENGTH(name) $direction")
                            ->orderBy("name", $direction);
                    }),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branches.name')
                    ->label('Branches')
                    ->badge()
                    ->placeholder('All Branches')
                    ->sortable(),
                Tables\Columns\TextColumn::make('pin')
                    ->label('PIN'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'scheduled' => 'gray',
                        'ongoing' => 'warning',
                        'completed' => 'success',
                        'audited' => 'info',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branches')
                    ->relationship('branches', 'name')
                    ->multiple(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'ongoing' => 'Ongoing',
                        'completed' => 'Completed',
                        'audited' => 'Audited',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('audit')
                    ->label('Audit Now')
                    ->icon('heroicon-o-document-check')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (Meeting $record): bool => $record->status === 'completed')
                    ->action(function () {
                        Artisan::call('app:audit-attendance');
                        Notification::make()
                            ->title('Meeting Audit processed')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AttendanceRecordsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMeetings::route('/'),
            'create' => Pages\CreateMeeting::route('/create'),
            'edit' => Pages\EditMeeting::route('/{record}/edit'),
        ];
    }
}
