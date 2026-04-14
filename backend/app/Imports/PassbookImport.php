<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Contribution;
use App\Models\Scheme;
use App\Models\WalletTransaction;
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
use Carbon\Carbon;

class PassbookImport implements OnEachRow, WithHeadingRow, WithValidation, WithChunkReading
{
    protected static $schemesCache = [];

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

        $schemeName = trim($data['scheme_name']);
        if (!isset(self::$schemesCache[$schemeName])) {
            self::$schemesCache[$schemeName] = Scheme::firstOrCreate(['name' => $schemeName]);
        }
        $scheme = self::$schemesCache[$schemeName];

        $year = (int) $data['year'];
        $months = [
            'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4,
            'may' => 5, 'june' => 6, 'july' => 7, 'august' => 8,
            'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12
        ];

        DB::transaction(function () use ($user, $scheme, $year, $months, $data, $schemeName) {

            // --- STEP 1: CLEANUP (Delete Demo Data for this Year/Scheme) ---

            $contributionsQuery = Contribution::where('user_id', $user->id)
                ->where('scheme_id', $scheme->id)
                ->whereYear('created_at', $year);

            // Clean up linked project investments first to satisfy foreign key constraint
            $contributionIds = $contributionsQuery->pluck('id');
            ProjectInvestment::whereIn('contribution_id', $contributionIds)->delete();

            $contributionsQuery->delete();

            if ($schemeName === 'Takaful') {
                TakafulContribution::where('user_id', $user->id)
                    ->where('period', 'like', "{$year}-%")
                    ->delete();

                TakafulPoolEntry::where('user_id', $user->id)
                    ->whereYear('created_at', $year)
                    ->delete();
            }

            // --- STEP 2: REBUILD (Insert Real Data from Excel) ---

            foreach ($months as $monthName => $monthNum) {
                $amount = (float) ($data[$monthName] ?? 0);

                // We only create records for months with actual payments
                if ($amount > 0) {
                    $createdDate = Carbon::create($year, $monthNum, 1, 12, 0, 0);

                    Contribution::create([
                        'user_id' => $user->id,
                        'scheme_id' => $scheme->id,
                        'amount' => $amount,
                        'status' => 'success',
                        'reference' => 'MIG-REC-' . strtoupper(substr($schemeName, 0, 3)) . '-' . Str::random(6),
                        'created_at' => $createdDate,
                    ]);

                    if ($schemeName === 'Takaful') {
                        $this->handleTakafulRebuild($user, $amount, $createdDate);
                    }
                }
            }

            // --- STEP 3: SYNC (Recalculate User Aggregate Column) ---
            $this->syncUserBalance($user, $schemeName);
        });
    }

    private function handleTakafulRebuild(User $user, float $amount, Carbon $date)
    {
        $period = $date->format('Y-m');

        TakafulContribution::create([
            'user_id' => $user->id,
            'period' => $period,
            'amount' => $amount,
            'status' => 'success',
            'reference' => 'MIG-REC-TAKF-' . Str::random(6),
            'meta' => ['description' => 'Account Reconciliation Rebuild'],
            'created_at' => $date,
        ]);

        TakafulPoolEntry::create([
            'user_id' => $user->id,
            'direction' => 'credit',
            'amount' => $amount,
            'reference' => 'MIG-REC-POOL-' . Str::random(6),
            'meta' => ['description' => 'Account Reconciliation Rebuild'],
            'created_at' => $date,
        ]);
    }

    private function syncUserBalance(User $user, string $schemeName)
    {
        $columnMap = [
            'Savings' => 'ordinary_savings',
            'Shares' => 'shares_capital',
            'Development' => 'development_fund_balance',
            'Outstanding Fines' => 'outstanding_fines',
            'Wallet Balance' => 'balance',
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
            'Digital Gold' => 'gold_balance',
            'Group Savings' => 'group_savings_balance',
            'Takaful' => 'takaful_balance',
        ];

        if (isset($columnMap[$schemeName])) {
            $column = $columnMap[$schemeName];

            // Re-calculate the TOTAL from the newly cleaned contributions
            $total = (float) Contribution::where('user_id', $user->id)
                ->whereHas('scheme', fn($q) => $q->where('name', $schemeName))
                ->where('status', 'success')
                ->sum('amount');

            // Force update the column to match the history exactly
            $user->forceFill([$column => $total])->save();

            // Create one master Audit Log for the reconciliation
            WalletTransaction::create([
                'user_id' => $user->id,
                'amount' => $total,
                'type' => 'credit',
                'source' => 'migration',
                'reference' => 'MIG-SYNC-' . Str::random(6),
                'meta' => ['description' => "Full Account Reconciliation for {$schemeName}"],
                'created_at' => now(),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'membership_no' => 'required|exists:users,membership_number',
            'scheme_name' => 'required|string',
            'year' => 'required|numeric',
            'january' => 'nullable|numeric',
            'february' => 'nullable|numeric',
            'march' => 'nullable|numeric',
            'april' => 'nullable|numeric',
            'may' => 'nullable|numeric',
            'june' => 'nullable|numeric',
            'july' => 'nullable|numeric',
            'august' => 'nullable|numeric',
            'september' => 'nullable|numeric',
            'october' => 'nullable|numeric',
            'november' => 'nullable|numeric',
            'december' => 'nullable|numeric',
        ];
    }
}
