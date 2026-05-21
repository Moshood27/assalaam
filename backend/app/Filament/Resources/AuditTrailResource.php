<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditTrailResource\Pages;
use App\Models\Branch;
use App\Models\Contribution;
use App\Models\ExpenseEntry;
use App\Models\Feature;
use App\Models\GoalBooking;
use App\Models\IncomeEntry;
use App\Models\LedgerEntry;
use App\Models\LedgerJournal;
use App\Models\MemberApplication;
use App\Models\Product;
use App\Models\ProjectInvestment;
use App\Models\ProjectProfit;
use App\Models\ProjectProfitPayout;
use App\Models\QardHasan;
use App\Models\QardHasanRepayment;
use App\Models\SavingsGoal;
use App\Models\Scheme;
use App\Models\Setting;
use App\Models\ShariahAuditLog;
use App\Models\StoreOrder;
use App\Models\TakafulContribution;
use App\Models\TransactionApproval;
use App\Models\User;
use App\Models\UtilityTransaction;
use App\Models\Vendor;
use App\Models\WalletTransaction;
use App\Models\WhitelistedIp;
use App\Models\WithdrawalRequest;
use Filament\Forms\Form;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Models\Activity;

class AuditTrailResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Security & Logs';

    protected static ?string $navigationLabel = 'Audit Trail';

    protected static ?string $modelLabel = 'Audit Log';

    protected static ?string $pluralModelLabel = 'Audit Trail';

    protected static ?string $slug = 'audit-trail';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date/Time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('log_name')
                    ->label('Log Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'auth' => 'info',
                        'security' => 'danger',
                        'audit' => 'warning',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('causer.full_name')
                    ->label('Admin')
                    ->searchable(['surname', 'name', 'other_names'])
                    ->placeholder('System/Unknown'),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Record Type')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_id')
                    ->label('ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Action')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('changes')
                    ->label('Change Details')
                    ->getStateUsing(function (Activity $record): string {
                        $old = $record->properties['old'] ?? [];
                        $new = $record->properties['attributes'] ?? [];

                        $user = auth()->user();
                        $isSuperAdmin = $user && $user->hasRole('super_admin');
                        $sensitive = ['bvn', 'membership_number', 'account_number', 'password'];

                        if (empty($old) && empty($new)) {
                            $props = $record->properties->except(['old', 'attributes'])->toArray();
                            if (!empty($props)) {
                                return collect($props)->map(function ($v, $k) use ($isSuperAdmin, $sensitive) {
                                    if (!$isSuperAdmin && in_array($k, $sensitive)) {
                                        $v = is_string($v) ? \Illuminate\Support\Str::mask($v, '*', 2, -2) : '*******';
                                    }
                                    return ucfirst(str_replace('_', ' ', $k)) . ": " . (is_array($v) ? json_encode($v) : $v);
                                })->implode(', ');
                            }
                            return 'No details';
                        }

                        if (empty($old) && !empty($new)) {
                            return 'Initial record created';
                        }

                        $changes = [];
                        foreach ($new as $key => $value) {
                            if (array_key_exists($key, $old) && $old[$key] != $value) {
                                $oldVal = is_array($old[$key]) ? json_encode($old[$key]) : $old[$key];
                                $newVal = is_array($value) ? json_encode($value) : $value;

                                // Mask sensitive data
                                if (!$isSuperAdmin && in_array($key, $sensitive)) {
                                    $oldVal = is_string($oldVal) ? \Illuminate\Support\Str::mask($oldVal, '*', 2, -2) : '*******';
                                    $newVal = is_string($newVal) ? \Illuminate\Support\Str::mask($newVal, '*', 2, -2) : '*******';
                                }

                                // Format currency for amount fields
                                if (str_contains($key, 'amount') || $key === 'balance') {
                                    $oldVal = '₦' . number_format((float)$oldVal, 2);
                                    $newVal = '₦' . number_format((float)$newVal, 2);
                                }

                                $changes[] = ucfirst(str_replace('_', ' ', $key)) . ": {$oldVal} → {$newVal}";
                            }
                        }

                        $otherProps = $record->properties->except(['old', 'attributes'])->toArray();
                        foreach ($otherProps as $key => $value) {
                            if (!$isSuperAdmin && in_array($key, $sensitive)) {
                                $value = is_string($value) ? \Illuminate\Support\Str::mask($value, '*', 2, -2) : '*******';
                            }
                            $changes[] = ucfirst(str_replace('_', ' ', $key)) . ": " . (is_array($value) ? json_encode($value) : $value);
                        }

                        return empty($changes) ? 'No significant changes' : implode(', ', $changes);
                    })
                    ->wrap()
                    ->tooltip(fn (Activity $record): string => $record->description === 'updated' ? 'Shows changes between old and new values' : ''),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->label('Log Type')
                    ->options([
                        'default' => 'Default',
                        'auth' => 'Auth Logs',
                        'security' => 'Suspicious Actions',
                        'audit' => 'Audit Logs',
                        'finance' => 'Finance Logs',
                        'chat' => 'Chat Logs',
                    ]),
                Tables\Filters\SelectFilter::make('causer_id')
                    ->label('Admin/User')
                    ->relationship('causer', 'surname')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('subject_type')
                    ->label('Model')
                    ->options([
                        Contribution::class => 'Contribution',
                        WalletTransaction::class => 'Wallet Transaction',
                        QardHasan::class => 'Qard Hasan',
                        QardHasanRepayment::class => 'Qard Hasan Repayment',
                        IncomeEntry::class => 'Income Entry',
                        ExpenseEntry::class => 'Expense Entry',
                        TakafulContribution::class => 'Takaful Contribution',
                        WithdrawalRequest::class => 'Withdrawal Request',
                        ProjectProfit::class => 'Project Profit',
                        ProjectProfitPayout::class => 'Project Profit Payout',
                        ProjectInvestment::class => 'Project Investment',
                        GoalBooking::class => 'Goal Booking',
                        SavingsGoal::class => 'Savings Goal',
                        StoreOrder::class => 'Store Order',
                        User::class => 'User / Member',
                        UtilityTransaction::class => 'Utility Transaction',
                        Branch::class => 'Branch',
                        Feature::class => 'Feature Toggle',
                        LedgerEntry::class => 'Ledger Entry',
                        LedgerJournal::class => 'Ledger Journal',
                        Product::class => 'Product',
                        Scheme::class => 'Scheme',
                        Setting::class => 'Setting',
                        ShariahAuditLog::class => 'Shariah Audit Log',
                        MemberApplication::class => 'Member Application',
                        TransactionApproval::class => 'Transaction Approval',
                        Vendor::class => 'Vendor',
                        WhitelistedIp::class => 'Whitelisted IP',
                    ]),
                Tables\Filters\SelectFilter::make('description')
                    ->label('Action')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Audit Details')
                    ->schema([
                        TextEntry::make('created_at')->label('Timestamp')->dateTime(),
                        TextEntry::make('causer.full_name')->label('Admin/User'),
                        TextEntry::make('subject_type')->label('Resource Type')->formatStateUsing(fn ($state) => class_basename($state)),
                        TextEntry::make('subject_id')->label('Resource ID'),
                        TextEntry::make('description')->label('Event'),
                    ])->columns(2),

                Section::make('Data Changes')
                    ->schema([
                        KeyValueEntry::make('properties.old')
                            ->label('Before')
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->state(function ($state) {
                                if ($state instanceof \Illuminate\Support\Collection) {
                                    $state = $state->toArray();
                                }

                                if (!is_array($state)) {
                                    return [];
                                }

                                $user = auth()->user();
                                $isSuperAdmin = $user && $user->hasRole('super_admin');

                                $sensitive = ['bvn', 'membership_number', 'account_number', 'password'];

                                foreach ($state as $key => $value) {
                                    if (!$isSuperAdmin && in_array($key, $sensitive)) {
                                        $state[$key] = is_string($value) ? \Illuminate\Support\Str::mask($value, '*', 2, -2) : '*******';
                                    } elseif (is_array($value) || is_object($value)) {
                                        $state[$key] = json_encode($value);
                                    }
                                }

                                return $state;
                            }),
                        KeyValueEntry::make('properties.attributes')
                            ->label('After')
                            ->keyLabel('Field')
                            ->valueLabel('Value')
                            ->state(function ($state) {
                                if ($state instanceof \Illuminate\Support\Collection) {
                                    $state = $state->toArray();
                                }

                                if (!is_array($state)) {
                                    return [];
                                }

                                $user = auth()->user();
                                $isSuperAdmin = $user && $user->hasRole('super_admin');

                                $sensitive = ['bvn', 'membership_number', 'account_number', 'password'];

                                foreach ($state as $key => $value) {
                                    if (!$isSuperAdmin && in_array($key, $sensitive)) {
                                        $state[$key] = is_string($value) ? \Illuminate\Support\Str::mask($value, '*', 2, -2) : '*******';
                                    } elseif (is_array($value) || is_object($value)) {
                                        $state[$key] = json_encode($value);
                                    }
                                }

                                return $state;
                            }),
                    ])->columns(2)->visible(fn (Activity $record) => !empty($record->properties['attributes'])),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $auditedModels = [
            Contribution::class,
            WalletTransaction::class,
            QardHasan::class,
            QardHasanRepayment::class,
            IncomeEntry::class,
            ExpenseEntry::class,
            TakafulContribution::class,
            WithdrawalRequest::class,
            ProjectProfit::class,
            ProjectProfitPayout::class,
            ProjectInvestment::class,
            GoalBooking::class,
            SavingsGoal::class,
            StoreOrder::class,
            User::class,
            UtilityTransaction::class,
            Setting::class,
            Scheme::class,
            Product::class,
            LedgerJournal::class,
            LedgerEntry::class,
            Branch::class,
            Feature::class,
            ShariahAuditLog::class,
        ];

        return parent::getEloquentQuery()
            ->where(function (Builder $query) use ($auditedModels) {
                $query->whereIn('subject_type', $auditedModels)
                    ->orWhereIn('log_name', ['auth', 'security', 'chat', 'finance']);
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditTrails::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        // Require specific permission or super_admin
        return auth()->user()->hasRole('super_admin') || auth()->user()->can('view_audit_trail');
    }
}
