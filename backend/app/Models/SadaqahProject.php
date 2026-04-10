<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SadaqahProject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'target_amount',
        'raised_amount',
        'type',
        'media_urls',
        'active',
        'started_at',
        'closed_at',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'raised_amount' => 'decimal:2',
        'media_urls' => 'array',
        'active' => 'boolean',
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function contributions()
    {
        return $this->hasMany(SadaqahContribution::class);
    }

    public function getProgressAttribute()
    {
        if ($this->target_amount <= 0) {
            return 0;
        }
        return min(100, round(($this->raised_amount / $this->target_amount) * 100, 2));
    }
}
