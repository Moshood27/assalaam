<?php

namespace App\Models;

use App\Models\Scheme;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Panel;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

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
        'dva_account_number',
        'dva_bank_name',
        'dva_account_name',
        'passport_path',
        'bvn',
        'bvn_verified_at',
        'dva_verification_meta',
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
        return $this->hasMany(\App\Models\QardHasan::class);
    }

    public function utilityTransactions()
    {
        return $this->hasMany(UtilityTransaction::class);
    }

    public function savingsGoals()
    {
        return $this->hasMany(\App\Models\SavingsGoal::class);
    }

    public function goalBookings()
    {
        return $this->hasMany(\App\Models\GoalBooking::class);
    }

    public function hasTransactionPin(): bool
    {
        return !empty($this->transaction_pin_hash);
    }

    public function verifyTransactionPin(?string $pin): bool
    {
        if (!$pin || empty($this->transaction_pin_hash)) {
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
        if (!$this->created_at) {
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
        $isFirstLoan = !$hasCompleted;

        $adjusted = $isFirstLoan ? round($base * 0.05, 2) : round($base * 2, 2);

        return array_merge($calc, [
            'months_in_system' => $months,
            'is_first_loan' => $isFirstLoan,
            'eligibility_adjusted' => $adjusted,
        ]);
    }
    public function canAccessPanel(Panel $panel): bool
    {
        // In production, you likely want to check an 'is_admin' column
        // For now, let's allow everyone to make sure it works:
        return true;

        // Later change to: return $this->is_admin === true;
    }
}
