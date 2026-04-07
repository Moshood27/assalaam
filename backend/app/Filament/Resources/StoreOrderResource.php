<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoreOrderResource\Pages;
use App\Models\ShariahAuditLog as ShariahAudit;
use App\Models\StoreOrder;
use App\Models\Product;
use App\Models\WalletTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\SelectColumn;

class StoreOrderResource extends Resource
{
    protected static ?string $model = StoreOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Coop Store';
    protected static ?int $navigationSort = 92;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Order Details')
                    ->schema([
                        Forms\Components\TextInput::make('reference')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid (Cash)',
                                'murabaha_pending' => 'Murabaha Application',
                                'murabaha_active' => 'Murabaha Active',
                                'processing' => 'Processing',
                                'completed' => 'Completed/Delivered',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('pending'),
                    ])->columns(3),

                Forms\Components\Section::make('Financing (Murabaha)')
                    ->schema([
                        Forms\Components\Placeholder::make('financing_details')
                            ->label('Financing Application')
                            ->content(fn ($record) => (function () use ($record) {
                                if (!$record || !isset($record->meta['financing'])) return 'Not a Murabaha order';
                                $fin = $record->meta['financing'];
                                return "Type: " . ($fin['type'] ?? 'Murabaha') . " | Months: " . ($fin['months'] ?? '—') . " | Profit Rate: " . (isset($fin['profit_rate']) ? ($fin['profit_rate'] * 100) . '%' : '—');
                            })()),
                        Forms\Components\Placeholder::make('schedule_view')
                            ->label('Installment Schedule')
                            ->content(fn ($record) => (function () use ($record) {
                                if (!$record || !isset($record->meta['financing']['schedule'])) return 'No schedule found';
                                $html = "<table style='width: 100%; font-size: 0.8rem; border-collapse: collapse;'>";
                                $html .= "<thead><tr style='border-bottom: 1px solid #ddd;'><th style='text-align: left;'>#</th><th style='text-align: left;'>Due</th><th style='text-align: right;'>Amount</th><th style='text-align: left;'>Status</th></tr></thead><tbody>";
                                foreach ($record->meta['financing']['schedule'] as $it) {
                                    $html .= "<tr style='border-bottom: 1px solid #f0f0f0;'>";
                                    $html .= "<td>{$it['installment']}</td>";
                                    $html .= "<td>" . \Carbon\Carbon::parse($it['due_date'])->format('d M Y') . "</td>";
                                    $html .= "<td style='text-align: right;'>₦ " . number_format($it['amount'], 2) . "</td>";
                                    $html .= "<td><span style='padding: 2px 6px; border-radius: 4px; font-weight: bold; background: " . (strtolower($it['status']) === 'paid' ? '#d1fae5; color: #065f46;' : '#fee2e2; color: #991b1b;') . "'>{$it['status']}</span></td>";
                                    $html .= "</tr>";
                                }
                                $html .= "</tbody></table>";
                                return new \Illuminate\Support\HtmlString($html);
                            })()),
                    ])
                    ->visible(fn ($record) => isset($record->meta['financing']))
                    ->columnSpanFull(),

