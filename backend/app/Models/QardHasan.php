<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\TransactionApproval;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
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
        $start = $startAt ?: ($this->approved_at ?: $this->created_at ?: now());
        $start = ($start instanceof Carbon) ? $start->copy() : Carbon::parse((string) $start);

        $items = [];
        $cursor = $start->copy();
        for ($i = 0; $i < $total; $i++) {
            $cursor = $this->addInterval($cursor, $interval); // move by one interval each time
            $items[] = [
                'index' => $i + 1,
                'due_at' => $cursor->copy(),
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
        static::deleting(function (QardHasan $loan) {
            // Prevent deletion if any repayment exists or any amount has been paid
            if ($loan->repayments()->exists() || (float) $loan->paid_amount > 0) {
                return false;
            }
            // Guarantor pivot will be cascaded by FK; no manual detach required
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
        'rejection_reason',
        'approved_by',
        'approved_at',
        'agreement_template',
        'signed_agreement',
        'agreement_uploaded_at',
        'agreement_verified_at',
        'agreement_rejection_reason',
    ];

    protected $casts = [
        'principal_amount' => 'float',
        'per_installment' => 'float',
        'admin_fee_flat' => 'float',
        'admin_fee_pct' => 'float',
        'paid_amount' => 'float',
        'approved_at' => 'datetime',
        'agreement_uploaded_at' => 'datetime',
        'agreement_verified_at' => 'datetime',
    ];

    protected $appends = [
        'remaining_principal',
        'progress_pct',
        'is_completed',
        'credited_amount',
        'next_due_at',
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
}
