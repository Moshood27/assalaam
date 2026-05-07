<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChatMessageResource\Pages;
use App\Models\ChatMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ChatMessageResource extends Resource
{
    protected static ?string $model = ChatMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Adab Monitoring';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('chat_room_id')
                    ->relationship('room', 'name')
                    ->required(),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\Textarea::make('body')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('metadata.is_flagged')
                    ->label('Is Flagged (Adab Violation)'),
                Forms\Components\KeyValue::make('metadata'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('room.name')
                    ->label('Room')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Sender')
                    ->searchable(),
                Tables\Columns\TextColumn::make('body')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\IconColumn::make('metadata.is_flagged')
                    ->label('Flagged')
                    ->boolean()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_flagged')
                    ->label('Adab Violations')
                    ->placeholder('All Messages')
                    ->trueLabel('Flagged Messages')
                    ->falseLabel('Clean Messages')
                    ->queries(
                        true: fn (Builder $query) => $query->where('metadata->is_flagged', true),
                        false: fn (Builder $query) => $query->where('metadata->is_flagged', '!=', true)->orWhereNull('metadata->is_flagged'),
                    )
                    ->default(true),
                Tables\Filters\SelectFilter::make('room')
                    ->relationship('room', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListChatMessages::route('/'),
        ];
    }
}
