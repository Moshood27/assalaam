<?php

namespace App\Filament\Widgets;

use App\Models\WalletTransaction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentWalletActivity extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return 'Recent Wallet Activity';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                WalletTransaction::query()->latest()->limit(10)
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Time')
                    ->since(),
                TextColumn::make('user.name')
                    ->label('Member')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'success' => 'credit',
                        'danger' => 'debit',
                    ]),
                TextColumn::make('amount')
                    ->money('ngn', true),
                TextColumn::make('source')
                    ->label('Source/Reason'),
                TextColumn::make('reference')
                    ->label('Ref')
                    ->searchable(),
            ])
            ->paginated(false);
    }
}
