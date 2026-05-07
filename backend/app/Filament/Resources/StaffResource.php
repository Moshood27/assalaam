<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StaffResource\Pages;
use App\Models\User;
use App\Models\ChatRoom;
use App\Services\ChatService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class StaffResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $slug = 'chat-staff';

    protected static ?string $label = 'Staff Management';

    protected static ?string $pluralLabel = 'Staff Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Staff Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->disabled(),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->disabled(),
                        Forms\Components\Select::make('branch_id')
                            ->relationship('branch', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Assigned Branch'),
                    ])->columns(2),

                Forms\Components\Section::make('Roles & Permissions')
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->helperText('Assign "Staff" role to enable chat management.'),
                        Forms\Components\Toggle::make('is_admin')
                            ->label('Is Administrator'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('roles', fn ($q) => $q->whereIn('name', ['Staff', 'Branch Manager', 'Clerk', 'super_admin'])))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (User $record) => $record->email),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'Staff' => 'success',
                        'Branch Manager' => 'warning',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Branch')
                    ->sortable(),
                Tables\Columns\TextColumn::make('assigned_chats_count')
                    ->label('Active Chats')
                    ->getStateUsing(fn (User $record) => ChatRoom::where('metadata->assigned_staff_id', (string) $record->id)->count())
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('last_activity_at')
                    ->label('Last Active')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_online')
                    ->label('Online')
                    ->boolean()
                    ->getStateUsing(fn (User $record) => $record->last_activity_at && $record->last_activity_at->gt(now()->subMinutes(10))),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch')
                    ->relationship('branch', 'name'),
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('assign_chat')
                    ->label('Assign Chat')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('chat_room_id')
                            ->label('Unassigned Support Chat')
                            ->options(fn () => ChatRoom::where('type', 'support')
                                ->whereNull('metadata->assigned_staff_id')
                                ->get()
                                ->mapWithKeys(function ($room) {
                                    $creator = $room->creator?->name ?? 'User #' . ($room->members()->first()?->user_id ?? '?');
                                    $date = $room->created_at->format('M d, H:i');
                                    return [$room->id => "{$room->name} ({$creator}) - {$date}"];
                                })
                            )
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (User $record, array $data, ChatService $chatService): void {
                        $room = ChatRoom::findOrFail($data['chat_room_id']);
                        $chatService->assignStaff($room, $record);

                        Notification::make()
                            ->title('Chat assigned successfully')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListStaff::route('/'),
            'edit' => Pages\EditStaff::route('/{record}/edit'),
        ];
    }
}
