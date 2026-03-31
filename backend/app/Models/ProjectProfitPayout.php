<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectProfitPayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_profit_id',
        'project_id',
        'user_id',
        'amount',
        'status',
        'notified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'notified_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function profit()
    {
        return $this->belongsTo(ProjectProfit::class, 'project_profit_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
