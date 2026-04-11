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
        'report_url',
        'media_urls',
        'target_amount',
        'management_fee_percent',
        'active',
        'is_unit_based',
        'unit_price',
        'total_units',
        'available_units',
        'started_at',
        'closed_at',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'management_fee_percent' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'is_unit_based' => 'boolean',
        'total_units' => 'integer',
        'available_units' => 'integer',
        'active' => 'boolean',
        'started_at' => 'datetime',
        'closed_at' => 'datetime',
        'media_urls' => 'array',
    ];

    public function investments()
    {
        return $this->hasMany(ProjectInvestment::class);
    }

    public function profits()
    {
        return $this->hasMany(ProjectProfit::class);
    }

    public function updates()
    {
        return $this->hasMany(ProjectUpdate::class)->latest();
    }

    public function savingsGroups()
    {
        return $this->hasMany(SavingsGroup::class);
    }
}
