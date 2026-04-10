<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SadaqahContribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sadaqah_project_id',
        'amount',
        'reference',
        'status',
        'is_anonymous',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_anonymous' => 'boolean',
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(SadaqahProject::class, 'sadaqah_project_id');
    }
}
