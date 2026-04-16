<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Investments';

    protected static ?int $navigationSort = 10;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

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
                        Forms\Components\TextInput::make('report_url')
                            ->label('Report URL (PDF)')
                            ->url()
                            ->maxLength(1000),
                        Forms\Components\Repeater::make('media_urls')
                            ->label('Site Photos / Media URLs')
                            ->schema([
                                Forms\Components\TextInput::make('url')->label('URL')->url()->required()->maxLength(1000),
                            ])
                            ->default([])
                            ->collapsed()
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Investment Units')
                    ->schema([
                        Forms\Components\Toggle::make('is_unit_based')
                            ->label('Is Unit-Based?')
                            ->reactive()
                            ->default(false),
                        Forms\Components\TextInput::make('unit_price')
                            ->label('Unit Price')
                            ->numeric()
                            ->prefix('₦')
                            ->visible(fn (callable $get) => $get('is_unit_based'))
                            ->required(fn (callable $get) => $get('is_unit_based')),
                        Forms\Components\TextInput::make('total_units')
                            ->label('Total Units')
                            ->numeric()
                            ->visible(fn (callable $get) => $get('is_unit_based'))
                            ->required(fn (callable $get) => $get('is_unit_based'))
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('available_units', $state);
                            }),
                        Forms\Components\TextInput::make('available_units')
                            ->label('Available Units')
                            ->numeric()
                            ->visible(fn (callable $get) => $get('is_unit_based'))
                            ->disabled()
                            ->dehydrated(true),
                    ])->columns(4),

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
            ->defaultSort('name', 'asc')
            ->columns([
                TextColumn::make('name')->sortable()->searchable(),
                IconColumn::make('active')->boolean()->label('Active')->sortable(),
                IconColumn::make('is_unit_based')->boolean()->label('Units?')->sortable(),
                TextColumn::make('available_units')->label('Available')->sortable(),
                TextColumn::make('total_units')->label('Total Units')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('target_amount')->label('Target')->money('ngn', true)->sortable(),
                TextColumn::make('management_fee_percent')->label('Mgmt Fee %')->sortable(),
                TextColumn::make('started_at')->dateTime()->since()->label('Started'),
                TextColumn::make('closed_at')->dateTime()->label('Closed')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')->label('Active'),
            ])
            ->actions([
                Tables\Actions\Action::make('downloadDistribution')
                    ->label('Profit Dist.')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('emerald')
                    ->url(fn (Project $record) => route('download-project-distribution', [
                        'id' => $record->id,
                        'token' => auth()->user()->createToken('FilamentProjectReport', ['*'], now()->addMinutes(5))->plainTextToken
                    ])),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_project');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_project');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update_project');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_project');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\InvestmentsRelationManager::class,
            RelationManagers\UpdatesRelationManager::class,
            RelationManagers\ProfitsRelationManager::class,
        ];
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
