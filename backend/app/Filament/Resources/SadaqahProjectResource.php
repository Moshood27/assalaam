<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SadaqahProjectResource\Pages;
use App\Filament\Resources\SadaqahProjectResource\RelationManagers;
use App\Models\SadaqahProject;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SadaqahProjectResource extends Resource
{
    protected static ?string $model = SadaqahProject::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->options([
                                'well' => 'Water Well',
                                'mosque' => 'Mosque Repair/Build',
                                'medical' => 'Medical Bills',
                                'education' => 'Education',
                                'general' => 'General Charity',
                            ])
                            ->required(),
                        Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        TextInput::make('target_amount')
                            ->numeric()
                            ->prefix('₦')
                            ->required(),
                        TextInput::make('raised_amount')
                            ->numeric()
                            ->prefix('₦')
                            ->disabled()
                            ->dehydrated(false),
                        FileUpload::make('media_urls')
                            ->multiple()
                            ->image()
                            ->directory('sadaqah-projects'),
                        Toggle::make('active')
                            ->default(true),
                        DateTimePicker::make('started_at'),
                        DateTimePicker::make('closed_at'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('target_amount')
                    ->money('NGN')
                    ->sortable(),
                TextColumn::make('raised_amount')
                    ->money('NGN')
                    ->sortable(),
                TextColumn::make('progress')
                    ->label('Progress')
                    ->suffix('%')
                    ->state(fn ($record) => $record->progress),
                IconColumn::make('active')
                    ->boolean(),
                TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSadaqahProjects::route('/'),
        ];
    }
}
