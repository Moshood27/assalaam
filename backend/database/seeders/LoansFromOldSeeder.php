<?php

namespace Database\Seeders;

use App\Models\QardHasan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class LoansFromOldSeeder extends Seeder
{
    public function run(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('loan')) {
            Log::warning('Old loan table not found. Run ImportOldSqlSeeder first.');
            return;
        }
        if (!DB::getSchemaBuilder()->hasTable('members')) {
            Log::warning('Old members table not found. Run ImportOldSqlSeeder first.');
            return;
        }

        DB::table('loan')->orderBy('id')->chunk(1000, function ($rows) {
            foreach ($rows as $row) {
                $user = $this->resolveUserFromLoanRow($row);
                if (!$user) {
                    continue; // skip if no matching user
                }

                // Safely map legacy columns with fallbacks to avoid undefined property notices
                $principal = (float)($this->firstProp($row, [
                    'appliedamount', // some dumps
                    'loan_amount',   // actual in included dump
                    'amount', 'principal', 'approved_amount'
                ]) ?? 0);

                $totalInstallments = (int)($this->firstProp($row, [
                    'repaymentmonths', // some dumps
                    'loan_term',       // actual in included dump
                    'repayment_months', 'tenure_months', 'months'
                ]) ?? 0);

                $perInstallment = (float)($this->firstProp($row, [
                    'amount_to_repay_monthly', // some dumps
                    'monthly_payment',         // actual in included dump
                    'per_installment', 'monthly_amount'
                ]) ?? 0);

                if ($totalInstallments <= 0 && $perInstallment > 0 && $principal > 0) {
                    $totalInstallments = max(1, (int) round($principal / $perInstallment));
                }
                if ($perInstallment <= 0 && $totalInstallments > 0) {
                    $perInstallment = round($principal / $totalInstallments, 2);
                }

                $rawStatus = (string)($this->firstProp($row, ['status']) ?? '');
                $status = strtolower($rawStatus);
                $status = match ($status) {
                    'approved' => 'active',
                    'pending' => 'pending',
                    'completed', 'repaid' => 'completed',
                    'cancelled', 'rejected' => 'cancelled',
                    default => 'active',
                };

                $qid = 'OLD-' . str_pad((string)$row->id, 6, '0', STR_PAD_LEFT);

                $releaseDate = $this->firstProp($row, ['releasedate', 'release_date', 'disbursed_at', 'created_at']);
                $ts = $this->normalizeLegacyDate($releaseDate ?? null);

                QardHasan::updateOrCreate(
                    ['qard_id_string' => $qid],
                    [
                        'user_id' => $user->id,
                        'principal_amount' => $principal,
                        'total_installments' => $totalInstallments ?: 1,
                        'per_installment' => $perInstallment ?: $principal,
                        'interval' => 'monthly',
                        'admin_fee_flat' => 0,
                        'admin_fee_pct' => 0,
                        'paid_amount' => 0,
                        'status' => $status,
                        'created_at' => $ts,
                        'updated_at' => $ts,
                    ]
                );
            }
        });
    }

    private function resolveUserFromLoanRow($row): ?User
    {
        $memberno = trim((string)($row->memberno ?? ''));
        $memberid = (int)($row->memberid ?? 0);

        // 1) Direct match on users.membership_number == loan.memberno
        if ($memberno !== '') {
            $u = User::where('membership_number', $memberno)->first();
            if ($u) return $u;
        }

        // 2) If memberno is numeric, it may actually be legacy members.id
        if ($memberno !== '' && ctype_digit($memberno)) {
            $m = DB::table('members')->where('id', (int)$memberno)->first();
            if ($m && !empty($m->memberno)) {
                $u = User::where('membership_number', (string)$m->memberno)->first();
                if ($u) return $u;
            }
        }

        // 3) Resolve via legacy members.id from loan.memberid
        if ($memberid > 0) {
            $m = DB::table('members')->where('id', $memberid)->first();
            if ($m && !empty($m->memberno)) {
                $u = User::where('membership_number', (string)$m->memberno)->first();
                if ($u) return $u;
            }
        }

        // 4) Fallback: find legacy member by memberno equal to loan.memberno (if non-numeric formatting differences)
        if ($memberno !== '') {
            $m = DB::table('members')->where('memberno', $memberno)->first();
            if ($m && !empty($m->memberno)) {
                $u = User::where('membership_number', (string)$m->memberno)->first();
                if ($u) return $u;
            }
        }

        return null;
    }

    private function firstProp($row, array $keys)
    {
        foreach ($keys as $k) {
            if (is_object($row) && property_exists($row, $k)) {
                $val = $row->$k;
                if ($val !== null && $val !== '') return $val;
            }
        }
        return null;
    }

    private function normalizeLegacyDate($date): string
    {
        if (empty($date) || $date === '0000-00-00') {
            return now()->format('Y-m-d H:i:s');
        }
        try {
            $str = (string)$date;
            $c = Carbon::parse($str);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) {
                $c = $c->startOfDay();
            }
            // Guard lower bound for MySQL TIMESTAMP (00:00:00 can be invalid)
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
