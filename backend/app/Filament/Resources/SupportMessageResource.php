<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportMessageResource\Pages;
use App\Models\SupportMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupportMessageResource extends Resource
{
    protected static ?string $model = SupportMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Settings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->disabled(),
                Forms\Components\TextInput::make('sender_type')
                    ->disabled(),
                Forms\Components\Textarea::make('body')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('read_at')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('user.name')->label('Member')->searchable(),
                TextColumn::make('sender_type')->badge()->colors([
                    'success' => ['admin'],
                    'info' => ['user'],
                ]),
                TextColumn::make('body')->limit(100),
                TextColumn::make('read_at')->dateTime()->placeholder('Unread'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('read')
                    ->query(fn ($query, $state) => match ($state) {
                        true => $query->whereNotNull('read_at'),
                        false => $query->whereNull('read_at'),
                        default => $query,
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_support_message');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_support_message');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update_support_message');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_support_message');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->when(
                auth()->user()->hasRole('Branch Manager'),
                fn ($query) => $query->whereHas('user', fn ($q) => $q->where('branch_id', auth()->user()->branch_id))
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportMessages::route('/'),
        ];
    }
}
