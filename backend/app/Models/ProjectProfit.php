<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectProfit extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'gross_profit',
        'management_fee_percent',
        'management_fee_amount',
        'net_distributable',
        'note',
    ];

    protected $casts = [
        'gross_profit' => 'decimal:2',
        'management_fee_percent' => 'decimal:2',
        'management_fee_amount' => 'decimal:2',
        'net_distributable' => 'decimal:2',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
