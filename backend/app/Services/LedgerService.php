<?php

namespace App\Services;

use App\Models\LedgerJournal;
use App\Models\LedgerAccount;
use Illuminate\Support\Facades\DB;
use Exception;

class LedgerService
{
    /**
     * Create a balanced journal entry.
     *
     * @param array $data ['date', 'reference', 'description', 'created_by']
     * @param array $entries [['ledger_account_id', 'debit', 'credit', 'description'], ...]
     * @return LedgerJournal
     * @throws Exception
     */
    public function record(array $data, array $entries): LedgerJournal
    {
        return DB::transaction(function () use ($data, $entries) {
            $journal = LedgerJournal::create([
                'date' => $data['date'] ?? now(),
                'reference' => $data['reference'] ?? null,
                'description' => $data['description'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);

            foreach ($entries as $entryData) {
                $journal->entries()->create([
                    'ledger_account_id' => $entryData['ledger_account_id'],
                    'debit' => $entryData['debit'] ?? 0,
                    'credit' => $entryData['credit'] ?? 0,
                    'description' => $entryData['description'] ?? null,
                ]);
            }

            if (!$journal->isBalanced()) {
                throw new Exception("Journal entry is not balanced. Total debits must equal total credits.");
            }

            return $journal;
        });
    }

    /**
     * Record a journal entry using account codes instead of IDs.
     *
     * @param array $data
     * @param array $entries [['code', 'debit', 'credit', 'description'], ...]
     * @return LedgerJournal
     * @throws Exception
     */
    public function recordByCode(array $data, array $entries): LedgerJournal
    {
        $resolvedEntries = array_map(function ($entry) {
            if (isset($entry['code'])) {
                $account = LedgerAccount::where('code', $entry['code'])->first();
                if (!$account) {
                    throw new Exception("Ledger account with code {$entry['code']} not found.");
                }
                $entry['ledger_account_id'] = $account->id;
                unset($entry['code']);
            }
            return $entry;
        }, $entries);

        return $this->record($data, $resolvedEntries);
    }

    /**
     * Get the balance of an account by code.
     */
    public function getBalance(string $code): float
    {
        $account = LedgerAccount::where('code', $code)->firstOrFail();
        return $account->balance;
    }
}
