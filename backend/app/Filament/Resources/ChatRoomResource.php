<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChatRoomResource\Pages;
use App\Filament\Resources\ChatRoomResource\RelationManagers;
use App\Filament\Resources\ChatRoomResource\Widgets\ChatStatsWidget;
use App\Models\ChatRoom;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChatRoomResource extends Resource
{
    protected static ?string $model = ChatRoom::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Communication';

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
                    ->options(User::whereHas('roles', fn($q) => $q->whereIn('name', ['Staff', 'Admin']))->pluck('name', 'id'))
                    ->searchable(),
                Forms\Components\KeyValue::make('metadata')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                Tables\Filters\TernaryFilter::make('has_flagged_messages')
                    ->label('Adab Violations')
                    ->placeholder('All Rooms')
                    ->trueLabel('Rooms with Flagged Messages')
                    ->falseLabel('Clean Rooms')
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('messages', fn ($q) => $q->where('metadata->is_flagged', true)),
                        false: fn (Builder $query) => $query->whereDoesntHave('messages', fn ($q) => $q->where('metadata->is_flagged', true)),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('chat')
                    ->label('Enter Chat')
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->color('primary')
                    ->url(fn (ChatRoom $record): string => static::getUrl('chat', ['record' => $record])),
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
