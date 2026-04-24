<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TakafulPoolEntryResource\Pages;
use App\Models\ShariahAuditLog as ShariahAudit;
use App\Models\TakafulPoolEntry;
use App\Models\User;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TakafulPoolEntryResource extends Resource
{
    protected static ?string $model = TakafulPoolEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Takaful Pool Ledger';

    protected static ?string $navigationGroup = 'Takaful';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                BadgeColumn::make('direction')
                    ->colors([
                        'success' => 'credit',
                        'danger' => 'debit',
                    ])
                    ->icons([
                        'heroicon-o-arrow-up-right' => 'credit',
                        'heroicon-o-arrow-down-left' => 'debit',
                    ])
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Amount (₦)')
                    ->money('ngn', true)
                    ->sortable(),
                TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('meta.user_id')
                    ->label('User ID')
                    ->sortable(),
                TextColumn::make('meta.period')
                    ->label('Period')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('meta.qard_code')
                    ->label('Qard Code')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('meta.reason')
                    ->label('Reason')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('direction')
                    ->options([
                        'credit' => 'Credit',
                        'debit' => 'Debit',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['value'])) {
                            $query->where('direction', $data['value']);
                        }
                    }),
                Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('From'),
                        Forms\Components\DatePicker::make('to')->label('To'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['from'])) {
                            $query->whereDate('created_at', '>=', $data['from']);
                        }
                        if (! empty($data['to'])) {
                            $query->whereDate('created_at', '<=', $data['to']);
                        }
                    }),
                Filter::make('user_id')
                    ->form([
                        Forms\Components\Select::make('user_id')
                            ->label('Member')
                            ->relationship('user', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                            ->searchable(['surname', 'name', 'other_names']),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['user_id'])) {
                            $query->where('meta->user_id', (int) $data['user_id']);
                        }
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('recordAdjustment')
                    ->label('Manual Entry')
                    ->icon('heroicon-o-plus-circle')
                    ->form([
                        Forms\Components\Select::make('direction')
                            ->options([
                                'credit' => 'Credit (Inflow)',
                                'debit' => 'Debit (Outflow)',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->minValue(0.01)
                            ->required()
                            ->prefix('₦'),
                        Forms\Components\TextInput::make('reason')
                            ->label('Reason/Description')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('user_id')
                            ->label('Related Member (optional)')
                            ->relationship('user', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                            ->searchable(['surname', 'name', 'other_names']),
                    ])
                    ->action(function (array $data) {
                        $direction = $data['direction'];
                        $amount = (float) $data['amount'];
                        $reason = $data['reason'];
                        $userId = $data['user_id'] ?? null;

                        $entry = TakafulPoolEntry::create([
                            'direction' => $direction,
                            'amount' => $amount,
                            'reference' => 'MANUAL-'.strtoupper(uniqid()),
                            'meta' => [
                                'reason' => $reason,
                                'user_id' => $userId,
                                'admin_id' => auth()->id(),
                            ],
                        ]);

                        ShariahAudit::log(auth()->user(), 'takaful_pool_manual_adjustment', [
                            'entry_id' => $entry->id,
                            'direction' => $direction,
                            'amount' => $amount,
                            'reason' => $reason,
                            'user_id' => $userId,
                        ]);

                        Notification::make()
                            ->title('Manual entry recorded')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('exportCsv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn () => route('takaful.web.export.ledger.csv'))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('exportPdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-text')
                    ->url(fn () => route('takaful.web.export.ledger.pdf'))
                    ->openUrlInNewTab(),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_takaful_pool_entry');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_takaful_pool_entry');
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_takaful_pool_entry');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(
                auth()->user()->hasRole('Branch Manager'),
                fn (Builder $query) => $query->whereHas('user', fn (Builder $q) => $q->where('branch_id', auth()->user()->branch_id))
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTakafulPoolEntries::route('/'),
        ];
    }
}
