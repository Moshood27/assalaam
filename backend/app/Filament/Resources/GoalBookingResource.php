<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GoalBookingResource\Pages;
use App\Models\GoalBooking;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GoalBookingResource extends Resource
{
    protected static ?string $model = GoalBooking::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Hajj & Umrah Savings';
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Member')
                    ->relationship('user', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable(['surname', 'name', 'other_names'])
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('savings_goal_id')
                    ->relationship('goal', 'title')
                    ->searchable()->preload()->required()->label('Savings Goal'),
                Forms\Components\TextInput::make('partner_name')->required()->maxLength(120),
                Forms\Components\TextInput::make('package')->maxLength(120),
                Forms\Components\TextInput::make('booking_amount')->label('Booking Amount (â‚¦)')->numeric()->minValue(0)->step('0.01')->prefix('â‚¦')->required(),
                Forms\Components\TextInput::make('commission_rate')->label('Commission Rate')->numeric()->minValue(0)->maxValue(1)->step('0.0001')->helperText('Fraction e.g. 0.05 = 5%'),
                Forms\Components\TextInput::make('commission_amount')->label('Commission Amount (â‚¦)')->numeric()->minValue(0)->step('0.01')->prefix('â‚¦'),
                Forms\Components\TextInput::make('reference')->disabled()->dehydrated(false)->columnSpanFull(),
                Forms\Components\Select::make('status')->options([
                    'booked' => 'Booked',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ])->required(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('user.full_name')
                    ->label('Member')
                    ->searchable(['surname', 'name', 'other_names'])
                    ->sortable(),
                TextColumn::make('goal.title')->label('Goal')->sortable()->searchable()->wrap()->limit(30),
                TextColumn::make('partner_name')->sortable()->searchable()->wrap()->limit(30),
                TextColumn::make('booking_amount')->label('Amount')->money('ngn', true)->sortable(),
                TextColumn::make('commission_rate')->label('Rate')->formatStateUsing(fn ($s) => number_format((float)$s * 100, 2) . '%'),
                TextColumn::make('commission_amount')->label('Commission')->money('ngn', true)->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('reference')->copyable()->label('Ref')->limit(16)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->since()->label('Created'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'booked' => 'Booked',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ]),
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
            'index' => Pages\ListGoalBookings::route('/'),
            'create' => Pages\CreateGoalBooking::route('/create'),
            'edit' => Pages\EditGoalBooking::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_goal_booking');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_goal_booking');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update_goal_booking');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_goal_booking');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(
                auth()->user()->hasRole('Branch Manager'),
                fn (Builder $query) => $query->whereHas('user', fn (Builder $q) => $q->where('branch_id', auth()->user()->branch_id))
            );
    }
}
