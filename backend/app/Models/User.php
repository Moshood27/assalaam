<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Scheme;
use Carbon\Carbon;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable, TwoFactorAuthenticatable;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'device_token',
        'fcm_token',
        'password',
        'branch_id',
        'membership_number',
        'balance',
        'outstanding_fines',
        'gold_balance',
        'ordinary_savings',
        'special_savings_balance',
        'shares_capital',
        'building_balance',
        'development_fund_balance',
        'agm_balance',
        'loan_repayment_balance',
        'fine_balance',
        'welfare_balance',
        'lateness_balance',
        'stationery_balance',
        'loan_form_balance',
        'others_balance',
        'id_card_balance',
        'emergency_balance',
        'entrance_balance',
        'h_savings_balance',
        'investment_balance',
        'group_savings_balance',
        'created_at',
        'is_admin',
        'is_defaulter',
        'paystack_customer_code',
        'paystack_authorization_code',
        'dva_account_number',
        'dva_bank_name',
        'dva_account_name',
        'passport_path',
        'bvn',
        'bvn_verified_at',
        'dva_verification_meta',
        'bank_name',
        'bank_code',
        'account_number',
        'account_name',
        'autosave_enabled',
        'autosave_amount',
        'autosave_weekday',
        'autosave_last_run_at',
        'deceased_at',
        'major_loss_at',
        'takaful_exempt',
        'takaful_notify_contacts',
        'notify_email',
        'notify_sms',
        'notify_push',
        'attaqwa_score',
        'last_activity_at',
        'wellness_check_notified_at',
        'zakat_nisab_crossed_at',
        'zakat_last_paid_at',
        // Membership Enrolment Form Fields
        'surname',
        'other_names',
        'gender',
        'native_place',
        'dob',
        'marital_status',
        'occupation',
        'secondary_phone',
        'residential_address',
        'permanent_address',
        'nature_of_business',
        'business_address',
        'has_other_cooperatives',
        'other_cooperative_details',
        'nok_name',
        'nok_address',
        'nok_phone',
        'nok_relationship',
        'guarantor_name',
        'guarantor_address',
        'guarantor_phone',
        'guarantor_occupation',
        'guarantor_signature_path',
        'religious_society_name',
        'imam_name',
        'mosque_address',
        'imam_phone',
        'duration_of_jamma_membership',
        'imam_approval_status',
        'imam_approved_at',
        'imam_signature_path',
        'id_card_path',
        'proof_of_address_path',
        'spouse_father_name',
        'spouse_father_address',
        'spouse_father_business_address',
        'spouse_father_phone',
        'spouse_father_consent_signature_path',
        'admission_form_number',
        'admission_date',
        'admission_officer_name',
        'officer_recommendation',
        'approval_status',
        'president_signature_path',
        'president_signed_at',
        'secretary_general_signature_path',
        'secretary_general_signed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'transaction_pin_hash',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_defaulter' => 'boolean',
            'balance' => 'decimal:2',
            'outstanding_fines' => 'decimal:2',
            'ordinary_savings' => 'decimal:2',
            'special_savings_balance' => 'decimal:2',
            'shares_capital' => 'decimal:2',
            'building_balance' => 'decimal:2',
            'development_fund_balance' => 'decimal:2',
            'agm_balance' => 'decimal:2',
            'loan_repayment_balance' => 'decimal:2',
            'fine_balance' => 'decimal:2',
            'welfare_balance' => 'decimal:2',
            'lateness_balance' => 'decimal:2',
            'stationery_balance' => 'decimal:2',
            'loan_form_balance' => 'decimal:2',
            'others_balance' => 'decimal:2',
            'id_card_balance' => 'decimal:2',
            'emergency_balance' => 'decimal:2',
            'entrance_balance' => 'decimal:2',
            'h_savings_balance' => 'decimal:2',
            'investment_balance' => 'decimal:2',
            'group_savings_balance' => 'decimal:2',
            'bvn_verified_at' => 'datetime',
            'dva_verification_meta' => 'array',
            'pin_set_at' => 'datetime',
            'autosave_enabled' => 'boolean',
            'autosave_amount' => 'decimal:2',
            'autosave_weekday' => 'integer',
            'autosave_last_run_at' => 'datetime',
            'deceased_at' => 'datetime',
            'major_loss_at' => 'datetime',
            'takaful_exempt' => 'boolean',
            'takaful_notify_contacts' => 'boolean',
            'notify_email' => 'boolean',
            'notify_sms' => 'boolean',
            'notify_push' => 'boolean',
            'gold_balance' => 'decimal:6',
            'last_activity_at' => 'datetime',
            'wellness_check_notified_at' => 'datetime',
            'zakat_nisab_crossed_at' => 'datetime',
            'zakat_last_paid_at' => 'datetime',
            'dob' => 'date',
            'admission_date' => 'date',
            'has_other_cooperatives' => 'boolean',
            'imam_approval_status' => 'boolean',
            'imam_approved_at' => 'datetime',
            'president_signed_at' => 'datetime',
            'secretary_general_signed_at' => 'datetime',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->surname} {$this->name} {$this->other_names}");
    }

    public function badges()
    {
        return $this->hasMany(UserBadge::class);
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Send a member-facing notification through enabled channels.
     *
     * @param string $title
     * @param string $message
     * @param array $data Optional payload for push/database channels
     * @param array|null $channels Subset of ['database','mail','sms','push']; null = auto from preferences
     */
    public function notifyMember(string $title, string $message, array $data = [], ?array $channels = null): void
    {
        try {
            $resolved = $channels ?: array_values(array_filter([
                ($this->notify_email ? 'mail' : null),
                ($this->notify_sms ? 'sms' : null),
                ($this->notify_push ? 'push' : null),
                'database',
            ]));

            $useMail = in_array('mail', $resolved, true) && (bool) ($this->notify_email ?? true) && !empty($this->email);
            $useDb = in_array('database', $resolved, true);

            // Use Laravel notification for database/email
            try {
                $this->notify(new \App\Notifications\GeneralNotification($title, $message, $data, $useMail, $useDb));
            } catch (\Throwable $e) {
                // avoid breaking caller flow
            }

            // SMS
            if (in_array('sms', $resolved, true) && (bool) ($this->notify_sms ?? true) && !empty($this->phone)) {
                try {
                    app(\App\Services\SmsService::class)->send($this->phone, $message);
                } catch (\Throwable $e) {
                }
            }

            // Push
            if (in_array('push', $resolved, true) && (bool) ($this->notify_push ?? true)) {
                $token = $this->fcm_token ?: ($this->device_token ?? null);
                if (!empty($token)) {
                    try {
                        app(\App\Services\PushService::class)->send($token, $title, $message, $data ?? [], $this);
                    } catch (\Throwable $e) {
                    }
                }
            }
        } catch (\Throwable $e) {
            // swallow all errors
        }
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function contributions()
    {
        return $this->hasMany(Contribution::class);
    }

    public function qardHasans()
    {
        return $this->hasMany(QardHasan::class);
    }

    public function qardHasanRepayments()
    {
        return $this->hasManyThrough(QardHasanRepayment::class, QardHasan::class);
    }

    public function storeOrders()
    {
        return $this->hasMany(StoreOrder::class);
    }

    public function shariaDisputes()
    {
        return $this->hasMany(ShariaDispute::class);
    }

    public function vendor()
    {
        return $this->hasOne(Vendor::class, 'owner_user_id');
    }

    public function utilityTransactions()
    {
        return $this->hasMany(UtilityTransaction::class);
    }

    public function savingsGoals()
    {
        return $this->hasMany(SavingsGoal::class);
    }

    public function goalBookings()
    {
        return $this->hasMany(GoalBooking::class);
    }

    public function takafulContributions()
    {
        return $this->hasMany(TakafulContribution::class);
    }

    public function withdrawalRequests()
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    public function projectInvestments()
    {
        return $this->hasMany(ProjectInvestment::class);
    }

    public function projectProfitPayouts()
    {
        return $this->hasMany(ProjectProfitPayout::class);
    }

    public function beneficiaries()
    {
        return $this->hasMany(Beneficiary::class);
    }

    public function juniorAccounts()
    {
        return $this->hasMany(JuniorAccount::class);
    }

    public function takafulPoolEntries()
    {
        return $this->hasMany(TakafulPoolEntry::class);
    }

    public function savingsGroupMembers()
    {
        return $this->hasMany(SavingsGroupMember::class);
    }

    public function savingsGroups()
    {
        return $this->hasManyThrough(SavingsGroup::class, SavingsGroupMember::class, 'user_id', 'id', 'id', 'savings_group_id');
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function createdSavingsGroups()
    {
        return $this->hasMany(SavingsGroup::class, 'creator_id');
    }

    public function hasTransactionPin(): bool
    {
        return ! empty($this->transaction_pin_hash);
    }

    public function verifyTransactionPin(?string $pin): bool
    {
        if (! $pin || empty($this->transaction_pin_hash)) {
            return false;
        }

        return Hash::check($pin, $this->transaction_pin_hash);
    }

    /**
     * Check if user is eligible for Shura (Voting and Project Proposals).
     */
    public function isEligibleForShura(): bool
    {
        if ($this->is_defaulter) {
            return false;
        }

        if ($this->deceased_at) {
            return false;
        }

        return true;
    }

    /**
     * Compute Savings + Shares totals and 2x eligibility for this user.
     * Returns array: [savings, shares, base, eligibility]
     */
    public function savingsSharesEligibility(): array
    {
        // Scheme IDs for Savings and Shares
        $schemes = Scheme::whereIn('name', ['Savings', 'Shares', 'Special Savings', 'Ordinary Savings', 'Share Capital'])->pluck('id', 'name');

        $savings = 0.0;
        $shares = 0.0;
        $specialSavings = 0.0;

        if (isset($schemes['Savings'])) {
            $savings += (float) $this->contributions()
                ->where('status', 'success')
                ->where('scheme_id', $schemes['Savings'])
                ->sum('amount');
        }
        if (isset($schemes['Ordinary Savings'])) {
            $savings += (float) $this->contributions()
                ->where('status', 'success')
                ->where('scheme_id', $schemes['Ordinary Savings'])
                ->sum('amount');
        }
        if (isset($schemes['Shares'])) {
            $shares += (float) $this->contributions()
                ->where('status', 'success')
                ->where('scheme_id', $schemes['Shares'])
                ->sum('amount');
        }
        if (isset($schemes['Share Capital'])) {
            $shares += (float) $this->contributions()
                ->where('status', 'success')
                ->where('scheme_id', $schemes['Share Capital'])
                ->sum('amount');
        }
        if (isset($schemes['Special Savings'])) {
            $specialSavings = (float) $this->contributions()
                ->where('status', 'success')
                ->where('scheme_id', $schemes['Special Savings'])
                ->sum('amount');
        }

        $base = round($savings + $shares + $specialSavings, 2);
        $eligibility = round($base * 2, 2);

        return [
            'savings' => $savings,
            'shares' => $shares,
            'special_savings' => $specialSavings,
            'base' => $base,
            'eligibility' => $eligibility,
        ];
    }

    /**
     * Months since the user joined (based on created_at).
     */
    public function monthsInSystem(): int
    {
        if (! $this->created_at) {
            return 0;
        }

        return (int) Carbon::parse($this->created_at)->diffInMonths(now());
    }

    /**
     * Whether the user has any completed loan (status completed or paid >= principal).
     */
    public function hasCompletedLoan(): bool
    {
        return $this->qardHasans()
            ->where(function ($q) {
                $q->where('status', 'completed')
                    ->orWhereColumn('paid_amount', '>=', 'principal_amount');
            })
            ->exists();
    }

    /**
     * Policy-aware eligibility for principal amount.
     * - If first loan (no completed loans): 5% of (Savings + Shares)
     * - Otherwise: 2x (Savings + Shares)
     */
    public function adjustedLoanEligibility(): array
    {
        $calc = $this->savingsSharesEligibility();
        $base = (float) ($calc['base'] ?? 0);
        $months = $this->monthsInSystem();
        $hasCompleted = $this->hasCompletedLoan();
        $isFirstLoan = ! $hasCompleted;

        $baseAdjusted = $isFirstLoan ? round($base * 0.05, 2) : round($base * 2, 2);
        $scoreEnabled = (bool) \App\Models\Setting::get('loan_credit_score_enabled', config('cooperative.loan_credit_score_enabled', true));

        // Attaqwa Score Bonus: +1% for every 20 points, max +50%
        $scoreBonus = $scoreEnabled ? min(($this->attaqwa_score / 20) / 100, 0.50) : 0.0;
        $finalEligibility = round($baseAdjusted * (1 + $scoreBonus), 2);

        return array_merge($calc, [
            'months_in_system' => $months,
            'is_first_loan' => $isFirstLoan,
            'attaqwa_score' => $this->attaqwa_score,
            'score_bonus_pct' => round($scoreBonus * 100, 2),
            'eligibility_adjusted' => $finalEligibility,
            'score_enabled' => $scoreEnabled,
        ]);
    }

    /**
     * Check if user has an active store financing (Murabaha/Mudarabah) order.
     */
    public function hasActiveStoreFinancing(): bool
    {
        return StoreOrder::where('user_id', $this->id)
            ->whereIn('status', ['murabaha_pending', 'murabaha_active'])
            ->exists();
    }

    /**
     * Check if user has an active loan (QardHasan).
     */
    public function hasActiveLoan(): bool
    {
        return $this->qardHasans()
            ->whereIn('status', ['active', 'pending', 'defaulted'])
            ->exists();
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    /**
     * Generate a unique 6-digit membership number for a branch.
     */
    public static function generateMembershipNumber(int $branchId): string
    {
        // Try up to 20 attempts to avoid rare collisions
        for ($i = 0; $i < 20; $i++) {
            $num = (string) random_int(100000, 999999);
            $exists = self::where('branch_id', $branchId)->where('membership_number', $num)->exists();
            if (!$exists) return $num;
        }
        // Fallback to timestamp-based unique suffix
        return substr((string) (time() . random_int(10, 99)), -6);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin === true || $this->hasAnyRole(['super_admin', 'Branch Manager', 'Clerk']);
    }

    /**
     * Compute withdrawable breakdown for the wallet using tiered logic.
     * Debits consume restricted credits first, so available_for_withdrawal reflects
     * what can be cashed out to bank right now.
     */
    public function withdrawableBreakdown(): array
    {
        // Sum credits that are withdrawable (or older rows without the flag)
        $creditsWithdrawable = (float) WalletTransaction::where('user_id', $this->id)
            ->where('type', 'credit')
            ->where(function ($q) {
                $q->where('withdrawable', true)->orWhereNull('withdrawable');
            })
            ->sum('amount');

        // Sum credits explicitly restricted (withdrawable=false)
        $creditsRestricted = (float) WalletTransaction::where('user_id', $this->id)
            ->where('type', 'credit')
            ->where('withdrawable', false)
            ->sum('amount');

        // Sum all debits
        $totalDebits = (float) WalletTransaction::where('user_id', $this->id)
            ->where('type', 'debit')
            ->sum('amount');

        // Identify cash-out debits (bank withdrawals) that must reduce withdrawable immediately
        $cashoutDebits = (float) WalletTransaction::where('user_id', $this->id)
            ->where('type', 'debit')
            ->whereIn('source', ['bank_withdrawal'])
            ->sum('amount');

        $otherDebits = max(0.0, $totalDebits - $cashoutDebits);

        // For non-cashout spending, consume restricted first, then withdrawable
        $debitedFromWithdrawableOther = max(0.0, $otherDebits - $creditsRestricted);

        // Total debited from withdrawable = cash-out debits (always from withdrawable) + spillover from other debits
        $debitedFromWithdrawable = $cashoutDebits + $debitedFromWithdrawableOther;
        $remainingWithdrawable = max(0.0, $creditsWithdrawable - $debitedFromWithdrawable);

        $available = min((float) $this->balance, $remainingWithdrawable);

        return [
            'credits_withdrawable' => round($creditsWithdrawable, 2),
            'credits_restricted' => round($creditsRestricted, 2),
            'total_debits' => round($totalDebits, 2),
            'cashout_debits' => round($cashoutDebits, 2),
            'remaining_withdrawable' => round($remainingWithdrawable, 2),
            'available_for_withdrawal' => round($available, 2),
        ];
    }

    /**
     * Convenience helper: numeric available-for-withdrawal.
     */
    public function availableForWithdrawal(): float
    {
        $b = $this->withdrawableBreakdown();

        return (float) ($b['available_for_withdrawal'] ?? 0.0);
    }

    /**
     * Calculate total assets relevant for Zakat (Naira + Gold + Shares + Savings)
     */
    public function zakatBaseWealth(float $goldPrice): float
    {
        // Savings, Shares are usually stored in Contributions
        $schemes = Scheme::whereIn('name', ['Savings', 'Shares', 'Special Savings', 'Ordinary Savings', 'Share Capital'])->pluck('id');

        $savingsAndShares = (float) $this->contributions()
            ->where('status', 'success')
            ->whereIn('scheme_id', $schemes)
            ->sum('amount');

        $goldValue = round(($this->gold_balance ?? 0) * $goldPrice, 2);
        $walletBalance = (float) ($this->balance ?? 0);

        return (float) round($savingsAndShares + $goldValue + $walletBalance, 2);
    }
    public function syncSchemeBalance(string $schemeName): void
    {
        $columnMap = [
            'Savings' => 'ordinary_savings',
            'Ordinary Savings' => 'ordinary_savings',
            'Shares' => 'shares_capital',
            'Share Capital' => 'shares_capital',
            'Development' => 'development_fund_balance',
            'Building' => 'building_balance',
            'AGM' => 'agm_balance',
            'Loan Repayment' => 'loan_repayment_balance',
            'Fine' => 'fine_balance',
            'Welfare' => 'welfare_balance',
            'Lateness' => 'lateness_balance',
            'Stationery' => 'stationery_balance',
            'Loan Form' => 'loan_form_balance',
            'Others' => 'others_balance',
            'ID Card' => 'id_card_balance',
            'Emergency' => 'emergency_balance',
            'Entrance' => 'entrance_balance',
            'H Savings' => 'h_savings_balance',
            'Investment' => 'investment_balance',
            'Group Savings' => 'group_savings_balance',
            'Special Savings' => 'special_savings_balance',
            'Takaful' => 'takaful_balance',
            'Digital Gold' => 'gold_balance',
        ];

        if (isset($columnMap[$schemeName])) {
            $column = $columnMap[$schemeName];

            $actualTotal = (float) $this->contributions()
                ->whereHas('scheme', fn($q) => $q->where('name', $schemeName))
                ->where('status', 'success')
                ->sum('amount');

            $this->forceFill([$column => $actualTotal])->save();
        }
    }
}