                Forms\Components\Section::make('Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->relationship('product', 'name')
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn ($state, Forms\Set $set) => (function() use ($state, $set) {
                                        if ($state) {
                                            $product = \App\Models\Product::find($state);
                                            if ($product) {
                                                $set('product_name', $product->name);
                                                $set('unit_price', $product->selling_price);
                                                $set('unit_cost', $product->cost_price);
                                            }
                                        }
                                    })()),
                                Forms\Components\TextInput::make('product_name')
                                    ->required(),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required(),
                                Forms\Components\TextInput::make('unit_price')
                                    ->numeric()
                                    ->prefix('₦')
                                    ->required(),
                                Forms\Components\TextInput::make('unit_cost')
                                    ->numeric()
                                    ->prefix('₦')
                                    ->required(),
                            ])->columns(5)
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->searchable()->sortable(),
                TextColumn::make('user.name')->label('Member')->searchable()->sortable(),
                TextColumn::make('total_amount')->label('Total')->money('ngn', true)->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'murabaha_pending' => 'info',
                        'murabaha_active' => 'success',
                        'processing' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid (Cash)',
                        'murabaha_pending' => 'Murabaha Application',
                        'murabaha_active' => 'Murabaha Active',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve_murabaha')
                    ->label('Approve Financing')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'murabaha_pending')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'murabaha_active']);
                        ShariahAudit::log(auth()->user(), 'approve_store_financing', [
                            'order_id' => $record->id,
                            'member_id' => $record->user_id,
                            'total_amount' => $record->total_amount,
                        ]);
                    }),

                Tables\Actions\Action::make('mark_processing')
                    ->label('Processing')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn ($record) => in_array($record->status, ['paid', 'murabaha_active']))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'processing']);
                        ShariahAudit::log(auth()->user(), 'mark_store_order_processing', [
                            'order_id' => $record->id,
                            'status' => 'processing',
                        ]);
                    }),

                Tables\Actions\Action::make('mark_completed')
                    ->label('Complete/Deliver')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->status, ['paid', 'murabaha_active', 'processing']))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['status' => 'completed']);
                        ShariahAudit::log(auth()->user(), 'complete_store_order', [
                            'order_id' => $record->id,
                            'status' => 'completed',
                        ]);
                    }),

                Tables\Actions\Action::make('cancel_order')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => !in_array($record->status, ['completed', 'cancelled']))
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        \Illuminate\Support\Facades\DB::transaction(function () use ($record) {
                            // Restore stock
                            foreach ($record->items as $item) {
                                \App\Models\Product::where('id', $item->product_id)
                                    ->where('track_stock', true)
                                    ->increment('stock_quantity', $item->quantity);
                            }

                            // If it was paid (cash), we might need to refund?
                            // For now, let's just mark as cancelled.
                            // Refund logic can be complex (e.g. partial refunds).
                            // But usually, if the admin cancels, they should probably refund the wallet.

                            if ($record->status === 'paid') {
                                $user = $record->user;
                                $user->increment('balance', $record->total_amount);

                                \App\Models\WalletTransaction::create([
                                    'user_id' => $user->id,
                                    'type' => 'credit',
                                    'amount' => $record->total_amount,
                                    'reference' => 'REFUND_' . $record->reference,
                                    'source' => 'store_refund',
                                    'meta' => ['store_order_id' => $record->id, 'note' => 'Order cancelled by admin'],
                                ]);
                            } elseif (str_starts_with($record->status, 'murabaha_')) {
                                $totalPaid = (float) ($record->meta['financing']['total_paid'] ?? 0);
                                if ($totalPaid > 0) {
                                    $user = $record->user;
                                    $user->increment('balance', $totalPaid);

                                    \App\Models\WalletTransaction::create([
                                        'user_id' => $user->id,
                                        'type' => 'credit',
                                        'amount' => $totalPaid,
                                        'reference' => 'REFUND_FIN_' . $record->reference,
                                        'source' => 'store_refund',
                                        'meta' => ['store_order_id' => $record->id, 'note' => 'Murabaha installments refunded'],
                                    ]);
                                }
                            }

                            $record->update(['status' => 'cancelled']);

                            ShariahAudit::log(auth()->user(), 'cancel_store_order', [
                                'order_id' => $record->id,
                                'status' => 'cancelled',
                                'total_amount' => $record->total_amount,
                            ]);
                        });
                    }),

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
        return auth()->user()->can('view_any_store_order');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_store_order');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update_store_order');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_store_order');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->when(
                auth()->user()->hasRole('Branch Manager'),
                fn ($query) => $query->whereHas('user', fn ($q) => $q->where('branch_id', auth()->user()->branch_id))
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStoreOrders::route('/'),
            'create' => Pages\CreateStoreOrder::route('/create'),
            'edit' => Pages\EditStoreOrder::route('/{record}/edit'),
            'view' => Pages\ViewStoreOrder::route('/{record}'),
        ];
    }
}
