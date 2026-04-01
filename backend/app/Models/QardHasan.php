<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QardHasan extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (QardHasan $loan) {
            // Prevent deletion if any repayment exists or any amount has been paid
            if ($loan->repayments()->exists() || (float) $loan->paid_amount > 0) {
                throw new \RuntimeException('Cannot delete this loan because repayments have already started.');
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
    ];

    protected $casts = [
        'principal_amount' => 'float',
        'per_installment' => 'float',
        'admin_fee_flat' => 'float',
        'admin_fee_pct' => 'float',
        'paid_amount' => 'float',
                'approved_at' => 'datetime',
    ];

    protected $appends = [
        'remaining_principal',
        'progress_pct',
        'is_completed',
        'credited_amount',
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

    public function guarantors()
    {
        return $this->belongsToMany(User::class, 'qard_hasan_guarantors', 'qard_hasan_id', 'guarantor_id')
            ->withTimestamps()
            ->withPivot(['status', 'token', 'responded_at', 'nudge_count', 'last_nudged_at', 'escalated_at']);
    }

    public function allGuarantorsAccepted(): bool
    {
        $g = $this->guarantors;
        if (!$g || $g->isEmpty()) return false;
        // Require at least 2 guarantors and all must be accepted
        return $g->count() >= 2 && $g->every(fn($u) => ($u->pivot?->status) === 'accepted');
    }

    public function pendingGuarantorCount(): int
    {
        return (int) ($this->guarantors?->filter(fn($u) => ($u->pivot?->status) === 'pending')->count() ?? 0);
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
        if ($pct > 100) $pct = 100;
        if ($pct < 0) $pct = 0;
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
}
