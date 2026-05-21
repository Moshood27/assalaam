<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SavingsGoal extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'user_id',
        'title',
        'target_amount',
        'saved_amount',
        'target_date',
        'status',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'saved_amount' => 'decimal:2',
        'target_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookings()
    {
        return $this->hasMany(GoalBooking::class, 'savings_goal_id');
    }
}
