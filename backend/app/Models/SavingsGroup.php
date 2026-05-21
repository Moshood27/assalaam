<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SavingsGroup extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'purpose',
        'monthly_contribution_amount',
        'project_id',
        'creator_id',
        'status',
        'started_at',
    ];

    protected $casts = [
        'monthly_contribution_amount' => 'decimal:2',
        'started_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function members()
    {
        return $this->hasMany(SavingsGroupMember::class);
    }

    public function activeMembers()
    {
        return $this->hasMany(SavingsGroupMember::class)->where('status', 'active');
    }

    public function contributions()
    {
        return $this->hasMany(Contribution::class);
    }

    public function totalContributions()
    {
        return (float) $this->contributions()
            ->where('status', 'success')
            ->sum('amount');
    }
}
