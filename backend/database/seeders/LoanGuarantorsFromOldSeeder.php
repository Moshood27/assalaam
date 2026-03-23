<?php

namespace Database\Seeders;

use App\Models\QardHasan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoanGuarantorsFromOldSeeder extends Seeder
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

        DB::table('loan')->orderBy('id')->chunk(500, function ($rows) {
            foreach ($rows as $row) {
                $qid = 'OLD-' . str_pad((string)$row->id, 6, '0', STR_PAD_LEFT);
                $loanModel = QardHasan::where('qard_id_string', $qid)->first();
                if (!$loanModel) {
                    continue; // skip if loan not imported (e.g., missing user)
                }

                $candidates = [
                    $row->guarantor_name ?? null,
                    $row->guarantor_name1 ?? null,
                    $row->guarantor_name2 ?? null,
                    $row->guarantor_name3 ?? null,
                ];

                $guarantorUserIds = [];

                foreach ($candidates as $val) {
                    $member = $this->resolveLegacyMember($val);
                    if (!$member) continue;

                    $user = User::where('membership_number', (string)$member->memberno)->first();
                    if ($user) {
                        $guarantorUserIds[] = $user->id;
                    }
                }

                if (!empty($guarantorUserIds)) {
                    $loanModel->guarantors()->syncWithoutDetaching(array_unique($guarantorUserIds));
                }
            }
        });
    }

    private function resolveLegacyMember($raw)
    {
        $v = trim((string)($raw ?? ''));
        if ($v === '' || $v === '0' || str_starts_with($v, '000000')) {
            return null;
        }

        // 1) Exact match on memberno
        $m = DB::table('members')->where('memberno', $v)->first();
        if ($m) return $m;

        // 2) If numeric, try legacy id
        if (ctype_digit($v)) {
            $m = DB::table('members')->where('id', (int)$v)->first();
            if ($m) return $m;
        }

        // 3) Fallback: try name match (case-insensitive, loose)
        $m = DB::table('members')->whereRaw('LOWER(membername) = ?', [mb_strtolower($v)])->first();
        if ($m) return $m;

        // 4) Fallback: partial like if reasonably long
        if (mb_strlen($v) >= 3) {
            $m = DB::table('members')->where('membername', 'LIKE', '%' . $v . '%')->first();
            if ($m) return $m;
        }

        return null;
    }
}
