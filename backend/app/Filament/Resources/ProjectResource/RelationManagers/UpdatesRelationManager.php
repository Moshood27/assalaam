<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Models\ProjectUpdate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UpdatesRelationManager extends RelationManager
{
    protected static string $relationship = 'updates';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->options([
                        'info' => 'Info',
                        'photo' => 'Photo',
                        'video' => 'Video',
                        'financial' => 'Financial',
                    ])
                    ->default('info')
                    ->required(),
                Forms\Components\Textarea::make('content')
                    ->rows(4)
                    ->columnSpanFull(),
                Forms\Components\Repeater::make('media_urls')
                    ->label('Media URLs (Photos/Videos)')
                    ->schema([
                        Forms\Components\TextInput::make('url')->label('URL')->url()->required()->maxLength(1000),
                    ])
                    ->default([])
                    ->collapsed()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->since()->label('Time')->sortable(),
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('type')->sortable(),
                TextColumn::make('media_urls')
                    ->label('Media')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state) : 0),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'info' => 'Info',
                        'photo' => 'Photo',
                        'video' => 'Video',
                        'financial' => 'Financial',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
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
