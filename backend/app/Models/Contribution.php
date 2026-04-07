<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Contribution extends Model
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
        'scheme_id',
        'project_id',
        'amount',
        'reference',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->reference)) {
                $model->reference = self::generateReference();
            }
        });

        static::created(function (self $model) {
            // If created already successful and linked to a project (e.g., wallet allocation), create investment
            try {
                if ($model->project_id && $model->status === 'success') {
                    if (! ProjectInvestment::where('contribution_id', $model->id)->exists()) {
                        ProjectInvestment::create([
                            'user_id' => $model->user_id,
                            'project_id' => $model->project_id,
                            'contribution_id' => $model->id,
                            'amount' => $model->amount,
                            'reference' => $model->reference,
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                // Don’t block creation on investment linkage failures
            }
        });

        static::updated(function (self $model) {
            // When a contribution tied to a project is marked successful, create a ProjectInvestment once
            try {
                if ($model->project_id && $model->status === 'success' && $model->wasChanged('status')) {
                    // Avoid duplicates if re-updated
                    if (! ProjectInvestment::where('contribution_id', $model->id)->exists()) {
                        ProjectInvestment::create([
                            'user_id' => $model->user_id,
                            'project_id' => $model->project_id,
                            'contribution_id' => $model->id,
                            'amount' => $model->amount,
                            'reference' => $model->reference,
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                // Swallow to prevent blocking payment finalization; logs can be added if needed
            }
        });
    }

    public static function generateReference(): string
    {
        return 'CNTRB-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function scheme()
    {
        return $this->belongsTo(Scheme::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
