<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TakafulPoolEntryResource\Pages;
use App\Models\TakafulPoolEntry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Services\TakafulService;
use Filament\Notifications\Notification;

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
                        if (!empty($data['value'])) {
                            $query->where('direction', $data['value']);
                        }
                    }),
                Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('From'),
                        Forms\Components\DatePicker::make('to')->label('To'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['from'])) {
                            $query->whereDate('created_at', '>=', $data['from']);
                        }
                        if (!empty($data['to'])) {
                            $query->whereDate('created_at', '<=', $data['to']);
                        }
                    }),
                Filter::make('user_id')
                    ->form([
                        Forms\Components\TextInput::make('user_id')->numeric()->label('User ID'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['user_id'])) {
                            // meta->user_id JSON search (MySQL/PG compatible via where)
                            $query->where('meta->user_id', (int) $data['user_id']);
                        }
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('exportCsv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn () => url('/api/admin/takaful/export/ledger.csv'))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('exportPdf')
                    ->label('Export PDF')
                    ->icon('heroicon-o-document-text')
                    ->url(fn () => url('/api/admin/takaful/export/ledger.pdf'))
                    ->openUrlInNewTab(),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTakafulPoolEntries::route('/'),
        ];
    }
}
