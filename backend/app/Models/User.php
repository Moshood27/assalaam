<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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
        ];
    }

    public function walletTransactions()
    {
        return $this->hasMany(WalletTransaction::class);
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

    public function takafulPoolEntries()
    {
        return $this->hasMany(TakafulPoolEntry::class);
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
     * Compute Savings + Shares totals and 2x eligibility for this user.
     * Returns array: [savings, shares, base, eligibility]
     */
    public function savingsSharesEligibility(): array
    {
        // Scheme IDs for Savings and Shares
        $schemes = Scheme::whereIn('name', ['Savings', 'Shares'])->pluck('id', 'name');

        $savings = 0.0;
        $shares = 0.0;

        if (isset($schemes['Savings'])) {
            $savings = (float) $this->contributions()
                ->where('status', 'success')
                ->where('scheme_id', $schemes['Savings'])
                ->sum('amount');
        }
        if (isset($schemes['Shares'])) {
            $shares = (float) $this->contributions()
                ->where('status', 'success')
                ->where('scheme_id', $schemes['Shares'])
                ->sum('amount');
        }

        $base = round($savings + $shares, 2);
        $eligibility = round($base * 2, 2);

        return [
            'savings' => $savings,
            'shares' => $shares,
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

        return Carbon::parse($this->created_at)->diffInMonths(now());
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

        $adjusted = $isFirstLoan ? round($base * 0.05, 2) : round($base * 2, 2);

        return array_merge($calc, [
            'months_in_system' => $months,
            'is_first_loan' => $isFirstLoan,
            'eligibility_adjusted' => $adjusted,
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
            ->whereIn('status', ['active', 'pending'])
            ->exists();
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
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
}
