<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class UtilityTransaction extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'user_id',
        'type',
        'network',
        'phone_number',
        'amount',
        'cost_price',
        'profit',
        'reference',
        'order_id',
        'provider',
        'status',
        'provider_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'profit' => 'decimal:2',
        'provider_response' => 'array',
    ];

    protected static function booted()
    {
        static::updated(function ($model) {
            if ($model->wasChanged('status') && $model->status === 'success') {
                app(\App\Services\AssalaamScoreService::class)->calculateAndUpdateScore($model->user);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
