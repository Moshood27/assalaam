<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Beneficiary extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'relationship',
        'phone',
        'email',
        'address',
        'percentage',
        'asset_type',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
