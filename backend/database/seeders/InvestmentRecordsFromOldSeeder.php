<?php

namespace Database\Seeders;

use App\Models\Contribution;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class InvestmentRecordsFromOldSeeder extends Seeder
{
    public function run(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('investment_record_details')) {
            Log::warning('Old investment_record_details table not found. Run ImportOldSqlSeeder first.');
            return;
        }

        // Map old columns to scheme names in the new system
        $map = [
            'investment' => 'Investment',
            'savings' => 'Savings',
            'shares' => 'Shares',
            'building' => 'Building',
            'dev' => 'Development',
            'agm' => 'AGM',
            'loanrepay' => 'Loan Repayment',
            'sav' => 'Sav',
            'wellfare' => 'Welfare',
            'lateness' => 'Lateness',
            'stationery' => 'Stationery',
            'loanform' => 'Loan Form',
            'others' => 'Others',
            'idcard' => 'ID Card',
            'emergency' => 'Emergency',
            'entrance' => 'Entrance',
            'h_savings' => 'H Savings',
            // 'fine' exists but may be separate from schemes; include it too
            'fine' => 'Fine',
        ];

        $schemeIds = Scheme::whereIn('name', array_values($map))
            ->pluck('id', 'name')
            ->toArray();

        // Greatly improve performance by batching rows per chunk and using a single upsert
        DB::disableQueryLog();

        DB::table('investment_record_details')->orderBy('id')->chunk(500, function ($rows) use ($map, $schemeIds) {
            $batch = [];
            $now = now()->format('Y-m-d H:i:s');

            foreach ($rows as $row) {
                $memberId = (int)$row->memberid;
                $paymentDate = $row->paymentdate ?: null;

                // Find user via old members table membership number
                $member = DB::table('members')->where('id', $memberId)->first();
                if (!$member) continue;
                $user = User::where('membership_number', (string)$member->memberno)->first();
                if (!$user) continue;

                $dt = $this->normalizeLegacyDate($paymentDate);

                foreach ($map as $col => $schemeName) {
                    $amount = (float)($row->$col ?? 0);
                    if ($amount <= 0) continue;

                    $schemeId = $schemeIds[$schemeName] ?? null;
                    if (!$schemeId) continue;

                    // Deterministic unique reference
                    $ref = 'OLDINV-' . $row->id . '-' . Str::upper($col);

                    $batch[] = [
                        'reference' => $ref,
                        'user_id' => $user->id,
                        'scheme_id' => $schemeId,
                        'amount' => $amount,
                        'status' => 'success',
                        'created_at' => $dt,
                        'updated_at' => $dt,
                    ];

                    // Flush in sub-batches to keep memory bounded
                    if (count($batch) >= 2000) {
                        DB::table('contributions')->upsert($batch, ['reference'], ['user_id','scheme_id','amount','status','created_at','updated_at']);
                        $batch = [];
                    }
                }
            }

            if (!empty($batch)) {
                DB::table('contributions')->upsert($batch, ['reference'], ['user_id','scheme_id','amount','status','created_at','updated_at']);
            }
        });
    }

    private function normalizeLegacyDate($date): string
    {
        // Treat empty, null, '0000-00-00' as now()
        if (empty($date) || $date === '0000-00-00') {
            return now()->format('Y-m-d H:i:s');
        }
        try {
            // If it's only a date (Y-m-d), append midnight; Carbon will handle
            $str = (string)$date;
            // Some dumps may include time already
            $c = Carbon::parse($str);
            // If original had no time, ensure we set midnight
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) {
                $c = $c->startOfDay();
            }
            // MySQL TIMESTAMP lower bound guard (00:00:00 can be invalid on some setups)
            $minTs = Carbon::create(1970, 1, 1, 0, 0, 1);
            if ($c->lessThan($minTs)) {
                $c = $minTs;
            }
            return $c->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return now()->format('Y-m-d H:i:s');
        }
    }
}
