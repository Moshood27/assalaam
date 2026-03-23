<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QardHasanResource\Pages;
use App\Mail\LoanDisbursedAdminNotification;
use App\Mail\LoanDisbursedUser;
use App\Models\QardHasan;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

class QardHasanResource extends Resource
{
    protected static ?string $model = QardHasan::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Loans';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Member')
                    ->options(User::query()->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $user = User::find($state);
                        if ($user) {
                            $adj = $user->adjustedLoanEligibility();
                            $set('principal_amount', $adj['eligibility_adjusted'] ?? 0);
                            $set('qard_id_string', 'QH-'.now()->format('Y').'-'.strtoupper(Str::random(6)));
                        }
                    }),
                Forms\Components\MultiSelect::make('guarantor_ids')
                    ->label('Guarantors (2–3, different branches, not in default)')
                    ->options(function (callable $get) {
                        $selectedUserId = $get('user_id');
                        return User::query()
                            ->when($selectedUserId, fn($q) => $q->where('id', '!=', $selectedUserId))
                            ->where('is_defaulter', false)
                            ->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required()
                    ->minItems(2)
                    ->maxItems(3)
                    ->helperText('Select at least two guarantors from different branches. Guarantors must not be in default.')
                    ->dehydrated(false),
                Forms\Components\TextInput::make('qard_id_string')
                    ->label('Loan ID')
                    ->maxLength(100)
                    ->disabled()
                    ->dehydrated(false)
                    ->hint('Auto-generated at create time'),
                Forms\Components\TextInput::make('principal_amount')
                    ->numeric()
                    ->prefix('₦')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Auto: 5% on first loan; 2 × thereafter (Savings + Shares)'),
                Forms\Components\TextInput::make('total_installments')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $principal = (float) ($get('principal_amount') ?? 0);
                        $ti = max((int) $state, 1);
                        $set('per_installment', $ti > 0 ? round($principal / $ti, 2) : 0);
                    }),
                Forms\Components\TextInput::make('per_installment')
                    ->numeric()
                    ->prefix('₦')
                    ->disabled()
                    ->dehydrated(false)
                    ->helperText('Auto-calculated from principal / installments'),
                Forms\Components\Select::make('interval')
                    ->options([
                        'daily' => 'Daily',
                        'weekly' => 'Weekly',
                        'monthly' => 'Monthly',
                    ])->required(),
                Forms\Components\TextInput::make('admin_fee_flat')
                    ->label('Admin Fee (Flat)')
                    ->numeric()
                    ->prefix('₦')
                    ->default(0),
                Forms\Components\TextInput::make('admin_fee_pct')
                    ->label('Admin Fee (%)')
                    ->numeric()
                    ->suffix('%')
                    ->default(0),
                Forms\Components\TextInput::make('paid_amount')
                    ->numeric()
                    ->prefix('₦')
                    ->default(0)
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])->required(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'guarantors']))
            ->columns([
                TextColumn::make('created_at')->label('Created')->since()->sortable(),
                TextColumn::make('user.name')->label('Member')->searchable(),
                TextColumn::make('guarantors_list')
                    ->label('Guarantors')
                    ->wrap()
                    ->getStateUsing(fn (QardHasan $record) => $record->guarantors?->pluck('name')->filter()->implode(', ') ?: '-'),
                TextColumn::make('qard_id_string')->label('Loan ID')->searchable(),
                TextColumn::make('principal_amount')->money('ngn', true)->label('Principal')->sortable(),
                TextColumn::make('paid_amount')->money('ngn', true)->label('Paid')->sortable(),
                TextColumn::make('status')->badge()->colors([
                    'warning' => ['pending'],
                    'success' => ['active', 'completed'],
                    'danger' => ['cancelled'],
                ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->extraAttributes(['onclick' => 'window.print()']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (QardHasan $record) => (float) $record->paid_amount <= 0
                        && ! $record->repayments()->exists())
                    ->requiresConfirmation()
                    ->successNotificationTitle('Loan deleted successfully'),
                Action::make('disburse')
                    ->label('Disburse')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (QardHasan $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (QardHasan $record) {
                        // Enforce 6-month membership before disbursement
                        if ($record->user && method_exists($record->user, 'monthsInSystem') && $record->user->monthsInSystem() < 6) {
                            Notification::make()
                                ->title('Cannot disburse')
                                ->body('Member must be in the system for at least 6 months before loan disbursement.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // Require all guarantors to accept before disbursement
                        $record->loadMissing('guarantors');
                        if (!method_exists($record, 'allGuarantorsAccepted') || !$record->allGuarantorsAccepted()) {
                            $pending = method_exists($record, 'pendingGuarantorCount') ? (int) $record->pendingGuarantorCount() : null;
                            $body = 'All selected guarantors must accept digitally before disbursement.';
                            if ($pending !== null) {
                                $body .= ' Pending: '.max($pending, 0);
                            }
                            Notification::make()
                                ->title('Cannot disburse')
                                ->body($body)
                                ->danger()
                                ->send();
                            return;
                        }

                        // Calculate credited amount
                        $principal = (float) $record->principal_amount;
                        $fee = (float) $record->admin_fee_flat + ($principal * ((float) $record->admin_fee_pct / 100));
                        $credit = max($principal - $fee, 0);

                        // Disburse within transaction
                        DB::transaction(function () use ($record, $credit) {
                            // Credit member wallet
                            $record->user->increment('balance', $credit);

                            // Mark loan as active (disbursed)
                            $record->update(['status' => 'active']);
                        });

                        // Refresh the record to get latest relations/status
                        $record->refresh();
                        $record->loadMissing('user');

                        // Send email to member if email exists
                        if (!empty($record->user?->email)) {
                            Mail::to($record->user->email)->send(new LoanDisbursedUser($record, $credit));
                        }

                        // Notify all admins
                        $adminEmails = User::query()
                            ->where('is_admin', true)
                            ->whereNotNull('email')
                            ->pluck('email')
                            ->all();
                        if (!empty($adminEmails)) {
                            Mail::to($adminEmails)->send(new LoanDisbursedAdminNotification($record, $credit));
                        }

                        // Best-effort notifications to member (SMS + Push)
                        try {
                            $fresh = $record->user?->fresh();
                            if ($fresh) {
                                $sms = app(\App\Services\SmsService::class);
                                $push = app(\App\Services\PushService::class);
                                $msg = 'Loan disbursed: ₦'.number_format($credit, 2).' to your wallet. Loan ID: '.($record->qard_id_string).'. Bal: ₦'.number_format((float) ($fresh->balance ?? 0), 2);
                                $sms->send($fresh->phone ?? null, $msg);
                                $push->send($fresh->device_token ?? null, 'Loan Disbursed', $msg, [
                                    'type' => 'loan_disbursed',
                                    'loan_id' => $record->id,
                                    'qard_id_string' => $record->qard_id_string,
                                    'credited_amount' => $credit,
                                    'balance' => (float) ($fresh->balance ?? 0),
                                ]);
                            }
                        } catch (\Throwable $e) {
                            // ignore notification errors
                        }

                        Notification::make()
                            ->title('Loan disbursed')
                            ->body('The loan has been disbursed and member wallet credited. Emails sent to member and admins (if configured).')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQardHasans::route('/'),
            'create' => Pages\CreateQardHasan::route('/create'),
            'edit' => Pages\EditQardHasan::route('/{record}/edit'),
        ];
    }
}
