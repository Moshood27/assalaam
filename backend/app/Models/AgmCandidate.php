<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

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

    /**
     * Ensure the stored image path is exposed as a public URL for the frontend.
     */
    public function getPhotoUrlAttribute($value)
    {
        if (empty($value)) {
            return null;
        }
        $val = (string) $value;
        if (Str::startsWith($val, ['http://', 'https://', '/storage/'])) {
            return $val;
        }
        try {
            return Storage::disk('public')->url($val);
        } catch (\Throwable $e) {
            return $val;
        }
    }

    public function session()
    {
        return $this->belongsTo(AgmSession::class, 'session_id');
    }

    public function votes()
    {
        return $this->hasMany(AgmVote::class, 'candidate_id');
    }
}
