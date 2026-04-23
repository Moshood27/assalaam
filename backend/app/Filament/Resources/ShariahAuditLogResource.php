<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShariahAuditLogResource\Pages;
use App\Models\ShariahAuditLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class ShariahAuditLogResource extends Resource
{
    protected static ?string $model = ShariahAuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Security & Logs';

    protected static ?string $navigationLabel = 'Shariah Audit Trail';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Member/User')
                    ->relationship('user', 'surname')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable(['surname', 'name', 'other_names'])
                    ->disabled(),
                Forms\Components\TextInput::make('action')
                    ->disabled(),
                Forms\Components\KeyValue::make('payload')
                    ->formatStateUsing(function ($state) {
                        if (!is_array($state)) {
                            return [];
                        }

                        $user = auth()->user();
                        $isSuperAdmin = $user && $user->hasRole('super_admin');

                        $sensitive = ['bvn', 'membership_number', 'account_number'];

                        foreach ($state as $key => $value) {
                            if (!$isSuperAdmin && in_array($key, $sensitive)) {
                                $state[$key] = is_string($value) ? \Illuminate\Support\Str::mask($value, '*', 2, -2) : '*******';
                            } elseif (is_array($value) || is_object($value)) {
                                $state[$key] = json_encode($value);
                            }
                        }

                        return $state;
                    })
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Timestamp')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('user.surname')
                    ->label('Member/User')
                    ->formatStateUsing(fn ($record) => $record->user?->full_name ?? '-')
                    ->searchable(['surname', 'name', 'other_names'])
                    ->sortable(),
                TextColumn::make('action')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payload')
                    ->label('Summary')
                    ->limit(50)
                    ->getStateUsing(function (ShariahAuditLog $record) {
                        $payload = $record->payload;
                        if (!auth()->user()->hasRole('super_admin') && is_array($payload)) {
                            $sensitive = ['bvn', 'membership_number', 'account_number'];
                            foreach ($sensitive as $key) {
                                if (isset($payload[$key]) && is_string($payload[$key])) {
                                    $payload[$key] = \Illuminate\Support\Str::mask($payload[$key], '*', 2, -2);
                                }
                            }
                        }
                        return json_encode($payload);
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for audit logs for safety
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShariahAuditLogs::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(
                auth()->user()->hasRole('Branch Manager'),
                fn (Builder $query) => $query->whereHas('user', fn (Builder $q) => $q->where('branch_id', auth()->user()->branch_id))
            );
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_shariah_audit_log');
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
        return false;
    }
}
