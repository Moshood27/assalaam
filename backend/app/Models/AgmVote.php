<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgmVote extends Model
{
    use HasFactory;

    protected $table = 'agm_votes';

    protected $fillable = [
        'session_id',
        'position',
        'user_id',
        'candidate_id',
    ];

    public function session()
    {
        return $this->belongsTo(AgmSession::class, 'session_id');
    }

    public function candidate()
    {
        return $this->belongsTo(AgmCandidate::class, 'candidate_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
