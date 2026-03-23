<?php

namespace App\Filament\Widgets;

use App\Models\QardHasan;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentPayouts extends BaseWidget
{
    public function getHeading(): ?string
    {
        return 'Recent Payouts';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                QardHasan::query()
                    ->where('status', 'active')
                    ->latest('updated_at')
            )
            ->columns([
                TextColumn::make('updated_at')
                    ->label('Disbursed')
                    ->since(),
                TextColumn::make('user.name')
                    ->label('Member')
                    ->searchable(),
                TextColumn::make('qard_id_string')
                    ->label('Loan ID')
                    ->searchable(),
                TextColumn::make('principal_amount')
                    ->label('Principal')
                    ->money('ngn', true)
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => ['active'],
                    ]),
            ])
            ->paginated([10]);
    }
}
