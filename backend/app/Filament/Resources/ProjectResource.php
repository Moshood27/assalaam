<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Investments';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Parameters')
                    ->schema([
                        Forms\Components\TextInput::make('target_amount')
                            ->label('Target Amount')
                            ->numeric()
                            ->prefix('₦')
                            ->default(0)
                            ->required(),
                        Forms\Components\TextInput::make('management_fee_percent')
                            ->label('Management Fee (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(0)
                            ->required(),
                        Forms\Components\Toggle::make('active')
                            ->label('Active')
                            ->default(true),
                        Forms\Components\DateTimePicker::make('started_at')->label('Started At')->native(false),
                        Forms\Components\DateTimePicker::make('closed_at')->label('Closed At')->native(false),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                IconColumn::make('active')->boolean()->label('Active')->sortable(),
                TextColumn::make('target_amount')->label('Target')->money('ngn', true)->sortable(),
                TextColumn::make('management_fee_percent')->label('Mgmt Fee %')->sortable(),
                TextColumn::make('started_at')->dateTime()->since()->label('Started'),
                TextColumn::make('closed_at')->dateTime()->label('Closed')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')->label('Active'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
