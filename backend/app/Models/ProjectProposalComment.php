<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectProposalComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_proposal_id',
        'user_id',
        'comment',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function proposal()
    {
        return $this->belongsTo(ProjectProposal::class, 'project_proposal_id');
    }
}
