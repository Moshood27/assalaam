<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use App\Filament\Resources\MigrationDiscrepancyResource\Pages;
use App\Filament\Resources\UserResource;

class MigrationDiscrepancyResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationGroup = 'Admin Tools';
    protected static ?string $navigationLabel = 'Migration Discrepancies';
    protected static ?string $modelLabel = 'Migration Discrepancy';
    protected static ?int $navigationSort = 96;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('discrepancy_reported_at')
            ->whereNull('verified_at');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Member Information')
                    ->schema([
                        Forms\Components\TextInput::make('full_name')->label('Name')->disabled(),
                        Forms\Components\TextInput::make('membership_number')->disabled(),
                        Forms\Components\DateTimePicker::make('discrepancy_reported_at')->disabled(),
                    ])->columns(3),
                Forms\Components\Section::make('Discrepancy Details')
                    ->schema([
                        Forms\Components\Placeholder::make('latest_message')
                            ->label('Details from Support Chat')
                            ->content(fn (User $record) => $record->supportMessages()
                                ->where('body', 'like', 'MIGRATION DISCREPANCY REPORT%')
                                ->latest()
                                ->first()?->body ?? 'No report details found.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Member')
                    ->searchable(['name', 'surname', 'other_names'])
                    ->sortable(),
                TextColumn::make('membership_number')
                    ->label('Member ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('discrepancy_reported_at')
                    ->label('Reported On')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('latest_discrepancy')
                    ->label('Details')
                    ->getStateUsing(fn (User $record) => \Illuminate\Support\Str::limit(
                        $record->supportMessages()
                            ->where('body', 'like', 'MIGRATION DISCREPANCY REPORT%')
                            ->latest()
                            ->first()?->body ?? '',
                        100
                    )),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Action::make('verify')
                    ->label('Verify & Clear')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Verify Migration Balance?')
                    ->modalDescription('This will mark the member\'s migration as verified and remove the discrepancy alert from their dashboard.')
                    ->action(function (User $record) {
                        $record->update(['verified_at' => now()]);
                        Notification::make()
                            ->success()
                            ->title('User migration verified')
                            ->body("Migration for {$record->full_name} has been marked as verified.")
                            ->send();
                    }),
                Action::make('view_user')
                    ->label('View Profile')
                    ->icon('heroicon-o-user')
                    ->color('info')
                    ->url(fn (User $record) => UserResource::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMigrationDiscrepancies::route('/'),
        ];
    }
}
