<?php

namespace Database\Seeders;

use App\Models\QardHasan;
use App\Models\QardHasanRepayment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class MigrateLoanRepaymentsSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure legacy staging tables are present
        if (!DB::getSchemaBuilder()->hasTable('investment_record_details')) {
            Log::warning('Legacy table investment_record_details not found. Run ImportOldSqlSeeder first.');
            return;
        }
        if (!DB::getSchemaBuilder()->hasTable('members')) {
            Log::warning('Legacy table members not found. Run ImportOldSqlSeeder first.');
            return;
        }

        DB::disableQueryLog();

        DB::table('investment_record_details')->orderBy('id')->chunk(1000, function ($rows) {
            foreach ($rows as $row) {
                $amount = (float)($row->loanrepay ?? 0);
                if ($amount <= 0) {
                    continue;
                }

                // Resolve the user from legacy members table via memberid -> members.memberno -> users.membership_number
                $memberId = (int)($row->memberid ?? 0);
                if ($memberId <= 0) continue;
                $member = DB::table('members')->where('id', $memberId)->first();
                if (!$member || empty($member->memberno)) continue;
                $user = User::where('membership_number', (string)$member->memberno)->first();
                if (!$user) continue;

                $paidAt = $this->normalizeLegacyDate($row->paymentdate ?? null);

                $loan = $this->resolvePlausibleLoanForUser($user->id, $paidAt);
                if (!$loan) {
                    // Try to locate the correct legacy loan and auto-import it if missing
                    $loan = $this->findOrImportLoanFromLegacy($user, $member, $paidAt);
                }
                if (!$loan) {
                    Log::info('No plausible loan found for repayment row', [
                        'legacy_row_id' => $row->id,
                        'user_id' => $user->id,
                        'membership_number' => $user->membership_number ?? null,
                        'amount' => $amount,
                        'paid_at' => $paidAt,
                    ]);
                    continue;
                }

                $reference = 'OLDREPAY-' . $row->id; // deterministic unique reference

                DB::transaction(function () use ($loan, $amount, $paidAt, $reference) {
                    // Idempotent create based on unique reference
                    $repayment = QardHasanRepayment::where('reference', $reference)->first();
                    if ($repayment) {
                        return; // already processed in prior run
                    }

                    QardHasanRepayment::create([
                        'qard_hasan_id' => $loan->id,
                        'amount' => $amount,
                        'reference' => $reference,
                        'status' => 'success',
                        'paid_at' => $paidAt,
                    ]);

                    // Update loan paid amount and status
                    $loan->increment('paid_amount', $amount);
                    $loan->refresh();
                    if ((float)$loan->paid_amount >= (float)$loan->principal_amount && $loan->status !== 'completed') {
                        $loan->update(['status' => 'completed']);
                    }
                });
            }
        });
    }

    private function resolvePlausibleLoanForUser(int $userId, string $paidAt)
    {
        // Prefer earliest incomplete loan started on/before the payment date
        $loan = QardHasan::where('user_id', $userId)
            ->whereIn('status', ['active', 'pending'])
            ->where('created_at', '<=', $paidAt)
            ->orderBy('created_at', 'asc')
            ->first();
        if ($loan) return $loan;

        // Next, any incomplete loan regardless of date
        $loan = QardHasan::where('user_id', $userId)
            ->whereIn('status', ['active', 'pending'])
            ->orderBy('created_at', 'asc')
            ->first();
        if ($loan) return $loan;

        // Finally, any non-cancelled loan
        $loan = QardHasan::where('user_id', $userId)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('created_at', 'asc')
            ->first();

        return $loan;
    }

    private function findOrImportLoanFromLegacy(User $user, $legacyMember, string $paidAt)
    {
        if (!DB::getSchemaBuilder()->hasTable('loan')) {
            return null;
        }

        // Build base query: loans for this member by either memberid or memberno
        $base = DB::table('loan')
            ->where(function ($q) use ($legacyMember) {
                $q->where('memberid', (int)($legacyMember->id ?? 0));
                if (!empty($legacyMember->memberno)) {
                    $q->orWhere('memberno', (string)$legacyMember->memberno);
                }
            });

        // Prefer the latest (closest) loan released on/before the repayment date and not cancelled/rejected
        $preferred = (clone $base)
            ->whereNotIn('status', ['Cancelled','Rejected','cancelled','rejected'])
            ->where(function ($q) use ($paidAt) {
                $q->whereNull('releasedate')->orWhere('releasedate', '<=', $paidAt);
            })
            ->orderBy('releasedate', 'desc')
            ->first();

        $legacyLoan = $preferred;

        if (!$legacyLoan) {
            // Any non-cancelled loan regardless of date (oldest first)
            $legacyLoan = (clone $base)
                ->whereNotIn('status', ['Cancelled','Rejected','cancelled','rejected'])
                ->orderBy('releasedate', 'asc')
                ->first();
        }
        if (!$legacyLoan) {
            // As last resort, any loan row
            $legacyLoan = (clone $base)
                ->orderBy('releasedate', 'asc')
                ->first();
        }
        if (!$legacyLoan) {
            return null;
        }

        $qid = 'OLD-' . str_pad((string)$legacyLoan->id, 6, '0', STR_PAD_LEFT);
        $loan = QardHasan::where('qard_id_string', $qid)->first();
        if ($loan) return $loan;

        // Map fields similar to LoansFromOldSeeder
        $principal = (float)($legacyLoan->appliedamount ?? 0);
        $totalInstallments = (int)($legacyLoan->repaymentmonths ?? 0);
        $perInstallment = (float)($legacyLoan->amount_to_repay_monthly ?? 0);
        if ($totalInstallments <= 0 && $perInstallment > 0 && $principal > 0) {
            $totalInstallments = max(1, (int) round($principal / $perInstallment));
        }
        if ($perInstallment <= 0 && $totalInstallments > 0) {
            $perInstallment = round($principal / $totalInstallments, 2);
        }

        $status = strtolower((string)($legacyLoan->status ?? ''));
        $status = match ($status) {
            'approved' => 'active',
            'pending' => 'pending',
            'completed', 'repaid' => 'completed',
            'cancelled', 'rejected' => 'cancelled',
            default => 'active',
        };

        $ts = $this->normalizeLegacyDate($legacyLoan->releasedate ?? null);

        // Create the missing loan idempotently
        $loan = QardHasan::updateOrCreate(
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

        return $loan;
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
            // Guard lower bound for MySQL TIMESTAMP
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
