<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\Support\LogOptions;
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
            ->dontLogEmptyChanges();
    }

    protected $casts = [
        'amount' => 'decimal:2',
        'units' => 'decimal:6',
    ];

    protected $fillable = [
        'user_id',
        'scheme_id',
        'project_id',
        'savings_group_id',
        'amount',
        'units',
        'reference',
        'status',
        'category',
        'ledger_journal_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->reference)) {
                $model->reference = self::generateReference();
            }
        });

        static::created(function (self $model) {
            // If created as success, handle project units decrement immediately (critical for consistency)
            if ($model->status === 'success' && $model->project_id && $model->units > 0) {
                try {
                    $project = Project::find($model->project_id);
                    if ($project && $project->is_unit_based) {
                        $project->decrement('available_units', $model->units);
                    }
                } catch (\Throwable $e) {}
            }

            // Offload all other side-effects to background job
            if ($model->status === 'success') {
                \App\Jobs\ProcessContributionSideEffects::dispatch($model->id);
            }
        });

        static::updated(function (self $model) {
            // When a contribution is marked successful, offload side-effects
            if ($model->status === 'success' && $model->wasChanged('status')) {

                // Handle project units decrement immediately (critical)
                if ($model->project_id && $model->units > 0) {
                    try {
                        $project = Project::find($model->project_id);
                        if ($project && $project->is_unit_based) {
                            $project->decrement('available_units', $model->units);
                        }
                    } catch (\Throwable $e) {}
                }

                \App\Jobs\ProcessContributionSideEffects::dispatch($model->id);
            }
        });

        static::deleted(function (self $model) {
            // Sync user scheme balance if it was successful (using background job)
            if ($model->status === 'success' && $model->scheme) {
                \App\Jobs\SyncUserBalance::dispatch($model->user_id, $model->scheme->name);
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

    public function savingsGroup()
    {
        return $this->belongsTo(SavingsGroup::class);
    }

    public function ledgerJournal()
    {
        return $this->belongsTo(LedgerJournal::class);
    }
}
