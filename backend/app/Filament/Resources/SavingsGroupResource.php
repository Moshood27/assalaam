<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SavingsGroupResource\Pages;
use App\Filament\Resources\SavingsGroupResource\RelationManagers\MembersRelationManager;
use App\Models\SavingsGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SavingsGroupResource extends Resource
{
    protected static ?string $model = SavingsGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Savings & Investments';
    protected static ?int $navigationSort = 15;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('purpose')
                    ->maxLength(65535),
                Forms\Components\TextInput::make('monthly_contribution_amount')
                    ->numeric()
                    ->prefix('₦')
                    ->required()
                    ->step('0.01'),
                Forms\Components\Select::make('project_id')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload()
                    ->nullable(),
                Forms\Components\Select::make('creator_id')
                    ->relationship('creator', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required(),
                Forms\Components\DateTimePicker::make('started_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('monthly_contribution_amount')
                    ->label('Monthly Amount')
                    ->money('ngn')
                    ->sortable(),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Creator')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'completed' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('active_members_count')
                    ->label('Active Members')
                    ->counts('activeMembers'),
                Tables\Columns\TextColumn::make('total_contributions')
                    ->label('Total Contributions')
                    ->money('ngn')
                    ->getStateUsing(fn ($record) => $record->totalContributions()),
                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
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
            MembersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSavingsGroups::route('/'),
            'create' => Pages\CreateSavingsGroup::route('/create'),
            'edit' => Pages\EditSavingsGroup::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_savings_group');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_savings_group');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update_savings_group');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_savings_group');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(
                auth()->user()->hasRole('Branch Manager'),
                fn (Builder $query) => $query->whereHas('creator', fn (Builder $q) => $q->where('branch_id', auth()->user()->branch_id))
            );
    }
}
