<?php

namespace App\Filament\Resources\ChatRoomResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\User;
use App\Models\ChatRoomMember;
use App\Services\ChatService;
use Filament\Notifications\Notification;

class MembersRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Member')
                    ->options(User::all()->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('role')
                    ->options([
                        'member' => 'Member',
                        'admin' => 'Admin',
                        'staff' => 'Staff',
                    ])
                    ->default('member')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user.name')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'staff' => 'info',
                        'member' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('joined_at')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('ban')
                    ->label('Ban from Chat')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (ChatRoomMember $record, ChatService $chatService) => $chatService->banUser($record->user))
                    ->visible(fn (ChatRoomMember $record, ChatService $chatService) => !$chatService->isUserBanned($record->user)),
                Tables\Actions\Action::make('unban')
                    ->label('Unban')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn (ChatRoomMember $record, ChatService $chatService) => $chatService->unbanUser($record->user))
                    ->visible(fn (ChatRoomMember $record, ChatService $chatService) => $chatService->isUserBanned($record->user)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
