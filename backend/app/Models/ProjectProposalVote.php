<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectProposalVote extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_proposal_id',
        'user_id',
        'choice',
        'weight',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
    ];

    public function proposal()
    {
        return $this->belongsTo(ProjectProposal::class, 'project_proposal_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
