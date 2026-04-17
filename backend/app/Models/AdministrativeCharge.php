<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdministrativeCharge extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'amount',
        'percentage',
        'max_amount',
        'is_active',
        'frequency',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'percentage' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
