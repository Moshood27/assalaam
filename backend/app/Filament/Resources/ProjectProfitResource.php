<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectProfitResource\Pages;
use App\Filament\Resources\ProjectProfitResource\RelationManagers;
use App\Jobs\DistributeProjectProfit;
use App\Models\Project;
use App\Models\ProjectProfit;
use App\Models\ShariahAuditLog as ShariahAudit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectProfitResource extends Resource
{
    protected static ?string $model = ProjectProfit::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Investments';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'Project Profit';

    protected static ?string $pluralModelLabel = 'Project Profits';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Profit Event')
                    ->schema([
                        Forms\Components\Select::make('project_id')
                            ->label('Project')
                            ->options(Project::query()->pluck('name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $project = Project::find($state);
                                if ($project) {
                                    $set('management_fee_percent', (float) $project->management_fee_percent);
                                }
                            }),
                        Forms\Components\TextInput::make('gross_profit')
                            ->label('Gross Profit')
                            ->numeric()
                            ->prefix('₦')
                            ->default(0)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $gross = (float) ($state ?? 0);
                                $pct = (float) ($get('management_fee_percent') ?? 0);
                                $fee = round($gross * $pct / 100, 2);
                                $net = round($gross - $fee, 2);
                                $set('management_fee_amount', $fee);
                                $set('net_distributable', $net);
                            }),
                        Forms\Components\TextInput::make('management_fee_percent')
                            ->label('Management Fee (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(0)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $pct = (float) ($state ?? 0);
                                $gross = (float) ($get('gross_profit') ?? 0);
                                $fee = round($gross * $pct / 100, 2);
                                $net = round($gross - $fee, 2);
                                $set('management_fee_amount', $fee);
                                $set('net_distributable', $net);
                            }),
                        Forms\Components\TextInput::make('management_fee_amount')
                            ->label('Management Fee (Amount)')
                            ->numeric()
                            ->prefix('₦')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('net_distributable')
                            ->label('Net Distributable')
                            ->numeric()
                            ->prefix('₦')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Textarea::make('note')->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->since()->label('Time')->sortable(),
                TextColumn::make('project.name')->label('Project')->searchable()->sortable(),
                TextColumn::make('gross_profit')->label('Gross')->money('ngn', true)->sortable(),
                TextColumn::make('management_fee_percent')->label('Mgmt %')->sortable(),
                TextColumn::make('management_fee_amount')->label('Mgmt Fee')->money('ngn', true)->sortable(),
                TextColumn::make('net_distributable')->label('Net')->money('ngn', true)->sortable(),
                TextColumn::make('payouts_count')->label('Payouts')->counts('payouts')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('distribute')
                    ->label('Distribute')
                    ->icon('heroicon-o-currency-dollar')
                    ->requiresConfirmation()
                    ->visible(fn (ProjectProfit $record) => $record->payouts()->count() === 0)
                    ->action(function (ProjectProfit $record) {
                        ShariahAudit::log(auth()->user(), 'distribute_project_profit', [
                            'project_profit_id' => $record->id,
                            'project_id' => $record->project_id,
                            'net_distributable' => $record->net_distributable,
                        ]);
                        DistributeProjectProfit::dispatch($record->id);
                    }),
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
        return auth()->user()->can('view_any_project_profit');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_project_profit');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update_project_profit');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_project_profit');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PayoutsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjectProfits::route('/'),
            'create' => Pages\CreateProjectProfit::route('/create'),
            'edit' => Pages\EditProjectProfit::route('/{record}/edit'),
        ];
    }
}
