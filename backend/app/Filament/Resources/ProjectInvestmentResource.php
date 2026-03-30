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
                TextColumn::make('user.name')->label('Member')->searchable()->sortable(),
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
                    ->relationship('user', 'name'),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjectInvestments::route('/'),
        ];
    }
}
