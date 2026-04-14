<?php

namespace App\Imports;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Contribution;
use App\Models\Scheme;
use App\Models\TakafulContribution;
use App\Models\TakafulPoolEntry;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Str;

class BalancesImport implements OnEachRow, WithHeadingRow, WithValidation
{
    protected $migrationDate;

    public function __construct($migrationDate = null)
    {
        $this->migrationDate = $migrationDate ?: now();
    }

    public function onRow(Row $row)
    {
        $data = $row->toArray();
        $user = User::where('membership_number', $data['member_no'])->first();
        if (!$user) {
            return;
        }

        DB::transaction(function () use ($user, $data) {
            // 1. Savings
            if ($savings = (float) ($data['savings_balance'] ?? 0)) {
                $this->processOpeningBalance($user, 'Savings', $savings);
            }

            // 2. Shares
            if ($shares = (float) ($data['shares_balance'] ?? 0)) {
                $this->processOpeningBalance($user, 'Shares', $shares);
            }

            // 3. Takaful
            if ($takaful = (float) ($data['takaful_balance'] ?? 0)) {
                $this->processTakafulOpening($user, $takaful);
            }

            // 4. Development Fund
            if ($dev = (float) ($data['development_fund_balance'] ?? 0)) {
                $this->processOpeningBalance($user, 'Development', $dev);
            }

            // 5. Outstanding Fines
            if ($fines = (float) ($data['outstanding_fines'] ?? 0)) {
                $user->increment('outstanding_fines', $fines);
            }

            // 6. Wallet Balance
            if ($wallet = (float) ($data['wallet_balance'] ?? 0)) {
                $user->increment('balance', $wallet);
                WalletTransaction::create([
                    'user_id' => $user->id,
                    'amount' => $wallet,
                    'type' => 'credit',
                    'source' => 'migration',
                    'reference' => 'MIG-WLT-' . Str::random(6),
                    'meta' => ['description' => 'System Migration Opening Wallet Balance'],
                    'created_at' => $this->migrationDate,
                ]);
            }
        });
    }

    private function processOpeningBalance(User $user, string $schemeName, float $amount)
    {
        $scheme = Scheme::where('name', $schemeName)->first();
        if (!$scheme) {
            $scheme = Scheme::create(['name' => $schemeName]);
        }

        // Create the Migration Transaction audit trail
        WalletTransaction::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'type' => 'credit',
            'source' => 'migration',
            'reference' => 'MIG-' . strtoupper(substr($schemeName, 0, 3)) . '-' . Str::random(6),
            'meta' => ['description' => "System Migration Opening Balance for {$schemeName}"],
            'created_at' => $this->migrationDate,
        ]);

        // Create a Contribution record to trigger balance updates in User
        Contribution::create([
            'user_id' => $user->id,
            'scheme_id' => $scheme->id,
            'amount' => $amount,
            'status' => 'success',
            'reference' => 'MIG-CNTRB-' . strtoupper(substr($schemeName, 0, 3)) . '-' . Str::random(6),
            'created_at' => $this->migrationDate,
        ]);
    }

    private function processTakafulOpening(User $user, float $amount)
    {
        // 1. Record individual contribution
        TakafulContribution::create([
            'user_id' => $user->id,
            'period' => $this->migrationDate->format('Y-m'),
            'amount' => $amount,
            'status' => 'success',
            'reference' => 'MIG-TAKF-' . Str::random(6),
            'meta' => ['description' => 'System Migration Opening Balance'],
            'created_at' => $this->migrationDate,
        ]);

        // 2. Record pool entry
        TakafulPoolEntry::create([
            'user_id' => $user->id,
            'direction' => 'credit',
            'amount' => $amount,
            'reference' => 'MIG-TAKF-POOL-' . Str::random(6),
            'meta' => ['description' => 'System Migration Opening Balance'],
            'created_at' => $this->migrationDate,
        ]);

        // Also record in WalletTransaction for consistency if desired
        WalletTransaction::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'type' => 'credit',
            'source' => 'migration',
            'reference' => 'MIG-TAKF-TX-' . Str::random(6),
            'meta' => ['description' => "System Migration Opening Balance for Takaful"],
            'created_at' => $this->migrationDate,
        ]);
    }

    public function rules(): array
    {
        return [
            'member_no' => 'required|exists:users,membership_number',
            'savings_balance' => 'nullable|numeric|min:0',
            'shares_balance' => 'nullable|numeric|min:0',
            'takaful_balance' => 'nullable|numeric|min:0',
            'development_fund_balance' => 'nullable|numeric|min:0',
            'outstanding_fines' => 'nullable|numeric|min:0',
            'wallet_balance' => 'nullable|numeric|min:0',
        ];
    }
}
