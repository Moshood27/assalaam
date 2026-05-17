<?php

namespace App\Filament\Resources;

use App\Filament\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\QardHasanResource\Pages;
use App\Filament\Resources\QardHasanResource\RelationManagers;
use App\Mail\LoanAgreementRejectedUser;
use App\Mail\LoanAgreementVerifiedUser;
use App\Mail\LoanApprovedUser;
use App\Mail\LoanDisbursedAdminNotification;
use App\Mail\LoanDisbursedUser;
use App\Mail\LoanRejectedUser;
use App\Models\QardHasan;
use Illuminate\Database\Eloquent\Builder;
use App\Models\ShariahAuditLog as ShariahAudit;
use App\Models\User;
use App\Support\DurationHelper;
use App\Models\WalletTransaction;
use App\Notifications\LoanAgreementRejectedNotification;
use App\Notifications\LoanAgreementVerifiedNotification;
use App\Notifications\LoanApprovedNotification;
use App\Services\PayoutService;
use App\Services\PushService;
use App\Services\SmsService;
use Exception;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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
                    ->relationship('user', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable(['surname', 'name', 'other_names'])
                    ->preload()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        $user = User::find($state);
                        if ($user) {
                            $adj = $user->adjustedLoanEligibility();
                            $principal = $adj['eligibility_adjusted'] ?? 0;
                            $set('principal_amount', $principal);
                            $set('qard_id_string', 'QH-'.now()->format('Y').'-'.strtoupper(Str::random(6)));
                            $set('meeting_attendance_count', $user->meetingAttendanceCount());

                            // Also update per_installment if total_installments is already set
                            $ti = (int) $get('total_installments');
                            if ($ti > 0) {
                                $set('per_installment', round($principal / $ti, 2));
                            }
                        }
                    }),
                Forms\Components\MultiSelect::make('guarantor_ids')
                    ->label('Guarantors (2–3, not in default)')
                    ->options(function (callable $get) {
                        $selectedUserId = $get('user_id');

                        return User::query()
                            ->when($selectedUserId, fn ($q) => $q->where('id', '!=', $selectedUserId))
                            ->where('is_defaulter', false)
                            ->get()
                            ->mapWithKeys(fn ($u) => [$u->id => $u->full_name]);
                    })
                    ->searchable()
                    ->required()
                    ->minItems(2)
                    ->maxItems(3)
                    ->helperText('Select at least two guarantors. Guarantors must not be in default.')
                    ->dehydrated(false),
                Forms\Components\TextInput::make('qard_id_string')
                    ->label('Loan ID')
                    ->maxLength(100)
                    ->disabled()
                    ->dehydrated()
                    ->hint('Auto-generated at create time'),
                Forms\Components\TextInput::make('principal_amount')
                    ->numeric()
                    ->prefix('₦')
                    ->required()
                    ->disabled()
                    ->dehydrated()
                    ->helperText('Auto: 5% on first loan; 2 × thereafter (incl. migrated balances). Migrated members bypass the 5% cap.'),
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
                    ->required()
                    ->disabled()
                    ->dehydrated()
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
                    ->dehydrated(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'defaulted' => 'Defaulted',
                        'rejected' => 'Rejected',
                    ])
                    ->default('pending')
                    ->required(),
                Forms\Components\TextInput::make('meeting_attendance_count')
                    ->label('Attendance Count')
                    ->numeric()
                    ->helperText('Number of audited meetings attended at the time of application.')
                    ->default(0),
                Forms\Components\DateTimePicker::make('received_at')
                    ->label('Date Received')
                    ->helperText('When the member actually received the loan funds.'),
                Forms\Components\DateTimePicker::make('defaulted_at')
                    ->label('Default Date')
                    ->helperText('If this loan is in default, when did it happen?'),
                FileUpload::make('agreement_template')
                    ->label('Agreement Template')
                    ->directory('loan-templates')
                    ->visibility('public')
                    ->helperText('Upload the agreement document for the member to download and sign.'),
                FileUpload::make('signed_agreement')
                    ->label('Signed Agreement (Member)')
                    ->directory('loan-signed')
                    ->visibility('public')
                    ->disabled()
                    ->helperText('This will be uploaded by the member.'),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'guarantors', 'approvedBy']))
            ->columns([
                TextColumn::make('created_at')->label('Created')->since()->sortable(),
                TextColumn::make('user.full_name')
                    ->label('Member')
                    ->searchable(['surname', 'name', 'other_names']),
                TextColumn::make('user.membership_number')
                    ->label('Member #')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(function ($state) {
                        if (auth()->user()->hasRole('super_admin')) {
                            return $state;
                        }
                        return \Illuminate\Support\Str::mask($state, '*', 2, -2);
                    }),
                TextColumn::make('guarantors_list')
                    ->label('Guarantors')
                    ->wrap()
                    ->getStateUsing(fn (QardHasan $record) => $record->guarantors?->map(fn ($u) => $u->full_name)->filter()->implode(', ') ?: '-'),
                TextColumn::make('meeting_attendance_count')
                    ->label('Attendance (S/C)')
                    ->badge()
                    ->getStateUsing(fn (QardHasan $record) => "{$record->meeting_attendance_count} / " . $record->user->meetingAttendanceCount())
                    ->color(function ($record) {
                        $required = (int) \App\Models\Setting::get('required_loan_meetings', config('cooperative.attendance.required_loan_meetings', 8));
                        $current = $record->user->meetingAttendanceCount();
                        return $current >= $required ? 'success' : 'danger';
                    })
                    ->description(fn (QardHasan $record) => "Req: " . (int) \App\Models\Setting::get('required_loan_meetings', config('cooperative.attendance.required_loan_meetings', 8)))
                    ->toggleable(),
                TextColumn::make('qard_id_string')->label('Loan ID')->searchable(),
                TextColumn::make('principal_amount')->money('ngn', true)->label('Principal')->sortable(),
                TextColumn::make('credited_amount')
                    ->money('ngn', true)
                    ->label('Credited')
                    ->sortable(
                        query: function (Builder $query, string $direction): Builder {
                            $dir = strtolower($direction) === 'asc' ? 'asc' : 'desc';

                            // credited_amount = principal_amount - (admin_fee_flat + principal_amount * admin_fee_pct/100)
                            return $query->orderByRaw('('.
                                'COALESCE(principal_amount,0) - ('.
                                'COALESCE(admin_fee_flat,0) + COALESCE(principal_amount,0) * (COALESCE(admin_fee_pct,0) / 100)'.
                                ')) '.$dir);
                        }
                    ),
                TextColumn::make('paid_amount')->money('ngn', true)->label('Paid')->sortable(),
                TextColumn::make('approvedBy.full_name')->label('Approved By')->formatStateUsing(fn ($state) => $state ?: '-')->toggleable(),
                TextColumn::make('approved_at')->label('Approved At')->dateTime()->sortable()->toggleable(),
                TextColumn::make('received_at')->label('Received At')->dateTime()->sortable()->toggleable(),
                TextColumn::make('defaulted_at')->label('Defaulted At')->dateTime()->sortable()->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($record, $state) => (($record->defaulted_at && $record->defaulted_at->lte(now())) || $state === 'defaulted') ? 'DEFAULTED' : strtoupper($state))
                    ->color(fn ($record, $state) => (($record->defaulted_at && $record->defaulted_at->lte(now())) || $state === 'defaulted') ? 'danger' : match ($state) {
                        'pending' => 'warning',
                        'active', 'completed' => 'success',
                        'cancelled', 'rejected' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('overdue_amount')
                    ->label('Overdue')
                    ->money('ngn', true)
                    ->getStateUsing(fn (QardHasan $record) => $record->getOverdueAmount())
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                    ->toggleable(),
                TextColumn::make('period_of_default')
                    ->label('Period of Default')
                    ->getStateUsing(fn (QardHasan $record) => $record->period_of_default)
                    ->color(fn (QardHasan $record) => $record->getOverdueDays() > 7 ? 'danger' : ($record->getOverdueDays() > 0 ? 'warning' : 'gray'))
                    ->toggleable(),
                IconColumn::make('agreement_verified_at')
                    ->label('Agreement Verified')
                    ->boolean()
                    ->getStateUsing(fn (QardHasan $record) => ! empty($record->agreement_verified_at))
                    ->sortable(),
                TextColumn::make('approvals_count')
                    ->label('Admin Approvals')
                    ->getStateUsing(function (QardHasan $record) {
                        if (!$record->isHighValue()) return 'N/A';
                        $count = $record->transactionApprovals()->where('status', 'approved')->count();
                        $required = config('cooperative.approvals.required_approvals_count', 2);
                        return "{$count} / {$required}";
                    })
                    ->badge()
                    ->color(fn (QardHasan $record) => $record->isHighValue() ? ($record->hasSufficientApprovals() ? 'success' : 'warning') : 'gray')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'defaulted' => 'Defaulted',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\TernaryFilter::make('is_defaulted')
                    ->label('Default Status')
                    ->placeholder('All Loans')
                    ->trueLabel('Defaulted Only')
                    ->falseLabel('Non-Defaulted')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('defaulted_at')
                            ->where('defaulted_at', '<=', now()),
                        false: fn (Builder $query) => $query->where(fn ($q) => $q->whereNull('defaulted_at')->orWhere('defaulted_at', '>', now())),
                    ),
            ])
            ->headerActions([
                Action::make('print')
                    ->label('Print')
                    ->icon('heroicon-o-printer')
                    ->extraAttributes(['onclick' => 'window.print()']),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('recalculateBalance')
                    ->label('Recalculate Balance')
                    ->icon('heroicon-o-calculator')
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(function (QardHasan $record) {
                        $old = (float) $record->paid_amount;
                        $new = $record->recalculatePaidAmount();

                        if ($old !== $new) {
                            Notification::make()
                                ->success()
                                ->title('Balance recalculated')
                                ->body("Paid amount updated from ₦" . number_format($old, 2) . " to ₦" . number_format($new, 2))
                                ->send();
                        } else {
                            Notification::make()
                                ->info()
                                ->title('Balance is correct')
                                ->body("Paid amount ₦" . number_format($new, 2) . " matches repayments.")
                                ->send();
                        }
                    }),
                Action::make('recordRepayment')
                    ->label('Record Repayment')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->visible(fn (QardHasan $record) => in_array($record->status, ['active', 'defaulted']))
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Repayment Amount')
                            ->numeric()
                            ->required()
                            ->prefix('₦')
                            ->default(fn (QardHasan $record) => max(0, min($record->principal_amount - $record->paid_amount, $record->next_installment_amount)))
                            ->rules([
                                fn (QardHasan $record): \Closure => function (string $attribute, $value, \Closure $fail) use ($record) {
                                    $remaining = $record->principal_amount - $record->paid_amount;
                                    if ($value > $remaining + 0.01) {
                                        $fail("The amount cannot exceed the remaining balance of ₦" . number_format($remaining, 2));
                                    }
                                },
                            ]),
                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Payment Date')
                            ->default(now())
                            ->required(),
                        Forms\Components\TextInput::make('reference')
                            ->label('Reference')
                            ->default(fn () => 'MANUAL-PAY-' . strtoupper(Str::random(6)))
                            ->placeholder('e.g., Cash, Bank Transfer Ref')
                            ->required(),
                    ])
                    ->action(function (QardHasan $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $repayment = $record->repayments()->create([
                                'amount' => $data['amount'],
                                'paid_at' => $data['paid_at'],
                                'reference' => $data['reference'],
                                'status' => 'success',
                            ]);

                            ShariahAudit::log(auth()->user(), 'record_qard_hasan_repayment', [
                                'qard_id' => $record->id,
                                'repayment_id' => $repayment->id,
                                'amount' => $data['amount'],
                                'reference' => $data['reference'],
                            ]);
                        });

                        Notification::make()
                            ->success()
                            ->title('Repayment recorded successfully')
                            ->send();
                    }),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (QardHasan $record) => auth()->user()->hasRole('super_admin')
                        && (float) $record->paid_amount <= 0
                        && ! $record->repayments()->exists())
                    ->before(function (QardHasan $record, Tables\Actions\DeleteAction $action) {
                        if ($record->repayments()->exists() || (float) $record->paid_amount > 0) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot delete loan')
                                ->body('Repayments have already started for this loan.')
                                ->send();

                            $action->halt();
                        }
                    })
                    ->requiresConfirmation()
                    ->successNotificationTitle('Loan deleted successfully'),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('primary')
                    ->visible(fn (QardHasan $record) => $record->status === 'pending' && empty($record->approved_at) && auth()->user()->can('approve_loans'))
                    ->requiresConfirmation(fn (QardHasan $record) => $record->user->meetingAttendanceCount() < (int) \App\Models\Setting::get('required_loan_meetings', config('cooperative.attendance.required_loan_meetings', 8)))
                    ->modalHeading('Confirm Approval')
                    ->modalDescription(function (QardHasan $record) {
                        $required = (int) \App\Models\Setting::get('required_loan_meetings', config('cooperative.attendance.required_loan_meetings', 8));
                        $current = $record->user->meetingAttendanceCount();
                        if ($current < $required) {
                            return "WARNING: This member has only attended {$current} audited meetings (Required: {$required}). Approval is at the discretion of the Administrator or President. Are you sure you want to proceed?";
                        }
                        return "Are you sure you want to approve this loan? The member has attended {$current} audited meetings.";
                    })
                    ->form([
                        FileUpload::make('agreement_template')
                            ->label('Agreement Template')
                            ->directory('loan-templates')
                            ->visibility('public')
                            ->helperText('Upload the agreement document for the member to download and sign.')
                            ->default(fn (QardHasan $record) => $record->agreement_template),
                    ])
                    ->action(function (QardHasan $record, array $data) {
                        $record->update([
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'agreement_template' => $data['agreement_template'] ?? $record->agreement_template,
                        ]);

                        ShariahAudit::log(auth()->user(), 'approve_qard_hasan', [
                            'qard_id' => $record->id,
                            'qard_id_string' => $record->qard_id_string,
                            'member_id' => $record->user_id,
                            'principal' => $record->principal_amount,
                        ]);

                        // Send push notifications to authorized admins for high-value loans
                        if ($record->isHighValue()) {
                            $required = config('cooperative.approvals.required_approvals_count', 2);
                            $admins = $record->user->getAuthorizedAdmins()
                                ->where('id', '!=', auth()->id())
                                ->filter(function($admin) {
                                    return $admin->hasAnyRole(['super_admin', 'Chairman', 'Sharia Auditor']);
                                });

                            foreach ($admins as $admin) {
                                $admin->notifyMember(
                                    'High-Value Loan Approval Required',
                                    "A loan of ₦" . number_format($record->principal_amount, 2) . " for {$record->user?->name} requires your multi-sig approval.",
                                    [
                                        'type' => 'high_value_loan_approval',
                                        'loan_id' => $record->id,
                                        'qard_id_string' => $record->qard_id_string,
                                    ],
                                    ['push', 'database']
                                );
                            }
                        }

                        $msg = "Your loan request ({$record->qard_id_string}) was approved! Please download the agreement from your dashboard, sign, and upload it back for verification.";
                        // Notify member via preferences
                        if ($record->user) {
                            $record->user->notifyMember('Loan Approved', $msg, [
                                'type' => 'loan_approved',
                                'loan_id' => $record->id,
                                'qard_id_string' => $record->qard_id_string,
                            ]);
                        }

                        Notification::make()
                            ->title('Loan approved')
                            ->body('Loan has been approved. Member will be notified to sign the agreement.')
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (QardHasan $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason for rejection')
                            ->rows(3)
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (QardHasan $record, array $data) {
                        $reason = trim((string) ($data['reason'] ?? ''));
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $reason,
                        ]);

                        ShariahAudit::log(auth()->user(), 'reject_qard_hasan', [
                            'qard_id' => $record->id,
                            'qard_id_string' => $record->qard_id_string,
                            'member_id' => $record->user_id,
                            'reason' => $reason,
                        ]);

                        // Notify member via preferences
                        if ($record->user) {
                            $msg = 'Your loan request '.($record->qard_id_string).' was rejected. Reason: '.$reason;
                            $record->user->notifyMember('Loan Rejected', $msg, [
                                'type' => 'loan_rejected',
                                'loan_id' => $record->id,
                                'qard_id_string' => $record->qard_id_string,
                                'reason' => $reason,
                            ]);
                        }

                        Notification::make()
                            ->title('Loan rejected')
                            ->body('The loan has been rejected and the member has been notified by email (if available).')
                            ->success()
                            ->send();
                    }),
                Action::make('accept_guarantors')
                    ->label('Accept Guarantors')
                    ->icon('heroicon-o-user-plus')
                    ->color('warning')
                    ->visible(fn (QardHasan $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (QardHasan $record) {
                        // Admin override: mark all existing guarantor pivots as accepted
                        $record->loadMissing('guarantors');
                        if ($record->guarantors && $record->guarantors->isNotEmpty()) {
                            foreach ($record->guarantors as $g) {
                                $record->guarantors()->updateExistingPivot($g->id, [
                                    'status' => 'accepted',
                                    'responded_at' => now(),
                                ]);
                            }
                        }
                        Notification::make()
                            ->title('Guarantors accepted')
                            ->body('All guarantors have been marked as accepted. You may now disburse the loan.')
                            ->success()
                            ->send();
                    }),
                Action::make('verify_agreement')
                    ->label('Verify Agreement')
                    ->icon('heroicon-o-document-check')
                    ->color('success')
                    ->visible(fn (QardHasan $record) => $record->status === 'pending' && ! empty($record->signed_agreement) && empty($record->agreement_verified_at))
                    ->requiresConfirmation()
                    ->action(function (QardHasan $record) {
                        $record->update([
                            'agreement_verified_at' => now(),
                            'agreement_rejection_reason' => null,
                        ]);

                        ShariahAudit::log(auth()->user(), 'verify_qard_hasan_agreement', [
                            'qard_id' => $record->id,
                            'qard_id_string' => $record->qard_id_string,
                            'member_id' => $record->user_id,
                        ]);

                        $msg = "Your signed loan agreement for {$record->qard_id_string} has been verified! Your loan is now ready for final disbursement.";
                        // Notify member via preferences
                        if ($record->user) {
                            $record->user->notifyMember('Agreement Verified', $msg, [
                                'type' => 'loan_agreement_verified',
                                'loan_id' => $record->id,
                                'qard_id_string' => $record->qard_id_string,
                            ]);
                        }

                        Notification::make()
                            ->title('Agreement Verified')
                            ->body('The signed agreement has been verified and the member has been notified.')
                            ->success()
                            ->send();
                    }),
                Action::make('reject_agreement')
                    ->label('Reject Agreement')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (QardHasan $record) => $record->status === 'pending' && ! empty($record->signed_agreement) && empty($record->agreement_verified_at))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->placeholder('e.g. Blurry photo, missing signature on page 2, wrong file uploaded.'),
                    ])
                    ->action(function (QardHasan $record, array $data) {
                        $reason = $data['reason'];

                        // Clear the signed_agreement so user can re-upload, and save reason
                        $record->update([
                            'signed_agreement' => null,
                            'agreement_uploaded_at' => null,
                            'agreement_rejection_reason' => $reason,
                        ]);

                        ShariahAudit::log(auth()->user(), 'reject_qard_hasan_agreement', [
                            'qard_id' => $record->id,
                            'qard_id_string' => $record->qard_id_string,
                            'member_id' => $record->user_id,
                            'reason' => $reason,
                        ]);

                        $msg = "Your signed loan agreement for {$record->qard_id_string} was rejected: {$reason}. Please re-upload.";
                        // Notify member via preferences
                        if ($record->user) {
                            $record->user->notifyMember('Agreement Rejected', $msg, [
                                'type' => 'loan_agreement_rejected',
                                'loan_id' => $record->id,
                                'qard_id_string' => $record->qard_id_string,
                                'reason' => $reason,
                            ]);
                        }

                        Notification::make()
                            ->title('Agreement Rejected')
                            ->body('The signed agreement has been rejected and the member has been notified.')
                            ->danger()
                            ->send();
                    }),
                Action::make('view_signed')
                    ->label('View Signed Agreement')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->visible(fn (QardHasan $record) => ! empty($record->signed_agreement))
                    ->url(fn (QardHasan $record) => asset('storage/'.$record->signed_agreement), true),
                Action::make('view_template')
                    ->label('View Template')
                    ->icon('heroicon-o-document')
                    ->color('gray')
                    ->visible(fn (QardHasan $record) => ! empty($record->agreement_template))
                    ->url(fn (QardHasan $record) => asset('storage/'.$record->agreement_template), true),
                Action::make('multi_sig_approve')
                    ->label('Admin Multi-Sig Approval')
                    ->icon('heroicon-o-shield-check')
                    ->color('info')
                    ->visible(fn (QardHasan $record) => $record->status === 'pending' && $record->isHighValue() && ! $record->hasSufficientApprovals())
                    ->requiresConfirmation()
                    ->modalDescription('Confirm that you have reviewed this high-value loan and approve it for disbursement.')
                    ->action(function (QardHasan $record) {
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

                        ShariahAudit::log($user, 'multi_sig_approve_loan', [
                            'qard_id' => $record->id,
                            'qard_id_string' => $record->qard_id_string,
                            'amount' => $record->principal_amount,
                        ]);

                        Notification::make()
                            ->title('Approval Recorded')
                            ->body('Your approval has been recorded for this high-value loan.')
                            ->success()
                            ->send();

                        // Notify user if now fully approved
                        if ($record->hasSufficientApprovals()) {
                             Notification::make()
                                ->title('Fully Approved')
                                ->body('This loan has now received sufficient admin approvals and can be disbursed.')
                                ->success()
                                ->send();
                        }
                    }),
                Action::make('disburse')
                    ->label('Disburse')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->visible(fn (QardHasan $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Radio::make('disbursement_mode')
                            ->label('Disbursement Mode')
                            ->options([
                                'internal' => 'Internal Credit (Default) — spend inside app; withdrawals disabled',
                                'cash_out' => 'Cash-Out Enabled — allow withdrawal to bank',
                            ])
                            ->default('internal')
                            ->inline(false)
                            ->columns(1)
                            ->required(),
                        Forms\Components\Textarea::make('note')
                            ->label('Internal Note (optional)')
                            ->maxLength(200)
                            ->rows(2)
                            ->placeholder('e.g., Low liquidity this week — restrict to internal spend'),
                    ])
                    ->action(function (QardHasan $record, array $data) {
                        // Enforce 6-month membership before disbursement
                        if ($record->user && method_exists($record->user, 'monthsInSystem') && $record->user->monthsInSystem() < 6) {
                            Notification::make()
                                ->title('Cannot disburse')
                                ->body('Member must be in the system for at least 6 months before loan disbursement.')
                                ->danger()
                                ->send();

                            return;
                        }

                        // Enforce agreement verification before disbursement
                        if (empty($record->agreement_verified_at)) {
                            Notification::make()
                                ->title('Cannot disburse')
                                ->body('The agreement must be uploaded and verified before disbursement.')
                                ->danger()
                                ->send();

                            return;
                        }

                        // Enforce Multi-Sig (Four-Eyes) approval for high-value loans
                        if ($record->isAwaitingApprovals()) {
                            $required = config('cooperative.approvals.required_approvals_count', 2);
                            $approved = $record->transactionApprovals()->where('status', 'approved')->count();

                            Notification::make()
                                ->title('Pending High-Value Approval')
                                ->body("This loan requires {$required} admin approvals because it exceeds the threshold. Currently approved by: {$approved}. One of the approvers must be the Chairman or Sharia Auditor.")
                                ->warning()
                                ->send();

                            return;
                        }

                        // Require all guarantors to accept before disbursement
                        $record->loadMissing('guarantors');
                        if (! method_exists($record, 'allGuarantorsAccepted') || ! $record->allGuarantorsAccepted()) {
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

                        $mode = $data['disbursement_mode'] ?? 'internal';
                        $withdrawable = $mode === 'cash_out';

                        // If admin selected Cash-Out, ensure member has verified bank details
                        if ($withdrawable) {
                            $member = $record->user?->fresh();
                            $hasBank = $member && ! empty($member->bank_code) && ! empty($member->account_number) && ! empty($member->account_name);
                            if (! $hasBank) {
                                Notification::make()
                                    ->title('Bank details required')
                                    ->body('Member has no verified bank details. Only Internal Credit is allowed. Ask the member to add bank details in Profile > Bank Settings.')
                                    ->danger()
                                    ->send();

                                return;
                            }
                        }

                        $reference = 'QHDISB-'.now()->format('YmdHis').'-'.$record->user_id.'-'.strtoupper(Str::random(6));

                        // If cash-out, trigger real payout before ledger updates
                        if ($withdrawable) {
                            try {
                                PayoutService::sendToBank(
                                    (string) $record->user->account_number,
                                    (string) $record->user->bank_code,
                                    (float) $credit,
                                    (string) $reference
                                );
                            } catch (Exception $e) {
                                Notification::make()
                                    ->title('Payout Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();

                                return;
                            }
                        }

                        // Disburse within transaction
                        DB::transaction(function () use ($record, $credit, $withdrawable, $reference, $mode, $data) {
                            // Credit member wallet
                            $record->user->increment('balance', $credit);

                            // Record wallet transaction with loan_disbursement source and withdrawable flag
                            WalletTransaction::create([
                                'user_id' => $record->user_id,
                                'type' => 'credit',
                                'amount' => $credit,
                                'reference' => $reference,
                                'source' => 'loan_disbursement',
                                'withdrawable' => $withdrawable,
                                'meta' => [
                                    'qard_hasan_id' => $record->id,
                                    'qard_id_string' => $record->qard_id_string,
                                    'mode' => $mode,
                                    'note' => trim((string) ($data['note'] ?? '')) ?: null,
                                ],
                            ]);

                            // Mark loan as active (disbursed)
                            $record->update(['status' => 'active']);
                        });

                        ShariahAudit::log(auth()->user(), 'disburse_qard_hasan', [
                            'qard_id' => $record->id,
                            'qard_id_string' => $record->qard_id_string,
                            'member_id' => $record->user_id,
                            'credit' => $credit,
                            'mode' => $mode,
                            'reference' => $reference,
                        ]);

                        // Refresh the record to get latest relations/status
                        $record->refresh();
                        $record->loadMissing('user');

                        // Send email to member if email exists
                        if (! empty($record->user?->email)) {
                            Mail::to($record->user->email)->send(new LoanDisbursedUser($record, $credit));
                        }

                        // Notify relevant admins
                        $adminEmails = $record->user?->getAuthorizedAdmins()
                            ->whereNotNull('email')
                            ->pluck('email')
                            ->all();
                        if (! empty($adminEmails)) {
                            Mail::to($adminEmails)->send(new LoanDisbursedAdminNotification($record, $credit));
                        }

                        // Notify member via preferences
                        if ($record->user) {
                            $modeText = $withdrawable ? 'Cash-out enabled' : 'Internal use only';
                            $msg = 'Loan disbursed: ₦'.number_format($credit, 2).' to your wallet ('.$modeText.'). Loan ID: '.($record->qard_id_string).'. Bal: ₦'.number_format((float) ($record->user->balance ?? 0), 2);
                            $record->user->notifyMember('Loan Disbursed', $msg, [
                                'type' => 'loan_disbursed',
                                'loan_id' => $record->id,
                                'qard_id_string' => $record->qard_id_string,
                                'credited_amount' => $credit,
                                'balance' => (float) ($record->user->balance ?? 0),
                                'withdrawable' => $withdrawable,
                            ]);
                        }

                        Notification::make()
                            ->title('Loan disbursed')
                            ->body('The loan has been disbursed and member wallet credited. Emails sent to member and admins (if configured).')
                            ->success()
                            ->send();
                    }),
                Action::make('toggle_cash_out')
                    ->label('Toggle Cash-Out Permission')
                    ->icon('heroicon-o-adjustments-vertical')
                    ->color('warning')
                    ->visible(fn (QardHasan $record) => $record->status === 'active')
                    ->form([
                        Forms\Components\Toggle::make('enable_cash_out')
                            ->label('Allow Withdrawal to Bank for this Loan Disbursement')
                            ->default(false),
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason (optional)')
                            ->maxLength(200)
                            ->rows(2),
                    ])
                    ->action(function (QardHasan $record, array $data) {
                        $enable = (bool) ($data['enable_cash_out'] ?? false);
                        // Find the wallet transaction for this loan disbursement
                        $txn = WalletTransaction::query()
                            ->where('user_id', $record->user_id)
                            ->where('source', 'loan_disbursement')
                            ->where('meta->qard_hasan_id', $record->id)
                            ->orderByDesc('id')
                            ->first();
                        if (! $txn) {
                            Notification::make()
                                ->title('No disbursement record found')
                                ->body('Could not find the wallet transaction for this loan disbursement.')
                                ->danger()
                                ->send();

                            return;
                        }
                        $txn->withdrawable = $enable;
                        $meta = (array) ($txn->meta ?? []);
                        $meta['admin_toggle_reason'] = trim((string) ($data['reason'] ?? '')) ?: null;
                        $meta['admin_toggled_at'] = now()->toISOString();
                        $meta['admin_toggled_by'] = auth()->id();
                        $txn->meta = $meta;
                        $txn->save();

                        // Notify member via preferences
                        if ($record->user) {
                            $msg = $enable
                                ? ('Cash-out ENABLED for loan '.($record->qard_id_string).'. You can now withdraw the funds to your bank.')
                                : ('Cash-out DISABLED for loan '.($record->qard_id_string).'. Withdrawal to bank is restricted; you can still spend inside the app.');

                            $record->user->notifyMember('Cash-Out Updated', $msg, [
                                'type' => 'loan_cashout_updated',
                                'loan_id' => $record->id,
                                'qard_id_string' => $record->qard_id_string,
                                'enabled' => $enable,
                            ]);
                        }

                        Notification::make()
                            ->title('Cash-out permission updated')
                            ->body('Withdrawable flag for the disbursement has been '.($enable ? 'enabled' : 'disabled').'.')
                            ->success()
                            ->send();
                    }),
                Action::make('mark_defaulted')
                    ->label('Mark as Defaulted')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->visible(fn (QardHasan $record) => in_array($record->status, ['active', 'pending']) && empty($record->defaulted_at))
                    ->form([
                        Forms\Components\DateTimePicker::make('defaulted_at')
                            ->label('Default Date')
                            ->default(now())
                            ->required(),
                        Forms\Components\Textarea::make('reason')
                            ->label('Notes')
                            ->placeholder('Why is this loan being marked as defaulted?'),
                    ])
                    ->action(function (QardHasan $record, array $data) {
                        $record->update([
                            'defaulted_at' => $data['defaulted_at'],
                        ]);

                        Notification::make()
                            ->title('Loan marked as defaulted')
                            ->body('The loan has been updated. Member status has been synced.')
                            ->success()
                            ->send();
                    }),
                Action::make('clear_default')
                    ->label('Clear Default')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (QardHasan $record) => ! empty($record->defaulted_at))
                    ->requiresConfirmation()
                    ->action(function (QardHasan $record) {
                        $record->update([
                            'defaulted_at' => null,
                        ]);

                        Notification::make()
                            ->title('Default status cleared')
                            ->body('The loan is no longer in default. Member status has been synced.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Loan Details')
                    ->schema([
                        TextEntry::make('qard_id_string')->label('Loan ID'),
                        TextEntry::make('user.full_name')->label('Member'),
                        TextEntry::make('principal_amount')->money('ngn'),
                        TextEntry::make('meeting_attendance_count')
                            ->label('Meeting Attendance (Snapshot / Current Audited)')
                            ->badge()
                            ->getStateUsing(fn (QardHasan $record) => "{$record->meeting_attendance_count} / " . $record->user->meetingAttendanceCount())
                            ->color(function ($record) {
                                $required = (int) \App\Models\Setting::get('required_loan_meetings', config('cooperative.attendance.required_loan_meetings', 8));
                                $current = $record->user->meetingAttendanceCount();
                                return $current >= $required ? 'success' : 'danger';
                            })
                            ->hint(fn() => "Required: " . (int) \App\Models\Setting::get('required_loan_meetings', config('cooperative.attendance.required_loan_meetings', 8))),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn ($record, $state) => (($record->defaulted_at && $record->defaulted_at->lte(now())) || $state === 'defaulted') ? 'DEFAULTED' : strtoupper($state))
                            ->color(fn ($record, $state) => (($record->defaulted_at && $record->defaulted_at->lte(now())) || $state === 'defaulted') ? 'danger' : match ($state) {
                                'pending' => 'warning',
                                'active', 'completed' => 'success',
                                'cancelled', 'rejected' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('received_at')->label('Date Received')->dateTime(),
                        TextEntry::make('defaulted_at')->label('Date Defaulted')->dateTime()->placeholder('Not in default'),
                        TextEntry::make('overdue_amount')
                            ->label('Current Overdue Amount')
                            ->money('ngn')
                            ->getStateUsing(fn (QardHasan $record) => $record->getOverdueAmount())
                            ->color(fn ($state) => $state > 0 ? 'danger' : null),
                        TextEntry::make('overdue_days')
                            ->label('Days Overdue')
                            ->getStateUsing(fn (QardHasan $record) => DurationHelper::format($record->getOverdueDays()))
                            ->color(fn ($record) => $record->getOverdueDays() > 0 ? 'danger' : null),
                    ])->columns(2),
                InfoSection::make('Multi-Sig Approvals')
                    ->schema([
                        RepeatableEntry::make('transactionApprovals')
                            ->label('Approvals Log')
                            ->schema([
                                TextEntry::make('approver.full_name')->label('Approver'),
                                TextEntry::make('status')->badge()->color('success'),
                                TextEntry::make('responded_at')->label('Signed At')->dateTime(),
                            ])->columns(3)
                    ])->visible(fn (QardHasan $record) => $record->isHighValue()),
                InfoSection::make('Loan Documents & Verification')
                    ->schema([
                        TextEntry::make('agreement_template')
                            ->label('Template File')
                            ->formatStateUsing(fn ($state) => $state ? 'Custom Uploaded' : 'System Generated')
                            ->badge()
                            ->color(fn ($state) => $state ? 'info' : 'gray')
                            ->hintAction(
                                InfolistAction::make('download_template')
                                    ->label('Download')
                                    ->icon('heroicon-o-arrow-down-tray')
                                    ->url(fn ($record) => $record->agreement_template
                                        ? asset('storage/'.$record->agreement_template)
                                        : route('download-loan-agreement', $record->id))
                                    ->openUrlInNewTab()
                            ),
                        TextEntry::make('signed_agreement')
                            ->label('Member Signed Copy')
                            ->formatStateUsing(fn ($state) => $state ? 'Uploaded' : 'Pending')
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'warning')
                            ->hintAction(
                                InfolistAction::make('view_signed')
                                    ->label('View')
                                    ->icon('heroicon-o-eye')
                                    ->url(fn ($record) => $record->signed_agreement ? asset('storage/'.$record->signed_agreement) : null)
                                    ->visible(fn ($record) => ! empty($record->signed_agreement))
                                    ->openUrlInNewTab()
                            ),
                        TextEntry::make('agreement_uploaded_at')
                            ->label('Member Uploaded')
                            ->dateTime(),
                        TextEntry::make('agreement_verified_at')
                            ->label('Verified By Admin')
                            ->dateTime()
                            ->placeholder('Not yet verified')
                            ->color('success'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RepaymentsRelationManager::class,
            ActivitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQardHasans::route('/'),
            'create' => Pages\CreateQardHasan::route('/create'),
            'edit' => Pages\EditQardHasan::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view_any_qard_hasan');
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create_qard_hasan');
    }

    public static function canEdit($record): bool
    {
        return auth()->user()->can('update_qard_hasan');
    }

    public static function canDelete($record): bool
    {
        return auth()->user()->can('delete_qard_hasan');
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
