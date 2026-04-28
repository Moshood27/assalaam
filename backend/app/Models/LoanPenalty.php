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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function qardHasan(): BelongsTo
    {
        return $this->belongsTo(QardHasan::class);
    }
}
