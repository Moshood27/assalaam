<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'target_amount',
        'management_fee_percent',
        'active',
        'started_at',
        'closed_at',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'management_fee_percent' => 'decimal:2',
        'active' => 'boolean',
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function investments()
    {
        return $this->hasMany(ProjectInvestment::class);
    }

    public function profits()
    {
        return $this->hasMany(ProjectProfit::class);
    }
}
