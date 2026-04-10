<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ShariahAuditLog;

class ProjectProposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'target_amount',
        'status',
        'sharia_status',
        'sharia_notes',
        'sharia_certificate_path',
        'fatwa_summary',
        'voting_type',
        'minimum_quorum',
        'voting_start_at',
        'voting_end_at',
        'voting_open_notified_at',
        'results_notified_at',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'minimum_quorum' => 'integer',
        'voting_start_at' => 'datetime',
        'voting_end_at' => 'datetime',
        'voting_open_notified_at' => 'datetime',
        'results_notified_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::updated(function ($proposal) {
            if ($proposal->isDirty('sharia_status')) {
                $statusName = str_replace('_', ' ', $proposal->sharia_status);
                $proposal->user->notifyMember(
                    "Project Proposal Sharia Update",
                    "Your proposal '{$proposal->title}' has been marked as {$statusName} by the Sharia Board."
                );

                // Log Sharia Audit
                ShariahAuditLog::log(
                    auth()->user(),
                    'sharia_status_updated',
                    [
                        'proposal_id' => $proposal->id,
                        'new_status' => $proposal->sharia_status,
                        'old_status' => $proposal->getOriginal('sharia_status'),
                        'notes' => $proposal->sharia_notes,
                    ]
                );
            }

            if ($proposal->isDirty('status')) {
                $proposal->user->notifyMember(
                    "Project Proposal Update",
                    "The status of your proposal '{$proposal->title}' has changed to {$proposal->status}."
                );
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function votes()
    {
        return $this->hasMany(ProjectProposalVote::class);
    }

    public function comments()
    {
        return $this->hasMany(ProjectProposalComment::class);
    }

    public function getIsVotingOpenAttribute(): bool
    {
        if ($this->status !== 'voting') {
            return false;
        }

        if ($this->voting_start_at && $this->voting_end_at) {
            return now()->between($this->voting_start_at, $this->voting_end_at);
        }

        return true;
    }
}
