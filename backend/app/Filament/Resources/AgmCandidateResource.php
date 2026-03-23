<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgmCandidateResource\Pages;
use App\Models\AgmCandidate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AgmCandidateResource extends Resource
{
    protected static ?string $model = AgmCandidate::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'AGM & Voting';
    protected static ?int $navigationSort = 31;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('session_id')
                    ->relationship('session', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('AGM Session')
                    ->native(false),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('position')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g., President, Secretary'),
                Forms\Components\Textarea::make('manifesto')
                    ->rows(5)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('photo_url')
                    ->label('Photo URL')
                    ->url()
                    ->columnSpanFull(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('position')->badge()->searchable()->sortable(),
                TextColumn::make('session.name')->label('Session')->searchable()->sortable(),
                TextColumn::make('votes_count')->label('Votes')->counts('votes')->sortable(),
                TextColumn::make('created_at')->since()->label('Created')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('session_id')
                    ->relationship('session', 'name')
                    ->label('Session'),
                Tables\Filters\Filter::make('position')
                    ->form([
                        Forms\Components\TextInput::make('value')->label('Position'),
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $query->where('position', 'like', '%' . $data['value'] . '%');
                        }
                    }),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgmCandidates::route('/'),
            'create' => Pages\CreateAgmCandidate::route('/create'),
            'edit' => Pages\EditAgmCandidate::route('/{record}/edit'),
        ];
    }
}
