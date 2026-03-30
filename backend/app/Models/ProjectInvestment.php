<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectInvestment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'contribution_id',
        'amount',
        'reference',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function contribution()
    {
        return $this->belongsTo(Contribution::class);
    }
}
