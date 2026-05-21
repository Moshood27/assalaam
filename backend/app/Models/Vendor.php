<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Vendor extends Model
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
        'name',
        'owner_user_id',
        'description',
        'phone',
        'address',
        'is_approved',
        'approved_at',
        'approved_by_id',
        'is_active',
        'settlement_bank_name',
        'settlement_bank_code',
        'settlement_account_number',
        'settlement_account_name',
        'commission_rate',
        'meta',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
        'is_active' => 'boolean',
        'commission_rate' => 'decimal:2',
        'meta' => 'array',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
