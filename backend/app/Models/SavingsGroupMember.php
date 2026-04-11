<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavingsGroupMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'savings_group_id',
        'user_id',
        'status',
        'joined_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
    ];

    public function savingsGroup()
    {
        return $this->belongsTo(SavingsGroup::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
