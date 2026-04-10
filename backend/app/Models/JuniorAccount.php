<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JuniorAccount extends Model
{
    protected $fillable = [
        'user_id',
        'child_name',
        'child_dob',
        'balance',
        'locked_until',
        'purpose',
    ];

    protected $casts = [
        'child_dob' => 'date',
        'locked_until' => 'date',
        'balance' => 'decimal:2',
    ];

    public function parent()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
