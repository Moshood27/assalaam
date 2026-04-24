<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'meeting_id',
        'status',
        'attended_at',
        'lat',
        'lng',
        'device_uuid',
        'fine_paid_at',
        'lateness_fine_paid',
        'lateness_fine_amount',
        'excuse_reason',
        'excuse_type',
        'excuse_proof_path',
        'excused_at',
    ];

    protected $casts = [
        'attended_at' => 'datetime',
        'fine_paid_at' => 'datetime',
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'lateness_fine_paid' => 'boolean',
        'lateness_fine_amount' => 'decimal:2',
        'excuse_type' => 'string',
        'excuse_proof_path' => 'string',
        'excused_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }
}
