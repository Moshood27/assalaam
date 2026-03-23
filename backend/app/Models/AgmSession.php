<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class AgmSession extends Model
{
    use HasFactory;

    protected $table = 'agm_sessions';

    protected $fillable = [
        'name',
        'status', // draft|open|closed
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    protected $appends = ['is_open', 'is_within_window'];

    public function getIsOpenAttribute(): bool
    {
        $status = (string) ($this->status ?? '');
        if ($status === 'open') {
            return true;
        }
        if ($this->start_at && $this->end_at) {
            $now = now();
            return $now->between($this->start_at, $this->end_at);
        }
        return false;
    }

    public function getIsWithinWindowAttribute(): bool
    {
        if ($this->start_at && $this->end_at) {
            $now = now();
            return $now->between($this->start_at, $this->end_at);
        }
        return false;
    }

    public function candidates()
    {
        return $this->hasMany(AgmCandidate::class, 'session_id');
    }

    public function votes()
    {
        return $this->hasMany(AgmVote::class, 'session_id');
    }
}
