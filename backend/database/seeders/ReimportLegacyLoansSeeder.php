<?php

namespace Database\Seeders;

use App\Models\QardHasan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReimportLegacyLoansSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Ensure legacy tables are staged (members, loan, etc.)
        $this->call(ImportOldSqlSeeder::class);

        // 2) Optionally purge safe-to-delete legacy loans first
        $shouldPurge = filter_var(env('REIMPORT_PURGE_OLD_LOANS', false), FILTER_VALIDATE_BOOL);
        if ($shouldPurge) {
            $deleted = 0;
            // Delete only loans that look like legacy imports (OLD-xxxxxx), have no repayments, and have not been paid
            $ids = QardHasan::query()
                ->where('qard_id_string', 'LIKE', 'OLD-%')
                ->where('paid_amount', '<=', 0)
                ->whereDoesntHave('repayments')
                ->pluck('id')
                ->all();

            if (!empty($ids)) {
                $deleted = QardHasan::whereIn('id', $ids)->delete();
            }

            Log::info("ReimportLegacyLoansSeeder: Purged {$deleted} legacy loans prior to re-import.");
        }

        // 3) Re-run the importers for loans and guarantors
        $this->call([
            LoansFromOldSeeder::class,
            LoanGuarantorsFromOldSeeder::class,
        ]);
    }
}
