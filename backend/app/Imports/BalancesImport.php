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

            // 1. Savings
            if ($savings = (float) ($data['savings_balance'] ?? 0)) {
                if (!$checkMigrated('System Migration Opening Balance for Savings')) {
                    $user->increment('ordinary_savings', $savings);
                    $this->processOpeningBalance($user, 'Savings', $savings);
                }
            }

            // 2. Shares
            if ($shares = (float) ($data['shares_balance'] ?? 0)) {
                if (!$checkMigrated('System Migration Opening Balance for Shares')) {
                    $user->increment('shares_capital', $shares);
                    $this->processOpeningBalance($user, 'Shares', $shares);
                }
            }

            // 3. Takaful
            if ($takaful = (float) ($data['takaful_balance'] ?? 0)) {
                // Takaful has its own check inside processTakafulOpening
                $this->processTakafulOpening($user, $takaful);
            }

            // 4. Development Fund
            if ($dev = (float) ($data['development_fund_balance'] ?? 0)) {
                if (!$checkMigrated('System Migration Opening Balance for Development')) {
                    $user->increment('development_fund_balance', $dev);
                    $this->processOpeningBalance($user, 'Development', $dev);
                }
            }

            // 5. Outstanding Fines
            if ($fines = (float) ($data['outstanding_fines'] ?? 0)) {
                if (!$checkMigrated('System Migration Opening Balance for Outstanding Fines')) {
                    $user->increment('outstanding_fines', $fines);
                    // Create a transaction to mark it as done
                    WalletTransaction::create([
                        'user_id' => $user->id,
                        'amount' => $fines,
                        'type' => 'debit', // Fines are usually debits, but here we are setting an outstanding balance
                        'source' => 'migration',
                        'reference' => 'MIG-FINE-OUT-' . Str::random(6),
                        'meta' => ['description' => 'System Migration Opening Balance for Outstanding Fines'],
                        'created_at' => $this->migrationDate,
                    ]);
                }
            }

            // 6. Wallet Balance
            if ($wallet = (float) ($data['wallet_balance'] ?? 0)) {
                if (!$checkMigrated('System Migration Opening Wallet Balance')) {
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
            }

            // 7. Building
            if ($building = (float) ($data['building_balance'] ?? 0)) {
                if (!$checkMigrated('System Migration Opening Balance for Building')) {
                    $user->increment('building_balance', $building);
                    $this->processOpeningBalance($user, 'Building', $building);
                }
            }

            // 8. AGM
            if ($agm = (float) ($data['agm_balance'] ?? 0)) {
                if (!$checkMigrated('System Migration Opening Balance for AGM')) {
                    $user->increment('agm_balance', $agm);
                    $this->processOpeningBalance($user, 'AGM', $agm);
                }
            }

            // 9. Loan Repayment
            if ($loanRepay = (float) ($data['loan_repayment_balance'] ?? 0)) {
                if (!$checkMigrated('System Migration Opening Balance for Loan Repayment')) {
                    $user->increment('loan_repayment_balance', $loanRepay);
                    $this->processOpeningBalance($user, 'Loan Repayment', $loanRepay);
                }
            }

            // 10. Fine (as a scheme/contribution)
            if ($fine = (float) ($data['fine_balance'] ?? 0)) {
                if (!$checkMigrated('System Migration Opening Balance for Fine')) {
                    $user->increment('fine_balance', $fine);
                    $this->processOpeningBalance($user, 'Fine', $fine);
                }
            }

            // 11. Welfare
            if ($welfare = (float) ($data['welfare_balance'] ?? 0)) {
                if (!$checkMigrated('System Migration Opening Balance for Welfare')) {
                    $user->increment('welfare_balance', $welfare);
                    $this->processOpeningBalance($user, 'Welfare', $welfare);
                }
            }

            // 12. Lateness
            if ($lateness = (float) ($data['lateness_balance'] ?? 0)) {
                if (!$checkMigrated('System Migration Opening Balance for Lateness')) {
                    $user->increment('lateness_balance', $lateness);
                    $this->processOpeningBalance($user, 'Lateness', $lateness);
                }
            }

            // 13. Stationery
            if ($stationery = (float) ($data['stationery_balance'] ?? 0)) {
                if (!$checkMigrated('System Migration Opening Balance for Stationery')) {
                    $user->increment('stationery_balance', $stationery);
                    $this->processOpeningBalance($user, 'Stationery', $stationery);
                }
            }

            // 14. Loan Form
            if ($loanForm = (float) ($data['loan_form_balance'] ?? 0)) {
                if (!$checkMigrated('System Migration Opening Balance for Loan Form')) {
                    $user->increment('loan_form_balance', $loanForm);
                    $this->processOpeningBalance($user, 'Loan Form', $loanForm);
                }
            }

            // 15. Others
            if ($others = (float) ($data['others_balance'] ?? 0)) {
                if (!$checkMigrated('System Migration Opening Balance for Others')) {
                    $user->increment('others_balance', $others);
                    $this->processOpeningBalance($user, 'Others', $others);
                }
            }

            // 16. ID Card
            if ($idCard = (float) ($data['id_card_balance'] ?? 0)) {
                if (!$checkMigrated('System Migration Opening Balance for ID Card')) {
                    $user->increment('id_card_balance', $idCard);
                    $this->processOpeningBalance($user, 'ID Card', $idCard);
                }
            }

            // 17. Emergency
            if ($emergency = (float) ($data['emergency_balance'] ?? 0)) {
                if (!$checkMigrated('System Migration Opening Balance for Emergency')) {
                    $user->increment('emergency_balance', $emergency);
                    $this->processOpeningBalance($user, 'Emergency', $emergency);
                }
            }

            // 18. Entrance
            if ($entrance = (float) ($data['entrance_balance'] ?? 0)) {
                if (!$checkMigrated('System Migration Opening Balance for Entrance')) {
                    $user->increment('entrance_balance', $entrance);
                    $this->processOpeningBalance($user, 'Entrance', $entrance);
                }
            }

            // 19. H Savings
            if ($hSavings = (float) ($data['h_savings_balance'] ?? 0)) {
                if (!$checkMigrated('System Migration Opening Balance for H Savings')) {
                    $user->increment('h_savings_balance', $hSavings);
                    $this->processOpeningBalance($user, 'H Savings', $hSavings);
                }
            }

            // 20. Investment
            if ($investment = (float) ($data['investment_balance'] ?? 0)) {
                if (!$checkMigrated('System Migration Opening Balance for Investment')) {
                    $user->increment('investment_balance', $investment);
                    $this->processOpeningBalance($user, 'Investment', $investment);
                }
            }

            // 21. Digital Gold (Weight in grams)
            if ($gold = (float) ($data['digital_gold_balance'] ?? 0)) {
                if (!$checkMigrated('System Migration Opening Gold Balance')) {
                    $user->increment('gold_balance', $gold);
                    WalletTransaction::create([
                        'user_id' => $user->id,
                        'amount' => 0, // It's gold, not naira
                        'type' => 'credit',
                        'source' => 'migration',
                        'reference' => 'MIG-GOLD-' . Str::random(6),
                        'meta' => [
                            'description' => 'System Migration Opening Gold Balance',
                            'gold_weight' => $gold
                        ],
                        'created_at' => $this->migrationDate,
                    ]);
                }
            }

            // 22. Group Savings
            if ($groupSavings = (float) ($data['group_savings_balance'] ?? 0)) {
                if (!$checkMigrated('System Migration Opening Balance for Group Savings')) {
                    $user->increment('group_savings_balance', $groupSavings);
                    $this->processOpeningBalance($user, 'Group Savings', $groupSavings);
                }
            }
        });
    }

    private function processOpeningBalance(User $user, string $schemeName, float $amount)
    {
        // Avoid duplicate migration if re-running
        $alreadyDone = WalletTransaction::where('user_id', $user->id)
            ->where('source', 'migration')
            ->where('meta->description', "System Migration Opening Balance for {$schemeName}")
            ->exists();

        if ($alreadyDone) {
            return;
        }

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
