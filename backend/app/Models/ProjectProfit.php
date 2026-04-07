<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class ProjectProfit extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['gross_profit', 'management_fee_percent', 'management_fee_amount', 'net_distributable'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

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

    protected static function booted()
    {
        static::saving(function (self $model) {
            $gross = (float) ($model->gross_profit ?? 0);
            // Use provided percent or default from project if not set
            $percent = $model->management_fee_percent;
            if ($percent === null && $model->project_id) {
                $percent = (float) optional(Project::find($model->project_id))->management_fee_percent;
            }
            $percent = (float) ($percent ?? 0);
            $fee = round($gross * $percent / 100, 2);
            $net = round($gross - $fee, 2);

            $model->management_fee_percent = $percent;
            $model->management_fee_amount = $fee;
            $model->net_distributable = $net;
        });
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function payouts()
    {
        return $this->hasMany(ProjectProfitPayout::class, 'project_profit_id');
    }
}
