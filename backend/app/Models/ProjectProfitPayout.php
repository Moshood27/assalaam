<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class ProjectProfitPayout extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

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
