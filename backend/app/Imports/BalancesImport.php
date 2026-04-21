<?php

namespace App\Imports;

use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Contribution;
use App\Models\Scheme;
use App\Models\TakafulContribution;
use App\Models\TakafulPoolEntry;
use App\Models\ProjectInvestment;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class BalancesImport implements OnEachRow, WithHeadingRow, WithValidation, WithChunkReading
{
    protected $migrationDate;
    protected static $schemesCache = [];

    public function __construct($migrationDate = null)
    {
        $this->migrationDate = $migrationDate ?: now();
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function onRow(Row $row)
    {
        $data = $row->toArray();
        $user = User::where('membership_number', $data['membership_no'])->first();
        if (!$user) {
            return;
        }

        DB::transaction(function () use ($user, $data) {
            // Standard Schemes from Database (Dynamic mapping)
            $dbSchemes = Scheme::all();
            foreach ($dbSchemes as $scheme) {
                // Map scheme name to Excel key used in BranchMigrationTemplateExport
                $excelKey = strtolower(str_replace(' ', '_', $scheme->name)) . '_balance';
                if (isset($data[$excelKey])) {
                    $target = (float) $data[$excelKey];
                    $this->reconcileScheme($user, $scheme->name, $target);
                }
            }

            // Takaful (uses separate tables)
            if (isset($data['takaful_balance'])) {
                $takafulTarget = (float) $data['takaful_balance'];
                $this->reconcileTakaful($user, $takafulTarget);
            }

            // Digital Gold (Weight in grams)
            if (isset($data['digital_gold_balance'])) {
                $goldTarget = (float) $data['digital_gold_balance'];
                $this->reconcileGold($user, $goldTarget);
            }

            // Outstanding Fines (Debt)
            if (isset($data['outstanding_fines'])) {
                $finesTarget = (float) $data['outstanding_fines'];
                $this->reconcileDebt($user, 'outstanding_fines', 'Outstanding Fines', $finesTarget);
            }

            // Core Wallet Balance
            if (isset($data['wallet_balance'])) {
                $walletTarget = (float) $data['wallet_balance'];
                $this->reconcileWallet($user, $walletTarget);
            }
        });
    }

    private function reconcileScheme(User $user, string $schemeName, float $targetAmount)
    {
        $scheme = self::$schemesCache[$schemeName] ??= Scheme::firstOrCreate(['name' => $schemeName]);

        // 1. Clean Sweep: Remove non-migration records and previous migration opening balances
        // This ensures the passbook looks clean and only contains valid migration/history data.
        $contributionsQuery = Contribution::where('user_id', $user->id)
            ->where('scheme_id', $scheme->id)
            ->where(function($q) {
                $q->where('reference', 'NOT LIKE', 'MIG-%')
                  ->orWhere('reference', 'LIKE', 'MIG-CNTRB-%');
            });

        // Clean up linked project investments first to satisfy foreign key constraint
        $contributionIds = $contributionsQuery->pluck('id');
        ProjectInvestment::whereIn('contribution_id', $contributionIds)->delete();

        $contributionsQuery->delete();

        // 2. Calculate current sum from Passbook history (MIG-REC- / MIG-PASS-)
        $currentSum = (float) $user->contributions()
            ->where('scheme_id', $scheme->id)
            ->where('status', 'success')
            ->sum('amount');

        $diff = $targetAmount - $currentSum;

        if ($diff != 0) {
            Contribution::create([
                'user_id' => $user->id,
                'scheme_id' => $scheme->id,
                'amount' => $diff,
                'status' => 'success',
                'reference' => 'MIG-CNTRB-' . strtoupper(substr($schemeName, 0, 3)) . '-' . Str::random(6),
                'created_at' => $this->migrationDate,
            ]);
        }

        $this->syncUserColumn($user, $schemeName);
    }

    private function reconcileTakaful(User $user, float $targetAmount)
    {
        // Wipe demo Takaful contributions and previous opening adjustments
        TakafulContribution::where('user_id', $user->id)
            ->where(function($q) {
                $q->where('reference', 'NOT LIKE', 'MIG-%')
                  ->orWhere('reference', 'LIKE', 'MIG-TAKF-OPEN-%');
            })
            ->delete();

        TakafulPoolEntry::where('user_id', $user->id)
            ->where(function($q) {
                $q->where('reference', 'NOT LIKE', 'MIG-%')
                  ->orWhere('reference', 'LIKE', 'MIG-TAKF-POOL-OPEN-%');
            })
            ->delete();

        // Calculate current sum from Takaful history (Passbook)
        $currentSum = (float) TakafulContribution::where('user_id', $user->id)
            ->where('status', 'success')
            ->sum('amount');

        $diff = $targetAmount - $currentSum;

        if ($diff != 0) {
            $period = $this->migrationDate->format('Y-m');
            TakafulContribution::create([
                'user_id' => $user->id,
                'period' => $period,
                'amount' => $diff,
                'status' => 'success',
                'reference' => 'MIG-TAKF-OPEN-' . Str::random(6),
                'meta' => ['description' => 'System Migration Opening Balance Adjustment'],
                'created_at' => $this->migrationDate,
            ]);

            TakafulPoolEntry::create([
                'user_id' => $user->id,
                'direction' => $diff > 0 ? 'credit' : 'debit',
                'amount' => abs($diff),
                'reference' => 'MIG-TAKF-POOL-OPEN-' . Str::random(6),
                'meta' => ['description' => 'System Migration Opening Balance Adjustment'],
                'created_at' => $this->migrationDate,
            ]);
        }

        $this->syncUserColumn($user, 'Takaful');
    }

    private function reconcileGold(User $user, float $targetAmount)
    {
        $schemeName = 'Digital Gold';
        $scheme = self::$schemesCache[$schemeName] ??= Scheme::firstOrCreate(['name' => $schemeName]);

        // Wipe previous migration opening adjustments
        $contributionsQuery = Contribution::where('user_id', $user->id)
            ->where('scheme_id', $scheme->id)
            ->where('reference', 'LIKE', 'MIG-CNTRB-DIG-%');

        $contributionIds = $contributionsQuery->pluck('id');
        ProjectInvestment::whereIn('contribution_id', $contributionIds)->delete();

        $contributionsQuery->delete();

        // Calculate current sum from Passbook history
        $currentSum = (float) $user->contributions()
            ->where('scheme_id', $scheme->id)
            ->where('status', 'success')
            ->sum('amount');

        $diff = $targetAmount - $currentSum;

        if ($diff != 0) {
            Contribution::create([
                'user_id' => $user->id,
                'scheme_id' => $scheme->id,
                'amount' => $diff,
                'status' => 'success',
                'reference' => 'MIG-CNTRB-DIG-' . Str::random(6),
                'created_at' => $this->migrationDate,
            ]);

            // Audit in WalletTransaction too
            WalletTransaction::create([
                'user_id' => $user->id,
                'amount' => 0,
                'type' => $diff > 0 ? 'credit' : 'debit',
                'source' => 'migration',
                'reference' => 'MIG-GOLD-OPEN-' . Str::random(6),
                'meta' => [
                    'description' => 'System Migration Opening Gold Balance Adjustment',
                    'gold_weight' => abs($diff),
                    'final_balance' => $targetAmount
                ],
                'created_at' => $this->migrationDate,
            ]);
        }

        $this->syncUserColumn($user, $schemeName);
    }

    private function reconcileDebt(User $user, string $column, string $label, float $targetAmount)
    {
        // Wipe previous migration records for this debt
        WalletTransaction::where('user_id', $user->id)
            ->where('source', 'migration')
            ->where('reference', 'LIKE', 'MIG-DEBT-' . strtoupper(substr($column, 0, 3)) . '-%')
            ->delete();

        // Debt is not a scheme, so we just set it. History for debt is usually not provided monthly in Passbook.
        $user->forceFill([$column => $targetAmount])->save();

        if ($targetAmount > 0) {
            WalletTransaction::create([
                'user_id' => $user->id,
                'amount' => $targetAmount,
                'type' => 'debit',
                'source' => 'migration',
                'reference' => 'MIG-DEBT-' . strtoupper(substr($column, 0, 3)) . '-' . Str::random(6),
                'meta' => ['description' => "System Migration Opening Balance for {$label}"],
                'created_at' => $this->migrationDate,
            ]);
        }
    }

    private function reconcileWallet(User $user, float $targetAmount)
    {
        $schemeName = 'Wallet Balance';
        $scheme = self::$schemesCache[$schemeName] ??= Scheme::firstOrCreate(['name' => $schemeName]);

        // Wipe previous opening adjustments
        $contributionsQuery = Contribution::where('user_id', $user->id)
            ->where('scheme_id', $scheme->id)
            ->where('reference', 'LIKE', 'MIG-CNTRB-WAL-%');

        $contributionIds = $contributionsQuery->pluck('id');
        ProjectInvestment::whereIn('contribution_id', $contributionIds)->delete();

        $contributionsQuery->delete();

        // Wipe demo transactions (not from migration)
        WalletTransaction::where('user_id', $user->id)
            ->where('source', '!=', 'migration')
            ->delete();

        // Previous opening wallet transactions
        WalletTransaction::where('user_id', $user->id)
            ->where('source', 'migration')
            ->where('reference', 'LIKE', 'MIG-WLT-OPEN-%')
            ->delete();

        // Calculate current sum from Passbook history
        $currentSum = (float) $user->contributions()
            ->where('scheme_id', $scheme->id)
            ->where('status', 'success')
            ->sum('amount');

        $diff = $targetAmount - $currentSum;

        if ($diff != 0) {
            Contribution::create([
                'user_id' => $user->id,
                'scheme_id' => $scheme->id,
                'amount' => $diff,
                'status' => 'success',
                'reference' => 'MIG-CNTRB-WAL-' . Str::random(6),
                'created_at' => $this->migrationDate,
            ]);

            WalletTransaction::create([
                'user_id' => $user->id,
                'amount' => abs($diff),
                'type' => $diff > 0 ? 'credit' : 'debit',
                'source' => 'migration',
                'reference' => 'MIG-WLT-OPEN-' . Str::random(6),
                'meta' => [
                    'description' => 'System Migration Opening Wallet Balance Adjustment',
                    'final_balance' => $targetAmount,
                    'adjustment' => $diff
                ],
                'created_at' => $this->migrationDate,
            ]);
        }

        $this->syncUserColumn($user, $schemeName);
    }

    private function syncUserColumn(User $user, string $schemeName)
    {
        $columnMap = [
            'Savings' => 'ordinary_savings',
            'Ordinary Savings' => 'ordinary_savings',
            'Shares' => 'shares_capital',
            'Share Capital' => 'shares_capital',
            'Development' => 'development_fund_balance',
            'Building' => 'building_balance',
            'AGM' => 'agm_balance',
            'Loan Repayment' => 'loan_repayment_balance',
            'Fine' => 'fine_balance',
            'Welfare' => 'welfare_balance',
            'Lateness' => 'lateness_balance',
            'Stationery' => 'stationery_balance',
            'Loan Form' => 'loan_form_balance',
            'Others' => 'others_balance',
            'ID Card' => 'id_card_balance',
            'Emergency' => 'emergency_balance',
            'Entrance' => 'entrance_balance',
            'H Savings' => 'h_savings_balance',
            'Investment' => 'investment_balance',
            'Group Savings' => 'group_savings_balance',
            'Special Savings' => 'special_savings_balance',
            'Takaful' => 'takaful_balance',
            'Digital Gold' => 'gold_balance',
            'Wallet Balance' => 'balance',
        ];

        if (isset($columnMap[$schemeName])) {
            $column = $columnMap[$schemeName];

            $actualTotal = (float) $user->contributions()
                ->whereHas('scheme', fn($q) => $q->where('name', $schemeName))
                ->where('status', 'success')
                ->sum('amount');

            $user->forceFill([$column => $actualTotal])->save();
        }
    }

    public function rules(): array
    {
        $rules = [
            'membership_no' => 'required|exists:users,membership_number',
        ];

        // Dynamic rules for all schemes in database
        $dbSchemes = Scheme::all();
        foreach ($dbSchemes as $scheme) {
            $excelKey = strtolower(str_replace(' ', '_', $scheme->name)) . '_balance';
            $rules[$excelKey] = 'nullable|numeric';
        }

        // Extra migration-specific balance columns
        $rules['takaful_balance'] = 'nullable|numeric';
        $rules['digital_gold_balance'] = 'nullable|numeric';
        $rules['outstanding_fines'] = 'nullable|numeric';
        $rules['wallet_balance'] = 'nullable|numeric';

        return $rules;
    }
}
