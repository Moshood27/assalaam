<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SchemeResource\Pages;
use App\Models\Scheme;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Database\Eloquent\Builder;

class SchemeResource extends Resource
{
    protected static ?string $model = Scheme::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('min_amount')
                    ->numeric()
                    ->prefix('₦')
                    ->required(),
                Forms\Components\Toggle::make('active')
                    ->label('Active')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort(function (Builder $query): Builder {
                return $query->orderByRaw('LENGTH(name) ASC')->orderBy('name', 'asc');
            })
            ->columns([
                TextColumn::make('name')->sortable(query: function (Builder $query, string $direction): Builder {
                    return $query
                        ->orderByRaw("LENGTH(name) $direction")
                        ->orderBy("name", $direction);
                })->searchable(),
                TextColumn::make('min_amount')
                    ->label('Minimum Amount')
                    ->money('ngn', true)
                    ->sortable(),
                IconColumn::make('active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->extraAttributes(['onclick' => 'window.print()']),
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

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_scheme');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_scheme');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update_scheme');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_scheme');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchemes::route('/'),
            'create' => Pages\CreateScheme::route('/create'),
            'edit' => Pages\EditScheme::route('/{record}/edit'),
        ];
    }
}
