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
        'grace_period_minutes',
        'pin',
        'fine_amount',
        'apology_fine_amount',
        'status',
        'reminder_sent_at',
    ];

    protected $appends = ['start_at', 'end_at'];

    protected $casts = [
        'date' => 'date',
        'venue_lat' => 'decimal:8',
        'venue_lng' => 'decimal:8',
        'grace_period_minutes' => 'integer',
        'fine_amount' => 'decimal:2',
        'apology_fine_amount' => 'decimal:2',
        'reminder_sent_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::created(function ($meeting) {
            if ($meeting->status === 'scheduled') {
                $meeting->notifyMembers(
                    "📅 New Meeting Scheduled: {$meeting->name}",
                    "A new meeting has been scheduled for " . $meeting->date->format('M d, Y') . " at " . $meeting->start_time . ".",
                    ['type' => 'meeting_scheduled']
                );
            } elseif ($meeting->status === 'ongoing') {
                $meeting->notifyMembers(
                    "⏰ Meeting Time: {$meeting->name}",
                    "The meeting is starting now. Please join or mark your attendance.",
                    ['type' => 'meeting_ongoing']
                );
            }
        });

        static::updated(function ($meeting) {
            // Handle manual status changes that might not be caught by commands
            if ($meeting->isDirty('status')) {
                $oldStatus = $meeting->getOriginal('status');
                $newStatus = $meeting->status;

                if ($oldStatus !== 'ongoing' && $newStatus === 'ongoing') {
                    $meeting->notifyMembers(
                        "⏰ Meeting Time: {$meeting->name}",
                        "The meeting is starting now. Please join or mark your attendance.",
                        ['type' => 'meeting_ongoing']
                    );
                } elseif ($oldStatus !== 'audited' && $newStatus === 'audited') {
                    $meeting->notifyMembers(
                        "✅ Meeting Audited: {$meeting->name}",
                        "The attendance for '{$meeting->name}' has been audited. You can check your status in the app.",
                        ['type' => 'meeting_audited']
                    );
                }
            }
        });
    }

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
                ], $data), ['push', 'database']);
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
