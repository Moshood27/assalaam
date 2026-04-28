<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoanPenaltyResource\Pages;
use App\Models\LoanPenalty;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LoanPenaltyResource extends Resource
{
    protected static ?string $model = LoanPenalty::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationGroup = 'Loans';

    protected static ?string $navigationLabel = 'Loan Penalties';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\Select::make('qard_hasan_id')
                    ->relationship('qardHasan', 'id')
                    ->required(),
                Forms\Components\TextInput::make('months_defaulted')
                    ->numeric()
                    ->required(),
                Forms\Components\DateTimePicker::make('default_started_at'),
                Forms\Components\DateTimePicker::make('default_cleared_at'),
                Forms\Components\DateTimePicker::make('penalty_until'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Member')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.membership_number')
                    ->label('Membership #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.branch.name')
                    ->label('Branch')
                    ->sortable(),
                Tables\Columns\TextColumn::make('months_defaulted')
                    ->label('Months (Full)')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('formatted_default_duration')
                    ->label('Default Duration')
                    ->sortable(['default_started_at']),
                Tables\Columns\TextColumn::make('default_started_at')
                    ->label('Default Started')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('default_cleared_at')
                    ->label('Default Cleared')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('penalty_until')
                    ->label('Wait Until')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('formatted_wait_remaining')
                    ->label('Wait Remaining')
                    ->sortable(['penalty_until']),
            ])
            ->filters([
                Tables\Filters\Filter::make('active_penalty')
                    ->query(fn (Builder $query): Builder => $query->where('penalty_until', '>', now()))
                    ->label('Active Penalties Only'),
                Tables\Filters\SelectFilter::make('branch')
                    ->relationship('user.branch', 'name')
                    ->label('Branch'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListLoanPenalties::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Automatically created by system
    }
}
