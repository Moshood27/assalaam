<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanPenalty extends Model
{
    protected $fillable = [
        'user_id',
        'qard_hasan_id',
        'months_defaulted',
        'default_started_at',
        'default_cleared_at',
        'penalty_until',
    ];

    protected $casts = [
        'default_started_at' => 'datetime',
        'default_cleared_at' => 'datetime',
        'penalty_until' => 'datetime',
    ];

    protected $appends = [
        'formatted_default_duration',
        'formatted_wait_remaining',
    ];

    protected static function booted()
    {
        static::saved(function (LoanPenalty $penalty) {
            $penalty->syncUserPenalty();
        });

        static::deleted(function (LoanPenalty $penalty) {
            $penalty->syncUserPenalty();
        });
    }

    public function syncUserPenalty(): void
    {
        if (!$this->user) {
            return;
        }

        $latestActivePenalty = LoanPenalty::where('user_id', $this->user_id)
            ->where('penalty_until', '>', now())
            ->orderByDesc('penalty_until')
            ->first();

        $penaltyUntil = $latestActivePenalty ? $latestActivePenalty->penalty_until : null;

        if ($this->user->loan_penalty_until != $penaltyUntil) {
            $this->user->update(['loan_penalty_until' => $penaltyUntil]);
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function qardHasan(): BelongsTo
    {
        return $this->belongsTo(QardHasan::class);
    }

    public function getFormattedDefaultDurationAttribute(): string
    {
        if (!$this->default_started_at || !$this->default_cleared_at) {
            return 'N/A';
        }
        return $this->default_started_at->diffForHumans($this->default_cleared_at, [
            'parts' => 3,
            'join' => ', ',
            'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE
        ]);
    }

    public function getFormattedWaitRemainingAttribute(): string
    {
        if (!$this->penalty_until || $this->penalty_until->isPast()) {
            return 'Expired';
        }
        return now()->diffForHumans($this->penalty_until, [
            'parts' => 3,
            'join' => ', ',
            'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE
        ]);
    }
}
