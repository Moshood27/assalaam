<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WithdrawalRequestResource\Pages;
use App\Models\WithdrawalRequest;
use App\Models\TransactionApproval;
use Illuminate\Database\Eloquent\Builder;
use App\Models\ShariahAuditLog as ShariahAudit;
use App\Models\User;
use App\Models\WalletTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class WithdrawalRequestResource extends Resource
{
    protected static ?string $model = WithdrawalRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Withdrawals';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Withdrawal Request')
                    ->schema([
                        Forms\Components\TextInput::make('user_id')->disabled()->dehydrated(false),
                        Forms\Components\TextInput::make('amount')->money('ngn')->disabled(),
                        Forms\Components\TextInput::make('reference')->disabled(),
                        Forms\Components\TextInput::make('status')->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Withdrawal Details')
                    ->schema([
                        TextEntry::make('user.name')->label('Member'),
                        TextEntry::make('amount')->money('ngn'),
                        TextEntry::make('reference')->label('Ref'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('bank_name')->label('Bank'),
                        TextEntry::make('account_number')->label('Acct #'),
                    ])->columns(2),
                InfoSection::make('Multi-Sig Approvals')
                    ->schema([
                        RepeatableEntry::make('transactionApprovals')
                            ->label('Approvals Log')
                            ->schema([
                                TextEntry::make('approver.name')->label('Approver'),
                                TextEntry::make('status')->badge()->color('success'),
                                TextEntry::make('responded_at')->label('Signed At')->dateTime(),
                            ])->columns(3)
                    ])->visible(fn (WithdrawalRequest $record) => $record->isHighValue())
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Requested')->since()->sortable(),
                TextColumn::make('user.name')->label('Member')->searchable(),
                TextColumn::make('user.membership_number')
                    ->label('Member #')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(function ($state) {
                        if (auth()->user()->hasRole('super_admin')) {
                            return $state;
                        }
                        return \Illuminate\Support\Str::mask($state, '*', 2, -2);
                    }),
                TextColumn::make('reference')->label('Ref')->copyable()->searchable(),
                TextColumn::make('reason')->label('Reason')->toggleable(),
                Tables\Columns\IconColumn::make('is_vendor_settlement')
                    ->label('Vendor')
                    ->boolean()
                    ->getStateUsing(fn ($record) => (bool)($record->meta['is_vendor_settlement'] ?? false))
                    ->toggleable(),
                TextColumn::make('amount')->money('ngn', true)->sortable(),
                TextColumn::make('bank_name')->label('Bank')->toggleable(),
                TextColumn::make('bank_code')->label('Code')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('account_number')
                    ->label('Acct #')
                    ->toggleable()
                    ->formatStateUsing(function ($state) {
                        if (auth()->user()->hasRole('super_admin')) {
                            return $state;
                        }
                        return \Illuminate\Support\Str::mask($state, '*', 2, -2);
                    }),
                TextColumn::make('account_name')->label('Acct Name')->toggleable(),
                TextColumn::make('status')->badge()->colors([
                    'warning' => ['pending'],
                    'success' => ['paid'],
                    'danger' => ['declined'],
                ])->sortable(),
                TextColumn::make('approvals_count')
                    ->label('Admin Approvals')
                    ->getStateUsing(function (WithdrawalRequest $record) {
                        if (!$record->isHighValue()) return 'N/A';
                        $count = $record->transactionApprovals()->where('status', 'approved')->count();
                        $required = config('cooperative.approvals.required_approvals_count', 2);
                        return "{$count} / {$required}";
                    })
                    ->badge()
                    ->color(fn (WithdrawalRequest $record) => $record->isHighValue() ? ($record->hasSufficientApprovals() ? 'success' : 'warning') : 'gray')
                    ->toggleable(),
                TextColumn::make('processed_at')->label('Processed')->dateTime()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'declined' => 'Declined',
                    ]),
                Tables\Filters\TernaryFilter::make('vendor_settlement')
                    ->label('Vendor Settlement')
                    ->placeholder('All Requests')
                    ->trueLabel('Only Vendor Settlements')
                    ->falseLabel('Exclude Vendor Settlements')
                    ->query(fn (Builder $query, $state) => match ($state) {
                        '1' => $query->where('meta->is_vendor_settlement', true),
                        '0' => $query->whereNull('meta->is_vendor_settlement'),
                        default => $query,
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->extraAttributes(['onclick' => 'window.print()']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()->hasRole('super_admin')), // Only visible to Super Admin
                Action::make('mark_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (WithdrawalRequest $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (WithdrawalRequest $record) {
                        // Enforce Multi-Sig (Four-Eyes) approval for high-value withdrawals
                        if ($record->isAwaitingApprovals()) {
                            $required = config('cooperative.approvals.required_approvals_count', 2);
                            $approved = $record->transactionApprovals()->where('status', 'approved')->count();

                            Notification::make()
                                ->title('Pending High-Value Approval')
                                ->body("This withdrawal requires {$required} admin approvals. Currently approved by: {$approved}.")
                                ->warning()
                                ->send();

                            return;
                        }

                        DB::transaction(function () use ($record) {
                            // Lock user and ensure sufficient balance
                            $user = User::where('id', $record->user_id)->lockForUpdate()->first();
                            if ((float)$user->balance < (float)$record->amount) {
                                throw new \RuntimeException('Insufficient member wallet balance to fulfill withdrawal.');
                            }
                            $user->decrement('balance', (float)$record->amount);

                            // Create wallet transaction (bank withdrawal debit)
                            WalletTransaction::create([
                                'user_id' => $user->id,
                                'type' => 'debit',
                                'amount' => (float)$record->amount,
                                'reference' => $record->reference,
                                'source' => 'bank_withdrawal',
                                'meta' => [
                                    'withdrawal_request_id' => $record->id,
                                    'bank_code' => $record->bank_code,
                                    'bank_name' => $record->bank_name,
                                    'account_number' => $record->account_number,
                                    'account_name' => $record->account_name,
                                ],
                            ]);

                            $record->status = 'paid';
                            $record->processed_at = now();
                            $record->save();
                            ShariahAudit::log(auth()->user(), 'approve_withdrawal', [
                                'withdrawal_request_id' => $record->id,
                                'user_id' => $record->user_id,
                                'amount' => $record->amount,
                                'reference' => $record->reference,
                            ]);
                        });

                        // Notify member via preferences
                        $user = $record->user?->fresh();
                        if ($user) {
                            $msg = 'Withdrawal paid: ₦'.number_format((float)$record->amount, 2).' to bank '.$record->bank_name.' (Acct '.$record->account_number.'). Ref: '.$record->reference;
                            $user->notifyMember('Withdrawal Paid', $msg, [
                                'type' => 'withdrawal_paid',
                                'amount' => (float)$record->amount,
                                'reference' => $record->reference,
                                'bank_name' => $record->bank_name,
                                'account_number' => $record->account_number,
                            ]);
                        }

                        Notification::make()
                            ->title('Withdrawal marked as paid')
                            ->success()
                            ->send();
                    }),
                Action::make('decline')
                    ->label('Decline')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (WithdrawalRequest $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason for decline')
                            ->rows(3)
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (WithdrawalRequest $record, array $data) {
                        $reason = trim((string)($data['reason'] ?? ''));
                        $record->status = 'declined';
                        $record->reason = $reason;
                        $record->processed_at = now();
                        $record->save();

                        ShariahAudit::log(auth()->user(), 'decline_withdrawal', [
                            'withdrawal_request_id' => $record->id,
                            'user_id' => $record->user_id,
                            'amount' => $record->amount,
                            'reason' => $reason,
                        ]);

                        // Notify member via preferences
                        $user = $record->user?->fresh();
                        if ($user) {
                            $msg = 'Withdrawal declined: ₦'.number_format((float)$record->amount, 2).'. Reason: '.$reason.' Ref: '.$record->reference;
                            $user->notifyMember('Withdrawal Declined', $msg, [
                                'type' => 'withdrawal_declined',
                                'amount' => (float)$record->amount,
                                'reference' => $record->reference,
                                'reason' => $reason,
                            ]);
                        }

                        Notification::make()
                            ->title('Withdrawal declined')
                            ->success()
                            ->send();
                    }),
                Action::make('multi_sig_approve')
                    ->label('Admin Multi-Sig Approval')
                    ->icon('heroicon-o-shield-check')
                    ->color('info')
                    ->visible(fn (WithdrawalRequest $record) => $record->status === 'pending' && $record->isHighValue() && ! $record->hasSufficientApprovals())
                    ->requiresConfirmation()
                    ->modalDescription('Confirm that you have reviewed this high-value withdrawal and approve it for processing.')
                    ->action(function (WithdrawalRequest $record) {
                        $user = auth()->user();

                        // Check if already approved by this user
                        if ($record->transactionApprovals()->where('approver_id', $user->id)->exists()) {
                            Notification::make()
                                ->title('Already Approved')
                                ->body('You have already signed off on this transaction.')
                                ->warning()
                                ->send();
                            return;
                        }

                        $record->transactionApprovals()->create([
                            'approver_id' => $user->id,
                            'status' => 'approved',
                            'responded_at' => now(),
                        ]);

                        ShariahAudit::log($user, 'multi_sig_approve_withdrawal', [
                            'withdrawal_id' => $record->id,
                            'user_id' => $record->user_id,
                            'amount' => $record->amount,
                        ]);

                        Notification::make()
                            ->title('Approval Recorded')
                            ->body('Your approval has been recorded for this high-value withdrawal.')
                            ->success()
                            ->send();

                        // Notify other admins if high value
                        if ($record->isHighValue() && $record->transactionApprovals()->count() === 1) {
                            $admins = User::where('is_admin', true)
                                ->where('id', '!=', auth()->id())
                                ->whereHas('roles', function($q) {
                                    $q->whereIn('name', ['super_admin', 'Chairman', 'Sharia Auditor']);
                                })
                                ->get();

                            foreach ($admins as $admin) {
                                $admin->notifyMember(
                                    'High-Value Withdrawal Approval Required',
                                    "A withdrawal of ₦" . number_format((float)$record->amount, 2) . " for {$record->user?->name} requires your multi-sig approval.",
                                    [
                                        'type' => 'high_value_withdrawal_approval',
                                        'withdrawal_id' => $record->id,
                                    ],
                                    ['push', 'database']
                                );
                            }
                        }

                        // Notify user if now fully approved
                        if ($record->hasSufficientApprovals()) {
                             Notification::make()
                                ->title('Fully Approved')
                                ->body('This withdrawal has now received sufficient admin approvals and can be processed.')
                                ->success()
                                ->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWithdrawalRequests::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_withdrawal_request');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_withdrawal_request');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update_withdrawal_request');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_withdrawal_request');
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
