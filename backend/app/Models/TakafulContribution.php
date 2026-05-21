<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TakafulContribution extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['amount', 'reference', 'status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected $fillable = [
        'user_id',
        'period',
        'amount',
        'status',
        'reference',
        'meta',
        'ledger_journal_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::created(function (TakafulContribution $model) {
            if ($model->status === 'success' && !$model->ledger_journal_id) {
                try {
                    $journal = app(\App\Services\LedgerService::class)->recordTakafulContribution($model);
                    $model->updateQuietly(['ledger_journal_id' => $journal->id]);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to record takaful contribution in ledger: " . $e->getMessage());
                }
            }
        });

        static::updated(function (TakafulContribution $model) {
            if ($model->status === 'success' && $model->wasChanged('status') && !$model->ledger_journal_id) {
                try {
                    $journal = app(\App\Services\LedgerService::class)->recordTakafulContribution($model);
                    $model->updateQuietly(['ledger_journal_id' => $journal->id]);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to record takaful contribution in ledger: " . $e->getMessage());
                }
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
