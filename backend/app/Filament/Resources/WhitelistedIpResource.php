<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhitelistedIpResource\Pages;
use App\Models\WhitelistedIp;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;

class WhitelistedIpResource extends Resource
{
    protected static ?string $model = WhitelistedIp::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Settings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('IP Address Details')
                    ->schema([
                        TextInput::make('ip_address')
                            ->label('IP Address / CIDR')
                            ->required()
                            ->maxLength(45)
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g., 1.2.3.4 or 192.168.1.0/24')
                            ->helperText('IPv4, IPv6, or CIDR notation.'),
                        TextInput::make('label')
                            ->label('Description')
                            ->placeholder('e.g., Main Office')
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                        DateTimePicker::make('last_used_at')
                            ->label('Last Used At')
                            ->disabled()
                            ->dehydrated(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ip_address')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('label')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('last_used_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
        return auth()->user()->can('view_any_whitelisted_ip');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_whitelisted_ip');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update_whitelisted_ip');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_whitelisted_ip');
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
            'index' => Pages\ListWhitelistedIps::route('/'),
            'create' => Pages\CreateWhitelistedIp::route('/create'),
            'edit' => Pages\EditWhitelistedIp::route('/{record}/edit'),
        ];
    }
}
