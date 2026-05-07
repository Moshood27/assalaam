<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChatRoomResource\Pages;
use App\Filament\Resources\ChatRoomResource\RelationManagers;
use App\Filament\Resources\ChatRoomResource\Widgets\ChatStatsWidget;
use App\Models\ChatRoom;
use App\Models\User;
use App\Services\ChatService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChatRoomResource extends Resource
{
    protected static ?string $model = ChatRoom::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Chat Rooms';

    protected static ?string $pluralLabel = 'Chat Rooms';

    protected static ?string $modelLabel = 'Chat Room';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('type', 'support')->whereNull('metadata->assigned_staff_id')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->options([
                        'private' => 'Private',
                        'group' => 'Group',
                        'official' => 'Official',
                        'support' => 'Support',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('avatar')
                    ->image()
                    ->directory('chat-avatars'),
                Forms\Components\Select::make('creator_id')
                    ->relationship('users', 'name')
                    ->searchable()
                    ->label('Creator'),
                Forms\Components\Select::make('metadata.assigned_staff_id')
                    ->label('Assigned Staff')
                    ->options(fn () => User::whereHas('roles', fn($q) => $q->whereIn('name', ['Staff', 'Admin']))
                        ->get()
                        ->mapWithKeys(function ($user) {
                            $status = ($user->last_activity_at && $user->last_activity_at->gt(now()->subMinutes(10))) ? '🟢' : '⚪';
                            return [$user->id => "{$status} {$user->name}"];
                        })
                    )
                    ->searchable(),
                Forms\Components\Section::make('Sharia & Security (Adab)')
                    ->schema([
                        Forms\Components\Toggle::make('metadata.requires_2fa')
                            ->label('Requires 2FA (Amanah)')
                            ->helperText('Sensitivity setting for financial data'),
                        Forms\Components\Select::make('metadata.gender_restriction')
                            ->label('Gender Restriction')
                            ->options([
                                'male' => 'Brothers Only',
                                'female' => 'Ladies Only',
                            ])
                            ->placeholder('No Restriction'),
                        Forms\Components\Toggle::make('metadata.is_official')
                            ->label('Official Room')
                            ->helperText('Mark as official for board/committees'),
                    ])->columns(3),
                Forms\Components\KeyValue::make('metadata')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Tables\Actions\Action::make('broadcast')
                    ->label('Broadcast Message')
                    ->icon('heroicon-o-megaphone')
                    ->color('warning')
                    ->form([
                        Forms\Components\Textarea::make('message')
                            ->required()
                            ->label('Message Content')
                            ->placeholder('Assalamu Alaikum, dear members...'),
                        Forms\Components\Select::make('type')
                            ->options([
                                'broadcast' => 'General Announcement',
                                'urgent' => 'Urgent Notification',
                            ])
                            ->default('broadcast')
                            ->required(),
                    ])
                    ->action(function (array $data, ChatService $chatService): void {
                        $chatService->broadcastMessage(
                            auth()->user(),
                            $data['message'],
                            $data['type']
                        );

                        Notification::make()
                            ->title('Broadcast message sent to all rooms')
                            ->success()
                            ->send();
                    }),
            ])
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'private' => 'gray',
                        'group' => 'info',
                        'official' => 'warning',
                        'support' => 'success',
                    }),
                Tables\Columns\TextColumn::make('assigned_staff')
                    ->label('Assigned Staff')
                    ->getStateUsing(fn (ChatRoom $record) => User::find($record->metadata['assigned_staff_id'] ?? null)?->name ?? 'Unassigned')
                    ->badge()
                    ->color(fn ($state) => $state !== 'Unassigned' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('members_count')
                    ->counts('members')
                    ->label('Members'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'private' => 'Private',
                        'group' => 'Group',
                        'official' => 'Official',
                        'support' => 'Support',
                    ]),
                Tables\Filters\TernaryFilter::make('assigned')
                    ->label('Staff Assignment')
                    ->placeholder('All Rooms')
                    ->trueLabel('Assigned Rooms')
                    ->falseLabel('Unassigned Rooms')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('metadata->assigned_staff_id'),
                        false: fn (Builder $query) => $query->whereNull('metadata->assigned_staff_id'),
                    ),
                Tables\Filters\TernaryFilter::make('has_flagged_messages')
                    ->label('Adab Violations')
                    ->placeholder('All Rooms')
                    ->trueLabel('Rooms with Flagged Messages')
                    ->falseLabel('Clean Rooms')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('messages', fn ($q) => $q->where('metadata->is_flagged', true)),
                        false: fn (Builder $query) => $query->whereDoesntHave('messages', fn ($q) => $q->where('metadata->is_flagged', true)),
                    ),
                Tables\Filters\SelectFilter::make('staff_filter')
                    ->label('Filter by Staff')
                    ->options(fn () => User::whereHas('roles', fn($q) => $q->whereIn('name', ['Staff', 'Admin']))->pluck('name', 'id'))
                    ->searchable()
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'], fn ($q, $v) => $q->where('metadata->assigned_staff_id', $v))),
            ])
            ->actions([
                Tables\Actions\Action::make('chat')
                    ->label('Enter Chat')
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->color('primary')
                    ->url(fn (ChatRoom $record): string => static::getUrl('chat', ['record' => $record])),
                Tables\Actions\Action::make('assignStaff')
                    ->label('Assign Staff')
                    ->icon('heroicon-o-user-plus')
                    ->modalWidth('sm')
                    ->form([
                        Forms\Components\Select::make('staff_id')
                            ->label('Staff Member')
                            ->options(fn () => User::whereHas('roles', fn($q) => $q->whereIn('name', ['Staff', 'Admin']))
                                ->get()
                                ->mapWithKeys(function ($user) {
                                    $status = ($user->last_activity_at && $user->last_activity_at->gt(now()->subMinutes(10))) ? '🟢' : '⚪';
                                    $assignedCount = ChatRoom::where('metadata->assigned_staff_id', $user->id)->count();
                                    return [$user->id => "{$status} {$user->name} ({$assignedCount} rooms)"];
                                })
                            )
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (ChatRoom $record, array $data, ChatService $chatService): void {
                        $staff = User::find($data['staff_id']);
                        $chatService->assignStaff($record, $staff);

                        Notification::make()
                            ->title('Staff assigned successfully')
                            ->success()
                            ->send();

                        Notification::make()
                            ->title('New Support Assignment (Amanah)')
                            ->body("You have been assigned to: {$record->name}")
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->color('success')
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('view')
                                    ->label('Open Chat')
                                    ->url(static::getUrl('chat', ['record' => $record])),
                            ])
                            ->sendToDatabase($staff);
                    })
                    ->visible(fn (ChatRoom $record) => $record->type === 'support' || $record->type === 'group'),
                Tables\Actions\Action::make('unassignStaff')
                    ->label('Unassign')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (ChatRoom $record): void {
                        $metadata = $record->metadata;
                        unset($metadata['assigned_staff_id']);
                        unset($metadata['assigned_at']);
                        $record->update(['metadata' => $metadata]);

                        Notification::make()
                            ->title('Staff unassigned successfully')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (ChatRoom $record) => isset($record->metadata['assigned_staff_id'])),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            ChatStatsWidget::class,
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChatRooms::route('/'),
            'create' => Pages\CreateChatRoom::route('/create'),
            'edit' => Pages\EditChatRoom::route('/{record}/edit'),
            'chat' => Pages\ChatRoomView::route('/{record}/chat'),
        ];
    }
}
