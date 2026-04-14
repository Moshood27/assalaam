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
            $checkMigrated = function ($desc) use ($user) {
                return WalletTransaction::where('user_id', $user->id)
                    ->where('source', 'migration')
                    ->where('meta->description', $desc)
                    ->exists();
            };

            // Safety: Ensure user balance columns are not NULL before incrementing
            $ensureNotNull = function ($column) use ($user) {
                if (is_null($user->{$column})) {
                    $user->{$column} = 0;
                    $user->save();
                }
            };

            // 1. Savings
            $savings = (float) ($data['savings_balance'] ?? 0);
            if (!$checkMigrated('System Migration Opening Balance for Savings')) {
                $ensureNotNull('ordinary_savings');

                // Reconciliation: Set balance to exactly what's in Excel
                $currentBalance = (float) $user->ordinary_savings;
                $diff = $savings - $currentBalance;

                if ($diff != 0) {
                    $user->increment('ordinary_savings', $diff);
                    $this->processOpeningBalance($user, 'Savings', $savings, $diff);
                } else {
                    // Even if diff is 0, we mark it as migrated to avoid future re-processing
                    $this->markAsMigrated($user, 'Savings', $savings);
                    $user->save();
                }
            }

            // 2. Shares
            $shares = (float) ($data['shares_balance'] ?? 0);
            if (!$checkMigrated('System Migration Opening Balance for Shares')) {
                $ensureNotNull('shares_capital');

                $currentBalance = (float) $user->shares_capital;
                $diff = $shares - $currentBalance;

                if ($diff != 0) {
                    $user->increment('shares_capital', $diff);
                    $this->processOpeningBalance($user, 'Shares', $shares, $diff);
                } else {
                    $this->markAsMigrated($user, 'Shares', $shares);
                    $user->save();
                }
            }

            // 3. Takaful
            $takaful = (float) ($data['takaful_balance'] ?? 0);
            // Takaful has its own check inside processTakafulOpening
            $this->processTakafulOpening($user, $takaful);

            // 4. Development Fund
            $dev = (float) ($data['development_fund_balance'] ?? 0);
            if (!$checkMigrated('System Migration Opening Balance for Development')) {
                $ensureNotNull('development_fund_balance');

                $currentBalance = (float) $user->development_fund_balance;
                $diff = $dev - $currentBalance;

                if ($diff != 0) {
                    $user->increment('development_fund_balance', $diff);
                    $this->processOpeningBalance($user, 'Development', $dev, $diff);
                } else {
                    $this->markAsMigrated($user, 'Development', $dev);
                    $user->save();
                }
            }

            // 5. Outstanding Fines
            $fines = (float) ($data['outstanding_fines'] ?? 0);
            if (!$checkMigrated('System Migration Opening Balance for Outstanding Fines')) {
                $ensureNotNull('outstanding_fines');

                $currentBalance = (float) $user->outstanding_fines;
                $diff = $fines - $currentBalance;

                if ($diff != 0) {
                    $user->increment('outstanding_fines', $diff);
                    // Create a transaction to mark it as done
                    WalletTransaction::create([
                        'user_id' => $user->id,
                        'amount' => abs($diff),
                        'type' => $diff > 0 ? 'debit' : 'credit', // Increasing debt is debit, decreasing is credit
                        'source' => 'migration',
                        'reference' => 'MIG-FINE-OUT-' . Str::random(6),
                        'meta' => [
                            'description' => 'System Migration Opening Balance for Outstanding Fines',
                            'final_balance' => $fines,
                            'adjustment' => $diff
                        ],
                        'created_at' => $this->migrationDate,
                    ]);
                } else {
                    $this->markAsMigrated($user, 'Outstanding Fines', $fines, 'MIG-FINE-OUT-');
                    $user->save();
                }
            }

            // 6. Wallet Balance
            $wallet = (float) ($data['wallet_balance'] ?? 0);
            if (!$checkMigrated('System Migration Opening Wallet Balance')) {
                $ensureNotNull('balance');

                $currentBalance = (float) $user->balance;
                $diff = $wallet - $currentBalance;

                if ($diff != 0) {
                    $user->increment('balance', $diff);
                    WalletTransaction::create([
                        'user_id' => $user->id,
                        'amount' => abs($diff),
                        'type' => $diff > 0 ? 'credit' : 'debit',
                        'source' => 'migration',
                        'reference' => 'MIG-WLT-' . Str::random(6),
                        'meta' => [
                            'description' => 'System Migration Opening Wallet Balance',
                            'final_balance' => $wallet,
                            'adjustment' => $diff
                        ],
                        'created_at' => $this->migrationDate,
                    ]);
                } else {
                    $this->markAsMigrated($user, 'Wallet Balance', $wallet, 'MIG-WLT-');
                    $user->save();
                }
            }

            // 7. Building
            $building = (float) ($data['building_balance'] ?? 0);
            if (!$checkMigrated('System Migration Opening Balance for Building')) {
                $ensureNotNull('building_balance');

                $currentBalance = (float) $user->building_balance;
                $diff = $building - $currentBalance;

                if ($diff != 0) {
                    $user->increment('building_balance', $diff);
                    $this->processOpeningBalance($user, 'Building', $building, $diff);
                } else {
                    $this->markAsMigrated($user, 'Building', $building);
                    $user->save();
                }
            }

            // 8. AGM
            $agm = (float) ($data['agm_balance'] ?? 0);
            if (!$checkMigrated('System Migration Opening Balance for AGM')) {
                $ensureNotNull('agm_balance');

                $currentBalance = (float) $user->agm_balance;
                $diff = $agm - $currentBalance;

                if ($diff != 0) {
                    $user->increment('agm_balance', $diff);
                    $this->processOpeningBalance($user, 'AGM', $agm, $diff);
                } else {
                    $this->markAsMigrated($user, 'AGM', $agm);
                    $user->save();
                }
            }

            // 9. Loan Repayment
            $loanRepay = (float) ($data['loan_repayment_balance'] ?? 0);
            if (!$checkMigrated('System Migration Opening Balance for Loan Repayment')) {
                $ensureNotNull('loan_repayment_balance');

                $currentBalance = (float) $user->loan_repayment_balance;
                $diff = $loanRepay - $currentBalance;

                if ($diff != 0) {
                    $user->increment('loan_repayment_balance', $diff);
                    $this->processOpeningBalance($user, 'Loan Repayment', $loanRepay, $diff);
                } else {
                    $this->markAsMigrated($user, 'Loan Repayment', $loanRepay);
                    $user->save();
                }
            }

            // 10. Fine (as a scheme/contribution)
            $fine = (float) ($data['fine_balance'] ?? 0);
            if (!$checkMigrated('System Migration Opening Balance for Fine')) {
                $ensureNotNull('fine_balance');

                $currentBalance = (float) $user->fine_balance;
                $diff = $fine - $currentBalance;

                if ($diff != 0) {
                    $user->increment('fine_balance', $diff);
                    $this->processOpeningBalance($user, 'Fine', $fine, $diff);
                } else {
                    $this->markAsMigrated($user, 'Fine', $fine);
                    $user->save();
                }
            }

            // 11. Welfare
            $welfare = (float) ($data['welfare_balance'] ?? 0);
            if (!$checkMigrated('System Migration Opening Balance for Welfare')) {
                $ensureNotNull('welfare_balance');

                $currentBalance = (float) $user->welfare_balance;
                $diff = $welfare - $currentBalance;

                if ($diff != 0) {
                    $user->increment('welfare_balance', $diff);
                    $this->processOpeningBalance($user, 'Welfare', $welfare, $diff);
                } else {
                    $this->markAsMigrated($user, 'Welfare', $welfare);
                    $user->save();
                }
            }

            // 12. Lateness
            $lateness = (float) ($data['lateness_balance'] ?? 0);
            if (!$checkMigrated('System Migration Opening Balance for Lateness')) {
                $ensureNotNull('lateness_balance');

                $currentBalance = (float) $user->lateness_balance;
                $diff = $lateness - $currentBalance;

                if ($diff != 0) {
                    $user->increment('lateness_balance', $diff);
                    $this->processOpeningBalance($user, 'Lateness', $lateness, $diff);
                } else {
                    $this->markAsMigrated($user, 'Lateness', $lateness);
                    $user->save();
                }
            }

            // 13. Stationery
            $stationery = (float) ($data['stationery_balance'] ?? 0);
            if (!$checkMigrated('System Migration Opening Balance for Stationery')) {
                $ensureNotNull('stationery_balance');

                $currentBalance = (float) $user->stationery_balance;
                $diff = $stationery - $currentBalance;

                if ($diff != 0) {
                    $user->increment('stationery_balance', $diff);
                    $this->processOpeningBalance($user, 'Stationery', $stationery, $diff);
                } else {
                    $this->markAsMigrated($user, 'Stationery', $stationery);
                    $user->save();
                }
            }

            // 14. Loan Form
            $loanForm = (float) ($data['loan_form_balance'] ?? 0);
            if (!$checkMigrated('System Migration Opening Balance for Loan Form')) {
                $ensureNotNull('loan_form_balance');

                $currentBalance = (float) $user->loan_form_balance;
                $diff = $loanForm - $currentBalance;

                if ($diff != 0) {
                    $user->increment('loan_form_balance', $diff);
                    $this->processOpeningBalance($user, 'Loan Form', $loanForm, $diff);
                } else {
                    $this->markAsMigrated($user, 'Loan Form', $loanForm);
                    $user->save();
                }
            }

            // 15. Others
            $others = (float) ($data['others_balance'] ?? 0);
            if (!$checkMigrated('System Migration Opening Balance for Others')) {
                $ensureNotNull('others_balance');

                $currentBalance = (float) $user->others_balance;
                $diff = $others - $currentBalance;

                if ($diff != 0) {
                    $user->increment('others_balance', $diff);
                    $this->processOpeningBalance($user, 'Others', $others, $diff);
                } else {
                    $this->markAsMigrated($user, 'Others', $others);
                    $user->save();
                }
            }

            // 16. ID Card
            $idCard = (float) ($data['id_card_balance'] ?? 0);
            if (!$checkMigrated('System Migration Opening Balance for ID Card')) {
                $ensureNotNull('id_card_balance');

                $currentBalance = (float) $user->id_card_balance;
                $diff = $idCard - $currentBalance;

                if ($diff != 0) {
                    $user->increment('id_card_balance', $diff);
                    $this->processOpeningBalance($user, 'ID Card', $idCard, $diff);
                } else {
                    $this->markAsMigrated($user, 'ID Card', $idCard);
                    $user->save();
                }
            }

            // 17. Emergency
            $emergency = (float) ($data['emergency_balance'] ?? 0);
            if (!$checkMigrated('System Migration Opening Balance for Emergency')) {
                $ensureNotNull('emergency_balance');

                $currentBalance = (float) $user->emergency_balance;
                $diff = $emergency - $currentBalance;

                if ($diff != 0) {
                    $user->increment('emergency_balance', $diff);
                    $this->processOpeningBalance($user, 'Emergency', $emergency, $diff);
                } else {
                    $this->markAsMigrated($user, 'Emergency', $emergency);
                    $user->save();
                }
            }

            // 18. Entrance
            $entrance = (float) ($data['entrance_balance'] ?? 0);
            if (!$checkMigrated('System Migration Opening Balance for Entrance')) {
                $ensureNotNull('entrance_balance');

                $currentBalance = (float) $user->entrance_balance;
                $diff = $entrance - $currentBalance;

                if ($diff != 0) {
                    $user->increment('entrance_balance', $diff);
                    $this->processOpeningBalance($user, 'Entrance', $entrance, $diff);
                } else {
                    $this->markAsMigrated($user, 'Entrance', $entrance);
                    $user->save();
                }
            }

            // 19. H Savings
            $hSavings = (float) ($data['h_savings_balance'] ?? 0);
            if (!$checkMigrated('System Migration Opening Balance for H Savings')) {
                $ensureNotNull('h_savings_balance');

                $currentBalance = (float) $user->h_savings_balance;
                $diff = $hSavings - $currentBalance;

                if ($diff != 0) {
                    $user->increment('h_savings_balance', $diff);
                    $this->processOpeningBalance($user, 'H Savings', $hSavings, $diff);
                } else {
                    $this->markAsMigrated($user, 'H Savings', $hSavings);
                    $user->save();
                }
            }

            // 20. Investment
            $investment = (float) ($data['investment_balance'] ?? 0);
            if (!$checkMigrated('System Migration Opening Balance for Investment')) {
                $ensureNotNull('investment_balance');

                $currentBalance = (float) $user->investment_balance;
                $diff = $investment - $currentBalance;

                if ($diff != 0) {
                    $user->increment('investment_balance', $diff);
                    $this->processOpeningBalance($user, 'Investment', $investment, $diff);
                } else {
                    $this->markAsMigrated($user, 'Investment', $investment);
                    $user->save();
                }
            }

            // 21. Digital Gold (Weight in grams)
            $gold = (float) ($data['digital_gold_balance'] ?? 0);
            if (!$checkMigrated('System Migration Opening Gold Balance')) {
                $ensureNotNull('gold_balance');

                $currentBalance = (float) $user->gold_balance;
                $diff = $gold - $currentBalance;

                if ($diff != 0) {
                    $user->increment('gold_balance', $diff);
                    WalletTransaction::create([
                        'user_id' => $user->id,
                        'amount' => 0, // It's gold, not naira
                        'type' => $diff > 0 ? 'credit' : 'debit',
                        'source' => 'migration',
                        'reference' => 'MIG-GOLD-' . Str::random(6),
                        'meta' => [
                            'description' => 'System Migration Opening Gold Balance',
                            'gold_weight' => abs($diff),
                            'final_balance' => $gold,
                            'adjustment' => $diff
                        ],
                        'created_at' => $this->migrationDate,
                    ]);
                } else {
                    $this->markAsMigrated($user, 'Gold Balance', $gold, 'MIG-GOLD-');
                    $user->save();
                }
            }

            // 22. Group Savings
            $groupSavings = (float) ($data['group_savings_balance'] ?? 0);
            if (!$checkMigrated('System Migration Opening Balance for Group Savings')) {
                $ensureNotNull('group_savings_balance');

                $currentBalance = (float) $user->group_savings_balance;
                $diff = $groupSavings - $currentBalance;

                if ($diff != 0) {
                    $user->increment('group_savings_balance', $diff);
                    $this->processOpeningBalance($user, 'Group Savings', $groupSavings, $diff);
                } else {
                    $this->markAsMigrated($user, 'Group Savings', $groupSavings);
                    $user->save();
                }
            }
        });
    }

    private function processOpeningBalance(User $user, string $schemeName, float $finalAmount, float $adjustment)
    {
        // Avoid duplicate migration if re-running
        $alreadyDone = WalletTransaction::where('user_id', $user->id)
            ->where('source', 'migration')
            ->where('meta->description', "System Migration Opening Balance for {$schemeName}")
            ->exists();

        if ($alreadyDone) {
            return;
        }

        if (!isset(self::$schemesCache[$schemeName])) {
            self::$schemesCache[$schemeName] = Scheme::firstOrCreate(['name' => $schemeName]);
        }
        $scheme = self::$schemesCache[$schemeName];

        // Create the Migration Transaction audit trail (The adjustment)
        WalletTransaction::create([
            'user_id' => $user->id,
            'amount' => abs($adjustment),
            'type' => $adjustment > 0 ? 'credit' : 'debit',
            'source' => 'migration',
            'reference' => 'MIG-' . strtoupper(substr($schemeName, 0, 3)) . '-' . Str::random(6),
            'meta' => [
                'description' => "System Migration Opening Balance for {$schemeName}",
                'final_balance' => $finalAmount,
                'adjustment' => $adjustment
            ],
            'created_at' => $this->migrationDate,
        ]);

        // Create a Contribution record to trigger balance updates in User
        // We use the adjustment here because Contribution logic might trigger events
        Contribution::create([
            'user_id' => $user->id,
            'scheme_id' => $scheme->id,
            'amount' => $adjustment, // Can be negative if supported by DB/Model, or we handle it
            'status' => 'success',
            'reference' => 'MIG-CNTRB-' . strtoupper(substr($schemeName, 0, 3)) . '-' . Str::random(6),
            'created_at' => $this->migrationDate,
        ]);
    }

    private function markAsMigrated(User $user, string $schemeName, float $finalAmount, string $prefix = 'MIG-SKIP-')
    {
        WalletTransaction::create([
            'user_id' => $user->id,
            'amount' => 0,
            'type' => 'credit',
            'source' => 'migration',
            'reference' => $prefix . strtoupper(substr($schemeName, 0, 3)) . '-' . Str::random(6),
            'meta' => [
                'description' => "System Migration Opening Balance for {$schemeName}",
                'final_balance' => $finalAmount,
                'adjustment' => 0,
                'note' => 'Balance already matched Excel; marked as migrated.'
            ],
            'created_at' => $this->migrationDate,
        ]);
    }

    private function processTakafulOpening(User $user, float $amount)
    {
        $period = $this->migrationDate->format('Y-m');

        // Avoid duplicate migration if re-running
        $alreadyDone = TakafulContribution::where('user_id', $user->id)
            ->where('period', $period)
            ->where('reference', 'like', 'MIG-TAKF-%')
            ->exists();

        if ($alreadyDone) {
            return;
        }

        // 1. Record individual contribution
        // Use updateOrCreate-like logic to avoid unique constraint crashes
        // even if a non-migration record exists (we'll increment it)
        $contribution = TakafulContribution::where('user_id', $user->id)
            ->where('period', $period)
            ->first();

        if ($contribution) {
            $contribution->increment('amount', $amount);
        } else {
            TakafulContribution::create([
                'user_id' => $user->id,
                'period' => $period,
                'amount' => $amount,
                'status' => 'success',
                'reference' => 'MIG-TAKF-' . Str::random(6),
                'meta' => ['description' => 'System Migration Opening Balance'],
                'created_at' => $this->migrationDate,
            ]);
        }

        // 2. Record pool entry
        TakafulPoolEntry::create([
            'user_id' => $user->id,
            'direction' => 'credit',
            'amount' => $amount,
            'reference' => 'MIG-TAKF-POOL-' . Str::random(6),
            'meta' => ['description' => 'System Migration Opening Balance'],
            'created_at' => $this->migrationDate,
        ]);

        // Also record in WalletTransaction for consistency
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
            'membership_no' => 'required|exists:users,membership_number',
            'savings_balance' => 'nullable|numeric|min:0',
            'shares_balance' => 'nullable|numeric|min:0',
            'takaful_balance' => 'nullable|numeric|min:0',
            'development_fund_balance' => 'nullable|numeric|min:0',
            'outstanding_fines' => 'nullable|numeric|min:0',
            'wallet_balance' => 'nullable|numeric|min:0',
            'building_balance' => 'nullable|numeric|min:0',
            'agm_balance' => 'nullable|numeric|min:0',
            'loan_repayment_balance' => 'nullable|numeric|min:0',
            'fine_balance' => 'nullable|numeric|min:0',
            'welfare_balance' => 'nullable|numeric|min:0',
            'lateness_balance' => 'nullable|numeric|min:0',
            'stationery_balance' => 'nullable|numeric|min:0',
            'loan_form_balance' => 'nullable|numeric|min:0',
            'others_balance' => 'nullable|numeric|min:0',
            'id_card_balance' => 'nullable|numeric|min:0',
            'emergency_balance' => 'nullable|numeric|min:0',
            'entrance_balance' => 'nullable|numeric|min:0',
            'h_savings_balance' => 'nullable|numeric|min:0',
            'investment_balance' => 'nullable|numeric|min:0',
            'digital_gold_balance' => 'nullable|numeric|min:0',
            'group_savings_balance' => 'nullable|numeric|min:0',
        ];
    }
}
