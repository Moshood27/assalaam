<?php

namespace App\Models;

use App\Jobs\AutoRecoverOverdueLoans;
use App\Jobs\RecoverOutstandingFines;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class WalletTransaction extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['type', 'amount', 'reference', 'source', 'meta', 'processed_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'reference',
        'source',
        'withdrawable',
        'meta',
        'ledger_journal_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'withdrawable' => 'boolean',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::created(function (WalletTransaction $tx) {
            // Trigger recoveries on any wallet credit after the surrounding DB transaction commits
            if (strtolower((string) $tx->type) === 'credit' && ! empty($tx->user_id)) {
                AutoRecoverOverdueLoans::dispatch((int) $tx->user_id)->afterCommit();
                RecoverOutstandingFines::dispatch((int) $tx->user_id)->afterCommit();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ledgerJournal()
    {
        return $this->belongsTo(LedgerJournal::class);
    }
}
