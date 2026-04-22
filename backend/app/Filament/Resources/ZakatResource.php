<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ZakatResource\Pages;
use App\Models\Scheme;
use App\Models\User;
use App\Support\DurationHelper;
use App\Services\GoldSilverPriceService;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ZakatResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Financials';

    protected static ?string $modelLabel = 'Zakat Tracker';

    protected static ?string $pluralModelLabel = 'Zakat Trackers';

    protected static ?string $slug = 'zakat-trackers';

    public static function table(Table $table): Table
    {
        $priceService = app(GoldSilverPriceService::class);
        $goldPrice = $priceService->getSellPrice() ?: 0;
        $nisabValue = $priceService->getGoldNisab() ?: 0;

        return $table
            ->defaultSort(function (Builder $query): Builder {
                return $query->orderByRaw('LENGTH(name) ASC')->orderBy('name', 'asc');
            })
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->orderByRaw("LENGTH(name) $direction")
                            ->orderBy("name", $direction);
                    }),
                Tables\Columns\TextColumn::make('membership_number')
                    ->label('Member #')
                    ->searchable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Wallet')
                    ->money('ngn')
                    ->sortable(),
                Tables\Columns\TextColumn::make('gold_balance')
                    ->label('Gold (g)')
                    ->numeric(4)
                    ->sortable(),
                Tables\Columns\TextColumn::make('zakat_nisab_crossed_at')
                    ->label('Nisab Crossed')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('zakat_last_paid_at')
                    ->label('Last Paid')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_eligible')
                    ->label('Due Now')
                    ->boolean()
                    ->getStateUsing(function (User $record) {
                        if (!$record->zakat_nisab_crossed_at) return false;
                        $lunarDays = (int) config('zakat.lunar_days', 354);
                        return now()->diffInDays($record->zakat_nisab_crossed_at) >= $lunarDays;
                    })
                    ->sortable(query: function (Builder $query, string $direction) {
                        $lunarDays = (int) config('zakat.lunar_days', 354);
                        return $query->orderBy(
                            DB::raw('DATEDIFF(NOW(), zakat_nisab_crossed_at) >= ' . $lunarDays),
                            $direction
                        );
                    }),
                Tables\Columns\TextColumn::make('base_wealth')
                    ->label('Zakatable Assets')
                    ->money('ngn')
                    ->getStateUsing(fn (User $record) => $record->zakatBaseWealth($goldPrice)),
                Tables\Columns\TextColumn::make('estimated_zakat')
                    ->label('Est. Zakat (2.5%)')
                    ->money('ngn')
                    ->getStateUsing(function (User $record) use ($goldPrice) {
                        $base = $record->zakatBaseWealth($goldPrice);
                        return round($base * 0.025, 2);
                    }),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_eligible')
                    ->label('Zakat Due')
                    ->queries(
                        true: function (Builder $query) {
                            $lunarDays = (int) config('zakat.lunar_days', 354);
                            return $query->whereNotNull('zakat_nisab_crossed_at')
                                ->whereRaw('DATEDIFF(NOW(), zakat_nisab_crossed_at) >= ?', [$lunarDays]);
                        },
                        false: function (Builder $query) {
                            $lunarDays = (int) config('zakat.lunar_days', 354);
                            return $query->where(function ($q) use ($lunarDays) {
                                $q->whereNull('zakat_nisab_crossed_at')
                                    ->orWhereRaw('DATEDIFF(NOW(), zakat_nisab_crossed_at) < ?', [$lunarDays]);
                            });
                        },
                    ),
                Tables\Filters\SelectFilter::make('branch')
                    ->relationship('branch', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('View Report')
                    ->modalHeading('Detailed Zakat Report'),
            ])
            ->bulkActions([
                // No bulk actions for now
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        $priceService = app(GoldSilverPriceService::class);
        $goldPrice = $priceService->getSellPrice() ?: 0;
        $nisabValue = $priceService->getGoldNisab() ?: 0;

        return $infolist
            ->schema([
                Section::make('Member Info')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('membership_number')->label('Membership Number'),
                        TextEntry::make('branch.name')->label('Branch'),
                    ])->columns(3),

                Section::make('Zakat Tracking')
                    ->schema([
                        TextEntry::make('zakat_nisab_crossed_at')
                            ->label('Nisab First Crossed At')
                            ->dateTime()
                            ->color('warning'),
                        TextEntry::make('hawl_progress')
                            ->label('Hawl Progress (Days)')
                            ->getStateUsing(function (User $record) {
                                if (!$record->zakat_nisab_crossed_at || $record->zakat_nisab_crossed_at->year <= 1970) return 'Not started';
                                $days = (int) abs(now()->diffInDays($record->zakat_nisab_crossed_at));
                                return DurationHelper::format($days);
                            }),
                        TextEntry::make('zakat_last_paid_at')
                            ->label('Last Zakat Paid')
                            ->date()
                            ->placeholder('Never'),
                    ])->columns(3),

                Section::make('Zakatable Assets Breakdown')
                    ->schema([
                        TextEntry::make('balance')
                            ->label('Wallet Balance')
                            ->money('ngn'),
                        TextEntry::make('ordinary_savings')
                            ->label('Savings Balance')
                            ->money('ngn'),
                        TextEntry::make('shares_capital')
                            ->label('Shares Balance')
                            ->money('ngn'),
                        TextEntry::make('gold_value')
                            ->label('Digital Gold Value')
                            ->money('ngn')
                            ->getStateUsing(fn (User $record) => round($record->gold_balance * $goldPrice, 2)),
                        TextEntry::make('total_wealth')
                            ->label('Total Zakatable Wealth')
                            ->money('ngn')
                            ->weight('bold')
                            ->color('success')
                            ->getStateUsing(fn (User $record) => $record->zakatBaseWealth($goldPrice)),
                    ])->columns(2),

                Section::make('Zakat Calculation')
                    ->schema([
                        TextEntry::make('nisab_value')
                            ->label('Current Nisab (85g Gold)')
                            ->money('ngn')
                            ->getStateUsing(fn () => $nisabValue),
                        TextEntry::make('is_eligible')
                            ->label('Status')
                            ->getStateUsing(function (User $record) use ($nisabValue, $goldPrice) {
                                $lunarDays = (int) config('zakat.lunar_days', 354);
                                $days = ($record->zakat_nisab_crossed_at && $record->zakat_nisab_crossed_at->year > 1970) ? (int) abs(now()->diffInDays($record->zakat_nisab_crossed_at)) : 0;
                                $base = $record->zakatBaseWealth($goldPrice);

                                if ($base < $nisabValue) return 'Below Nisab';
                                if ($days < $lunarDays) {
                                    $formattedDays = DurationHelper::format($days);
                                    return "Hawl in progress ($formattedDays/$lunarDays days)";
                                }
                                return 'ZAKAT DUE';
                            })
                            ->color(function ($state) {
                                if ($state === 'ZAKAT DUE') return 'success';
                                if (str_contains($state, 'progress')) return 'warning';
                                return 'gray';
                            }),
                        TextEntry::make('zakat_due')
                            ->label('Estimated Zakat (2.5%)')
                            ->money('ngn')
                            ->weight('bold')
                            ->getStateUsing(function (User $record) use ($goldPrice) {
                                $base = $record->zakatBaseWealth($goldPrice);
                                return round($base * 0.025, 2);
                            }),
                    ])->columns(3),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListZakats::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        // Only show users who have crossed nisab or have a gold balance/significant wallet balance
        return parent::getEloquentQuery()
            ->where(function ($query) {
                $query->whereNotNull('zakat_nisab_crossed_at')
                    ->orWhere('gold_balance', '>', 0)
                    ->orWhere('balance', '>', 500000); // Random threshold to catch potential candidates
            });
    }
}
