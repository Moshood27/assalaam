<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LedgerAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'description',
        'parent_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(LedgerAccount::class, 'parent_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    /**
     * Get the balance of the account.
     */
    public function getBalanceAttribute(): float
    {
        $debits = $this->entries()->sum('debit');
        $credits = $this->entries()->sum('credit');

        // Assets and Expenses are increased by Debits
        // Liabilities, Equity, and Income are increased by Credits
        if (in_array($this->type, ['asset', 'expense'])) {
            return (float) ($debits - $credits);
        }

        return (float) ($credits - $debits);
    }
}
