<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Branch extends Model
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
        'latitude',
        'longitude',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Calculate the total savings for this branch.
     * Savings is defined as contributions where scheme name is 'Savings'.
     */
    public function getSavingsRateAttribute(): float
    {
        return (float) $this->users()
            ->join('contributions', 'users.id', '=', 'contributions.user_id')
            ->join('schemes', 'contributions.scheme_id', '=', 'schemes.id')
            ->where('schemes.name', 'Savings')
            ->where('contributions.status', 'success')
            ->sum('contributions.amount');
    }

    /**
     * Calculate the default rate for this branch.
     * Default Rate = (Unpaid Principal / Total Principal) * 100
     */
    public function getDefaultRateAttribute(): float
    {
        $totalPrincipal = (float) $this->users()
            ->join('qard_hasans', 'users.id', '=', 'qard_hasans.user_id')
            ->whereIn('qard_hasans.status', ['approved', 'completed'])
            ->sum('qard_hasans.principal_amount');

        if ($totalPrincipal <= 0) {
            return 0.0;
        }

        $paidPrincipal = (float) $this->users()
            ->join('qard_hasans', 'users.id', '=', 'qard_hasans.user_id')
            ->whereIn('qard_hasans.status', ['approved', 'completed'])
            ->sum('qard_hasans.paid_amount');

        $unpaidPrincipal = $totalPrincipal - $paidPrincipal;

        return round(($unpaidPrincipal / $totalPrincipal) * 100, 2);
    }
}
