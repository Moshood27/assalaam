<?php

namespace App\Models;

use App\Support\DurationHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\TransactionApproval;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\LoanPenalty;
use App\Models\Setting;
use Carbon\Carbon;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class QardHasan extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * Compute the next due date for this loan based on interval, total installments, and progress.
     * This is a computed helper and does not persist any schedule.
     */
    public function getNextDueAtAttribute(): ?string
    {
        // If not active (not yet disbursed) or already completed, next due is not applicable
        if (! in_array($this->status, ['active'], true)) {
            return null;
        }
        if ((float) $this->principal_amount <= 0 || (int) $this->total_installments <= 0) {
            return null;
        }

        $per = (float) $this->per_installment;
        if ($per <= 0) {
            // Derive per installment if not stored or invalid
            $per = round(((float) $this->principal_amount) / max((int) $this->total_installments, 1), 2);
        }

        $paid = (float) $this->paid_amount;
        $installmentsPaid = (int) floor($per > 0 ? ($paid / $per) : 0);
        if ($installmentsPaid >= (int) $this->total_installments) {
            return null; // fully paid
        }

        $schedule = $this->generateInstallmentSchedule();
        if (empty($schedule)) {
            return null;
        }

        // Next installment index is installmentsPaid (0-based)
        $idx = max(0, min($installmentsPaid, count($schedule) - 1));
        $next = $schedule[$idx]['due_at'] ?? null;

        return $next instanceof Carbon ? $next->toISOString() : (is_string($next) ? $next : null);
    }

    /**
     * Generate a simple installment schedule as an array of [index, due_at (Carbon), amount].
     * Start date: approved_at when present, otherwise created_at; first installment is one interval after start.
     */
    public function generateInstallmentSchedule(?Carbon $startAt = null): array
    {
        $total = (int) $this->total_installments;
        if ($total <= 0) {
            return [];
        }

        $per = (float) $this->per_installment;
        if ($per <= 0) {
            $per = round(((float) $this->principal_amount) / max($total, 1), 2);
        }

        $interval = strtolower((string) $this->interval ?: 'monthly');
        $start = $startAt ?: ($this->received_at ?: ($this->approved_at ?: ($this->created_at ?: now())));
        $start = ($start instanceof Carbon) ? $start->copy() : Carbon::parse((string) $start);

        $items = [];
        $cursor = $start->copy();
        for ($i = 0; $i < $total; $i++) {
            $cursor = $this->addInterval($cursor, $interval); // move by one interval each time
            $items[] = [
                'index' => $i + 1,
                'due_at' => $cursor->copy(),
                'due_date' => $cursor->toDateString(),
                'amount' => $per,
            ];
        }

        return $items;
    }

    /**
     * Add interval (daily|weekly|monthly) to a Carbon date and return a cloned instance.
     */
    public function addInterval(Carbon $date, string $interval): Carbon
    {
        $d = $date->copy();
        $key = strtolower(trim($interval));

        return match ($key) {
            'daily' => $d->addDay(),
            'weekly' => $d->addWeek(),
            default => $d->addMonth(), // monthly fallback
        };
    }

    protected static function booted(): void
    {
        static::updating(function (QardHasan $loan) {
            // If just defaulted
            if ($loan->isDirty('defaulted_at') && !$loan->getOriginal('defaulted_at') && $loan->defaulted_at) {
                $loan->startPenaltyRecord();
            }
            // If default cleared
            if ($loan->isDirty('defaulted_at') && $loan->getOriginal('defaulted_at') && !$loan->defaulted_at) {
                $loan->completePenaltyRecord();
            }
        });

        static::saving(function (QardHasan $loan) {
            // Auto-complete if fully paid
            if ($loan->paid_amount >= $loan->principal_amount && $loan->principal_amount > 0) {
                if (!in_array($loan->status, ['cancelled', 'rejected'])) {
                    $loan->status = 'completed';
                }
                if ($loan->defaulted_at) {
                    $loan->defaulted_at = null;
                }
            }

            if ($loan->status !== 'completed' && $loan->defaulted_at && $loan->defaulted_at->year > 1970 && $loan->defaulted_at->lte(now())) {
                if (in_array($loan->status, ['active', 'pending'])) {
                    $loan->status = 'defaulted';
                }
            } elseif ($loan->status === 'defaulted') {
                if (!$loan->defaulted_at || $loan->defaulted_at->gt(now())) {
                    $loan->status = 'active';
                }
            }
        });

        static::updated(function (QardHasan $loan) {
            if ($loan->wasChanged(['defaulted_at', 'status', 'paid_amount'])) {
                $loan->syncUserDefaulterStatus();
            }

            // Record Loan Disbursement when status becomes 'active'
            if ($loan->status === 'active' && $loan->wasChanged('status') && !$loan->ledger_journal_id) {
                try {
                    $journal = app(\App\Services\LedgerService::class)->recordLoanDisbursement($loan);
                    $loan->updateQuietly(['ledger_journal_id' => $journal->id]);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to record loan disbursement in ledger: " . $e->getMessage());
                }
            }
        });

        static::created(function (QardHasan $loan) {
            if ($loan->defaulted_at) {
                $loan->syncUserDefaulterStatus();
            }

            // Record Loan Disbursement if already active (typical for migration)
            if ($loan->status === 'active' && !$loan->ledger_journal_id) {
                try {
                    $journal = app(\App\Services\LedgerService::class)->recordLoanDisbursement($loan);
                    $loan->updateQuietly(['ledger_journal_id' => $journal->id]);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to record loan disbursement in ledger: " . $e->getMessage());
                }
            }
        });

        static::deleting(function (QardHasan $loan) {
            // Prevent deletion if any repayment exists or any amount has been paid
            if ($loan->repayments()->exists() || (float) $loan->paid_amount > 0) {
                return false;
            }
            // Guarantor pivot will be cascaded by FK; no manual detach required
        });

        static::deleted(function (QardHasan $loan) {
            $loan->syncUserDefaulterStatus();
        });
    }

    protected $fillable = [
        'user_id',
        'qard_id_string',
        'principal_amount',
        'total_installments',
        'per_installment',
        'interval',
        'admin_fee_flat',
        'admin_fee_pct',
        'paid_amount',
        'status',
        'meeting_attendance_count',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'received_at',
        'defaulted_at',
        'agreement_template',
        'signed_agreement',
        'agreement_uploaded_at',
        'agreement_verified_at',
        'agreement_rejection_reason',
        'ledger_journal_id',
    ];

    protected $casts = [
        'principal_amount' => 'float',
        'per_installment' => 'float',
        'admin_fee_flat' => 'float',
        'admin_fee_pct' => 'float',
        'paid_amount' => 'float',
        'approved_at' => 'datetime',
        'received_at' => 'datetime',
        'defaulted_at' => 'datetime',
        'agreement_uploaded_at' => 'datetime',
        'agreement_verified_at' => 'datetime',
    ];

    protected $appends = [
        'remaining_principal',
        'progress_pct',
        'is_completed',
        'credited_amount',
        'next_due_at',
        'next_installment_amount',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function repayments()
    {
        return $this->hasMany(QardHasanRepayment::class)->orderByDesc('paid_at');
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function guarantors()
    {
        return $this->belongsToMany(User::class, 'qard_hasan_guarantors', 'qard_hasan_id', 'guarantor_id')
            ->withTimestamps()
            ->withPivot(['status', 'token', 'responded_at', 'nudge_count', 'last_nudged_at', 'escalated_at']);
    }

    public function allGuarantorsAccepted(): bool
    {
        $g = $this->guarantors;
        if (! $g || $g->isEmpty()) {
            return false;
        }

        // Require at least 2 guarantors and all must be accepted
        return $g->count() >= 2 && $g->every(fn ($u) => ($u->pivot?->status) === 'accepted');
    }

    public function pendingGuarantorCount(): int
    {
        return (int) ($this->guarantors?->filter(fn ($u) => ($u->pivot?->status) === 'pending')->count() ?? 0);
    }

    /**
     * Sync the user's is_defaulter status based on all their loans.
     */
    public function syncUserDefaulterStatus(): void
    {
        if ($this->user) {
            $this->user->syncLoanDefaulterStatus();
        }
    }

    /**
     * Calculate the expected amount to be paid by a certain date.
     */
    public function getExpectedAmountToDate(?Carbon $asAt = null): float
    {
        $asAt = $asAt ?: now();
        if ($this->status === 'pending') return 0.0;

        $per = (float) $this->per_installment;
        if ($per <= 0) {
            $per = round(((float)$this->principal_amount) / max((int)$this->total_installments, 1), 2);
        }
        if ($per <= 0) return 0.0;

        $schedule = $this->generateInstallmentSchedule();
        if (empty($schedule)) return 0.0;

        $dueCount = 0;
        foreach ($schedule as $item) {
            $dueAt = $item['due_at'] instanceof Carbon ? $item['due_at'] : Carbon::parse((string) $item['due_at']);
            if ($dueAt->lessThanOrEqualTo($asAt)) {
                $dueCount++;
            } else {
                break;
            }
        }

        return round(min($dueCount * $per, (float) $this->principal_amount), 2);
    }

    /**
     * Calculate the overdue amount for this loan.
     */
    public function getOverdueAmount(?Carbon $asAt = null): float
    {
        $asAt = $asAt ?: now();

        if ($this->status !== 'active' && $this->status !== 'defaulted') return 0.0;

        // If the loan is marked as defaulted, the full remaining balance is considered overdue (acceleration)
        if ($this->defaulted_at && $this->defaulted_at->year > 1970) {
            if ($this->defaulted_at->lessThanOrEqualTo($asAt)) {
                return (float) $this->remaining_principal;
            }

            // If the defaulted date is in the future, the amount defaulted should be 0.00
            return 0.0;
        }

        $expectedPaid = $this->getExpectedAmountToDate($asAt);
        $alreadyPaid = (float) $this->paid_amount;
        $overdue = round(max(0.0, $expectedPaid - $alreadyPaid), 2);

        $remaining = max(0.0, (float) $this->principal_amount - $alreadyPaid);
        if ($overdue > $remaining) $overdue = $remaining;

        return $overdue;
    }

    /**
     * Get the number of days the loan is overdue.
     */
    public function getOverdueDays(?Carbon $asAt = null): int
    {
        $asAt = $asAt ?: now();

        // If explicitly marked as defaulted, calculate from defaulted_at
        if ($this->defaulted_at && $this->defaulted_at->year > 1970) {
            if ($this->defaulted_at->lessThanOrEqualTo($asAt)) {
                return (int) abs($asAt->diffInDays($this->defaulted_at));
            }
        }

        if ($this->getOverdueAmount($asAt) <= 0) return 0;

        $schedule = $this->generateInstallmentSchedule();
        $per = (float) $this->per_installment;
        if ($per <= 0) {
            $per = round(((float)$this->principal_amount) / max((int)$this->total_installments, 1), 2);
        }

        $paid = (float) $this->paid_amount;
        $installmentsPaid = (int) floor($per > 0 ? ($paid / $per) : 0);

        // The first installment that is NOT paid but its due_at is in the past
        if (isset($schedule[$installmentsPaid])) {
            $dueAt = $schedule[$installmentsPaid]['due_at'];
            if ($dueAt->lessThan($asAt)) {
                return (int) abs($asAt->diffInDays($dueAt));
            }
        }

        return 0;
    }

    // Accessors for transparency
    public function getRemainingPrincipalAttribute(): float
    {
        $remaining = (float) $this->principal_amount - (float) $this->paid_amount;

        return $remaining > 0 ? round($remaining, 2) : 0.0;
    }

    public function getProgressPctAttribute(): float
    {
        if ((float) $this->principal_amount <= 0) {
            return 0.0;
        }
        $pct = ((float) $this->paid_amount / (float) $this->principal_amount) * 100;
        if ($pct > 100) {
            $pct = 100;
        }
        if ($pct < 0) {
            $pct = 0;
        }

        return round($pct, 2);
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed' || $this->remaining_principal <= 0.0;
    }

    public function getCreditedAmountAttribute(): float
    {
        $p = (float) $this->principal_amount;
        $fee = (float) $this->admin_fee_flat + ($p * ((float) $this->admin_fee_pct / 100));
        $credit = $p - $fee;

        return $credit > 0 ? round($credit, 2) : 0.0;
    }

    public function getNextInstallmentAmountAttribute(): float
    {
        $per = (float) $this->per_installment;
        if ($per <= 0) {
            $per = round(((float)$this->principal_amount) / max((int)$totalInstallments = $this->total_installments, 1), 2);
        }
        return min($per, $this->remaining_principal);
    }

    public function getDefaultStartDate(?Carbon $asAt = null): ?Carbon
    {
        $asAt = $asAt ?: now();
        if ($this->defaulted_at && $this->defaulted_at->year > 1970) {
            if ($this->defaulted_at->lessThanOrEqualTo($asAt)) {
                return $this->defaulted_at;
            }
        }

        if ($this->getOverdueAmount($asAt) <= 0) return null;

        $schedule = $this->generateInstallmentSchedule();
        $per = (float) $this->per_installment;
        if ($per <= 0) {
            $per = round(((float)$this->principal_amount) / max((int)$this->total_installments, 1), 2);
        }

        $paid = (float) $this->paid_amount;
        $installmentsPaid = (int) floor($per > 0 ? ($paid / $per) : 0);

        if (isset($schedule[$installmentsPaid])) {
            $dueAt = $schedule[$installmentsPaid]['due_at'];
            if ($dueAt->lessThan($asAt)) {
                return $dueAt;
            }
        }

        return null;
    }

    public function getPeriodOfDefaultAttribute(): string
    {
        $startDate = $this->getDefaultStartDate();
        if (!$startDate) return 'None';

        $days = (int) abs(now()->diffInDays($startDate));
        $formattedDuration = DurationHelper::format($days);
        return $startDate->format('d-m-Y') . " ({$formattedDuration})";
    }
    public function transactionApprovals(): MorphMany
    {
        return $this->morphMany(TransactionApproval::class, 'approvable');
    }

    public function isHighValue(): bool
    {
        $threshold = config('cooperative.approvals.high_value_loan_threshold', 500000);
        return (float) $this->principal_amount >= (float) $threshold;
    }

    public function hasSufficientApprovals(): bool
    {
        if (!$this->isHighValue()) {
            return true;
        }

        $requiredCount = config('cooperative.approvals.required_approvals_count', 2);
        $approvedCount = $this->transactionApprovals()
            ->where('status', 'approved')
            ->count();

        return $approvedCount >= $requiredCount;
    }

    public function isAwaitingApprovals(): bool
    {
        return $this->isHighValue() && !$this->hasSufficientApprovals();
    }

    public function startPenaltyRecord(): void
    {
        if (!$this->defaulted_at) {
            return;
        }

        LoanPenalty::create([
            'user_id' => $this->user_id,
            'qard_hasan_id' => $this->id,
            'default_started_at' => $this->defaulted_at,
        ]);
    }

    public function completePenaltyRecord(): void
    {
        $penalty = LoanPenalty::where('qard_hasan_id', $this->id)
            ->whereNull('default_cleared_at')
            ->latest()
            ->first();

        if (!$penalty) {
            return;
        }

        $defaultedAt = $this->getOriginal('defaulted_at') ?: $penalty->default_started_at;
        if (!$defaultedAt) {
            return;
        }

        $now = now();
        $monthsDefaulted = (int) $defaultedAt->diffInMonths($now);
        $penaltyUntil = $now->copy()->add($defaultedAt->diff($now));

        $penalty->update([
            'months_defaulted' => $monthsDefaulted,
            'default_cleared_at' => $now,
            'penalty_until' => $penaltyUntil,
        ]);
    }

    public function ledgerJournal()
    {
        return $this->belongsTo(LedgerJournal::class);
    }

    /**
     * Recalculate the total paid amount from all successful repayments.
     */
    public function recalculatePaidAmount(): float
    {
        $total = (float) $this->repayments()
            ->where('status', 'success')
            ->sum('amount');

        if ((float) $this->paid_amount !== $total) {
            $this->paid_amount = $total;

            // Auto-complete if fully paid
            if ($this->paid_amount >= $this->principal_amount && $this->principal_amount > 0) {
                if (!in_array($this->status, ['cancelled', 'rejected'])) {
                    $this->status = 'completed';
                }
                if ($this->defaulted_at) {
                    $this->defaulted_at = null;
                }
            } elseif ($this->status === 'completed' && $this->paid_amount < $this->principal_amount) {
                // Re-activate if it was completed but now has balance (e.g. after deletion of repayment)
                $this->status = 'active';
            }

            $this->save();
        }

        return $total;
    }
}
