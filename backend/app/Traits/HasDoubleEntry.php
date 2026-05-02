<?php

namespace App\Traits;

use App\Models\LedgerJournal;

trait HasDoubleEntry
{
    /**
     * Link the model to a ledger journal.
     */
    public function ledgerJournal()
    {
        return $this->belongsTo(LedgerJournal::class);
    }

    /**
     * Scope to find models without ledger entries.
     */
    public function scopeUnrecorded($query)
    {
        return $query->whereNull('ledger_journal_id');
    }

    /**
     * Helper to record ledger entry and link it.
     */
    public function recordToLedger(callable $recordingCallback)
    {
        if ($this->ledger_journal_id) {
            return $this->ledgerJournal;
        }

        try {
            $journal = $recordingCallback($this);
            if ($journal instanceof LedgerJournal) {
                $this->updateQuietly(['ledger_journal_id' => $journal->id]);
            }
            return $journal;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Failed to record ledger entry for " . get_class($this) . " ID: {$this->id}. Error: " . $e->getMessage());
            return null;
        }
    }
}
