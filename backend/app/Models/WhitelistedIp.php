<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhitelistedIp extends Model
{
    protected $fillable = [
        'label',
        'ip_address',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];
}
