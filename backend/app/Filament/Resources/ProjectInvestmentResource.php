<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectInvestmentResource\Pages;
use App\Models\ProjectInvestment;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class ProjectInvestmentResource extends Resource
{
    protected static ?string $model = ProjectInvestment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Investments';
    protected static ?int $navigationSort = 20;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        // No manual creation/editing; investments are created from payments.
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')->since()->label('Time')->sortable(),
                TextColumn::make('user.surname')
                    ->label('Member')
                    ->formatStateUsing(fn ($record) => $record->user?->full_name ?? '-')
                    ->searchable(['surname', 'name', 'other_names'])
                    ->sortable(),
                TextColumn::make('project.name')->label('Project')->searchable()->sortable(),
                TextColumn::make('amount')->money('ngn', true)->sortable(),
                TextColumn::make('reference')->label('Ref')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'name'),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Member')
                    ->relationship('user', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable(['surname', 'name', 'other_names'])
                    ->preload(),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_project_investment');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_project_investment');
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
            'index' => Pages\ListProjectInvestments::route('/'),
        ];
    }
}
