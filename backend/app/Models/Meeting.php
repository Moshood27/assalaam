<?php

namespace App\Models;

use App\Models\User;
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
        'status',
        'reminder_sent_at',
    ];

    protected $appends = ['start_at', 'end_at'];

    protected $casts = [
        'date' => 'date',
        'venue_lat' => 'decimal:8',
        'venue_lng' => 'decimal:8',
        'fine_amount' => 'decimal:2',
        'reminder_sent_at' => 'datetime',
    ];

    public function getStartAtAttribute()
    {
        $timezone = config('cooperative.timezone', 'Africa/Lagos');
        return \Carbon\Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->start_time, $timezone)->toIso8601String();
    }

    public function getEndAtAttribute()
    {
        $timezone = config('cooperative.timezone', 'Africa/Lagos');
        return \Carbon\Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->end_time, $timezone)->toIso8601String();
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function branches(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Branch::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    /**
     * Notify all eligible members about this meeting.
     */
    public function notifyMembers(string $title, string $body, array $data = []): void
    {
        $query = User::where('is_admin', false)
            ->where('is_defaulter', false);

        // Filter by branches if specified
        if ($this->branches()->exists()) {
            $query->whereIn('branch_id', $this->branches()->pluck('branches.id'));
        }

        $users = $query->get();

        foreach ($users as $user) {
            try {
                $user->notifyMember($title, $body, array_merge([
                    'type' => 'meeting_notification',
                    'meeting_id' => (string) $this->id,
                    'action' => '/attendance'
                ], $data));
            } catch (\Throwable $e) {
                // skip failed notifications
            }
        }
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
