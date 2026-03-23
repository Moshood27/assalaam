<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoalBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'savings_goal_id',
        'partner_name',
        'package',
        'booking_amount',
        'commission_rate',
        'commission_amount',
        'reference',
        'status',
    ];

    protected $casts = [
        'booking_amount' => 'decimal:2',
        'commission_rate' => 'decimal:4',
        'commission_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function goal()
    {
        return $this->belongsTo(SavingsGoal::class, 'savings_goal_id');
    }
}
