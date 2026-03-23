<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgmCandidate extends Model
{
    use HasFactory;

    protected $table = 'agm_candidates';

    protected $fillable = [
        'session_id',
        'name',
        'position',
        'manifesto',
        'photo_url',
    ];

    public function session()
    {
        return $this->belongsTo(AgmSession::class, 'session_id');
    }

    public function votes()
    {
        return $this->hasMany(AgmVote::class, 'candidate_id');
    }
}
