<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChatAuditResource\Pages;
use App\Models\ChatMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChatAuditResource extends Resource
{
    protected static ?string $model = ChatMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $pluralModelLabel = 'Chat Audit Log';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('chat_room_id')
                    ->relationship('room', 'name')
                    ->disabled(),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->disabled(),
                Forms\Components\TextInput::make('type')
                    ->disabled(),
                Forms\Components\Textarea::make('body')
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\KeyValue::make('metadata')
                    ->disabled()
                    ->columnSpanFull(),
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
                    ->trueColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('chat_room_id')
                    ->relationship('room', 'name')
                    ->label('Filter by Room'),
                Tables\Filters\TernaryFilter::make('is_flagged')
                    ->label('Adab Violations')
                    ->queries(
                        true: fn (Builder $query) => $query->where('metadata->is_flagged', true),
                        false: fn (Builder $query) => $query->where('metadata->is_flagged', false),
                    ),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChatAudits::route('/'),
        ];
    }
}
