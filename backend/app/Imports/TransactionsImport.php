<?php

namespace App\Imports;

use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TransactionsImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading
{
    protected static $sweptUsers = [];

    public function chunkSize(): int
    {
        return 100;
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $user = User::where('membership_number', $row['membership_no'])->first();
        if (!$user) {
            return null;
        }

        // --- CLEAN SWEEP: Execute ONLY ONCE per user in this import session ---
        if (!isset(self::$sweptUsers[$user->id])) {
            // 1. Remove non-migration (demo) transactions
            WalletTransaction::where('user_id', $user->id)
                ->where('source', '!=', 'migration')
                ->delete();

            // 2. Remove previous migration history transactions to avoid duplicates on re-run
            WalletTransaction::where('user_id', $user->id)
                ->where('source', 'migration')
                ->where('reference', 'LIKE', 'MIG-HIST-TX-%')
                ->delete();

            self::$sweptUsers[$user->id] = true;
        }

        $amount = (float) $row['amount'];
        $type = strtolower($row['type'] ?? 'credit');
        $date = $row['date'] ? Carbon::parse($row['date']) : now();
        $reference = 'MIG-HIST-TX-' . Str::upper($row['reference'] ?? Str::random(8));

        // Use standard migration source for historical transactions
        return new WalletTransaction([
            'user_id' => $user->id,
            'amount' => $amount,
            'type' => $type,
            'reference' => $reference,
            'source' => 'migration',
            'meta' => [
                'description' => $row['description'] ?? 'System Migration Transaction History',
                'original_reference' => $row['reference'] ?? null,
                'imported_at' => now()->toDateTimeString(),
            ],
            'created_at' => $date,
        ]);
    }

    public function rules(): array
    {
        return [
            'membership_no' => 'required|exists:users,membership_number',
            'amount' => 'required|numeric',
            'type' => 'required|in:credit,debit,CREDIT,DEBIT',
            'date' => 'nullable|date',
        ];
    }
}
