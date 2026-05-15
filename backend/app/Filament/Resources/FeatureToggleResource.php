<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeatureToggleResource\Pages;
use App\Models\Feature;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Builder;

class FeatureToggleResource extends Resource
{
    protected static ?string $model = Feature::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $label = 'Feature Toggle';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g., disable-withdrawals')
                            ->helperText('The internal key used in code.'),
                        Forms\Components\TextInput::make('label')
                            ->placeholder('e.g., Disable Withdrawals')
                            ->helperText('Friendly name for the admin UI.'),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('scope')
                            ->required()
                            ->default('global')
                            ->helperText('Use "global" for system-wide toggles.'),
                        Forms\Components\Toggle::make('value')
                            ->label('Enabled')
                            ->default(true)
                            ->afterStateHydrated(function (Forms\Components\Toggle $component, $state) {
                                $component->state((bool) $state);
                            })
                            ->dehydrateStateUsing(fn ($state) => $state ? 'true' : 'false'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label('Feature')
                    ->description(fn (Feature $record): string => $record->name)
                    ->searchable(['name', 'label'])
                    ->sortable(),
                TextColumn::make('scope')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'global' ? 'success' : 'warning')
                    ->sortable(),
                ToggleColumn::make('value')
                    ->label('Status')
                    ->afterStateUpdated(function (Feature $record, $state) {
                        $record->update(['value' => $state ? 'true' : 'false']);
                    }),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('scope')
                    ->options([
                        'global' => 'Global',
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
            'index' => Pages\ListFeatureToggles::route('/'),
            'create' => Pages\CreateFeatureToggle::route('/create'),
            'edit' => Pages\EditFeatureToggle::route('/{record}/edit'),
        ];
    }
}
