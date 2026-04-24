<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TakafulContributionResource\Pages;
use App\Models\TakafulContribution;
use App\Models\ShariahAuditLog as ShariahAudit;
use App\Models\User;
use App\Services\TakafulService;
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

class TakafulContributionResource extends Resource
{
    protected static ?string $model = TakafulContribution::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = 'Takaful Contributions';

    protected static ?string $navigationGroup = 'Takaful';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('user.surname')
                    ->label('Member')
                    ->formatStateUsing(fn ($record) => $record->user?->full_name ?? '-')
                    ->searchable(['surname', 'name', 'other_names'])
                    ->sortable(),
                TextColumn::make('user.membership_number')
                    ->label('Member #')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(function ($state) {
                        if (auth()->user()->hasRole('super_admin')) {
                            return $state;
                        }
                        return \Illuminate\Support\Str::mask($state, '*', 2, -2);
                    }),
                TextColumn::make('period')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Amount (₦)')
                    ->money('ngn', true)
                    ->sortable(),
                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'success',
                        'warning' => 'pending',
                        'danger' => 'failed',
                    ])
                    ->sortable(),
                TextColumn::make('reference')
                    ->label('Reference')
                    ->copyable()
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'success' => 'Success',
                        'pending' => 'Pending',
                        'failed' => 'Failed',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->where('status', $data['value']);
                        }
                    }),
                Filter::make('period')
                    ->form([
                        Forms\Components\TextInput::make('period')->placeholder('YYYY-MM')->label('Period'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['period'])) {
                            $query->where('period', $data['period']);
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
                        if (!empty($data['user_id'])) {
                            $query->where('user_id', (int) $data['user_id']);
                        }
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('chargeMonthly')
                    ->label('Charge Monthly')
                    ->icon('heroicon-o-bolt')
                    ->form([
                        Forms\Components\TextInput::make('period')
                            ->label('Period (YYYY-MM)')
                            ->placeholder(now()->format('Y-m')),
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->label('Amount (optional)')
                            ->prefix('₦'),
                        Forms\Components\Select::make('user_id')
                            ->label('Only Member (optional)')
                            ->relationship('user', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                            ->searchable(['surname', 'name', 'other_names']),
                        Forms\Components\Toggle::make('dry_run')
                            ->label('Dry-run (no writes)')
                            ->default(false),
                    ])
                    ->action(function (array $data) {
                        $service = app(TakafulService::class);
                        $period = $data['period'] ?? null;
                        $amount = isset($data['amount']) && $data['amount'] !== '' ? (float) $data['amount'] : null;
                        $userId = isset($data['user_id']) && $data['user_id'] !== '' ? (int) $data['user_id'] : null;
                        $dry = (bool) ($data['dry_run'] ?? false);
                        $result = $service->chargeMonthly($period, $amount, $userId, $dry);
                        if (!$dry) {
                            ShariahAudit::log(auth()->user(), 'charge_takaful_monthly', [
                                'period' => $period,
                                'amount' => $amount,
                                'user_id' => $userId,
                                'processed' => $result['processed'],
                                'created' => $result['created'],
                                'charged' => $result['charged'],
                            ]);
                        }
                        Notification::make()
                            ->title('Takaful charge '.($dry ? '(dry-run)' : 'completed'))
                            ->body("Processed: {$result['processed']} | Created: {$result['created']} | Charged: ₦".number_format((float)$result['charged'], 2)." | Insufficient: {$result['insufficient_funds']} | Balance: ₦".number_format((float)$result['balance'], 2))
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('exportSummaryCsv')
                    ->label('Export Summary CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn () => route('takaful.web.export.summary.csv'))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('exportSummaryPdf')
                    ->label('Export Summary PDF')
                    ->icon('heroicon-o-document-text')
                    ->url(fn () => route('takaful.web.export.summary.pdf'))
                    ->openUrlInNewTab(),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->hasRole('super_admin')), // Only visible to Super Admin
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTakafulContributions::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_takaful_contribution');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_takaful_contribution');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update_takaful_contribution');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_takaful_contribution');
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        // If the user is a Super Admin, let them see everything
        if ($user->hasRole('super_admin')) {
            return parent::getEloquentQuery();
        }

        // Otherwise, only show records belonging to the user's branch
        return parent::getEloquentQuery()->whereHas('user', function ($query) use ($user) {
            $query->where('branch_id', $user->branch_id);
        });
    }
}
