<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SavingsGoalResource\Pages;
use App\Filament\Resources\SavingsGoalResource\RelationManagers\BookingsRelationManager;
use App\Models\SavingsGoal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SavingsGoalResource extends Resource
{
    protected static ?string $model = SavingsGoal::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Hajj & Umrah Savings';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->label('Member'),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(120),
                Forms\Components\TextInput::make('target_amount')
                    ->label('Target Amount (₦)')
                    ->numeric()
                    ->minValue(0.01)
                    ->step('0.01')
                    ->prefix('₦')
                    ->required(),
                Forms\Components\TextInput::make('saved_amount')
                    ->label('Saved Amount (₦)')
                    ->numeric()
                    ->minValue(0)
                    ->step('0.01')
                    ->prefix('₦')
                    ->default(0),
                Forms\Components\DatePicker::make('target_date')
                    ->native(false)
                    ->label('Target Date')
                    ->displayFormat('Y-m-d')
                    ->closeOnDateSelection(),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'booked' => 'Booked',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required()
                    ->native(false),
                Forms\Components\Placeholder::make('progress_placeholder')
                    ->label('Progress')
                    ->content(fn ($record, $get) => (function () use ($record, $get) {
                        $target = (float)($get('target_amount') ?? ($record->target_amount ?? 0));
                        $saved = (float)($get('saved_amount') ?? ($record->saved_amount ?? 0));
                        if ($target <= 0) return '0%';
                        $p = round(min(100, ($saved / $target) * 100), 2);
                        return $p . '%';
                    })()),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')->label('Member')->searchable()->sortable(),
                TextColumn::make('title')->wrap()->limit(40)->searchable(),
                TextColumn::make('target_amount')->label('Target')->money('ngn', true)->sortable(),
                TextColumn::make('saved_amount')->label('Saved')->money('ngn', true)->sortable(),
                TextColumn::make('progress')
                    ->label('Progress')
                    ->getStateUsing(function ($record) {
                        $t = (float)($record->target_amount ?? 0);
                        $s = (float)($record->saved_amount ?? 0);
                        if ($t <= 0) return '0%';
                        $p = round(min(100, ($s / $t) * 100), 2);
                        return $p . '%';
                    }),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('created_at')->since()->label('Created')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'booked' => 'Booked',
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

    public static function getRelations(): array
    {
        return [
            BookingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSavingsGoals::route('/'),
            'create' => Pages\CreateSavingsGoal::route('/create'),
            'edit' => Pages\EditSavingsGoal::route('/{record}/edit'),
        ];
    }
}
