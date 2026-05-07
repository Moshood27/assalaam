<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\ChatRoomResource;
use App\Models\ChatRoom;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ChatRoomsRelationManager extends RelationManager
{
    protected static string $relationship = 'chatRooms';

    protected static ?string $title = 'Chat Rooms (Amanah)';

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'private' => 'gray',
                        'group' => 'info',
                        'official' => 'warning',
                        'support' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('messages_count')
                    ->counts('messages')
                    ->label('Messages'),
                Tables\Columns\TextColumn::make('last_message_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Last Activity'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('chat')
                    ->label('Enter Chat')
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->color('primary')
                    ->url(fn (ChatRoom $record): string => ChatRoomResource::getUrl('chat', ['record' => $record])),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
