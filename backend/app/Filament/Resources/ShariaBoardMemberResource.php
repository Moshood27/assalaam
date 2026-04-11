<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShariaBoardMemberResource\Pages;
use App\Filament\Resources\ShariaBoardMemberResource\RelationManagers;
use App\Models\ShariaBoardMember;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShariaBoardMemberResource extends Resource
{
    protected static ?string $model = ShariaBoardMember::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Sharia Board';
    protected static ?int $navigationSort = 40;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('title')
                    ->maxLength(255)
                    ->placeholder('e.g. Sheikh, Professor, Chairman'),
                Forms\Components\FileUpload::make('photo_url')
                    ->image()
                    ->directory('sharia-board'),
                Forms\Components\RichEditor::make('bio')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->default(true),
                Forms\Components\TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo_url')->circular(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->sortable(),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
            ])
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShariaBoardMembers::route('/'),
            'create' => Pages\CreateShariaBoardMember::route('/create'),
            'edit' => Pages\EditShariaBoardMember::route('/{record}/edit'),
        ];
    }
}
