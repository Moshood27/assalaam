<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'date',
        'start_time',
        'end_time',
        'venue_lat',
        'venue_lng',
        'radius_meters',
        'pin',
        'fine_amount',
        'apology_fee_amount',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'venue_lat' => 'decimal:8',
        'venue_lng' => 'decimal:8',
        'fine_amount' => 'decimal:2',
        'apology_fee_amount' => 'decimal:2',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function isOngoing(): bool
    {
        if ($this->status !== 'ongoing') {
            return false;
        }

        $now = now();
        $start = \Carbon\Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->start_time);
        $end = \Carbon\Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->end_time);

        return $now->between($start, $end);
    }
}
