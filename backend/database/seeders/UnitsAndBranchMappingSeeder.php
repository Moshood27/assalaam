<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnitsAndBranchMappingSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure legacy units table exists
        if (!DB::getSchemaBuilder()->hasTable('units')) {
            Log::warning('Old units table not found. Run ImportOldSqlSeeder first.');
            return;
        }
        if (!DB::getSchemaBuilder()->hasTable('members')) {
            Log::warning('Old members table not found. Run ImportOldSqlSeeder first.');
            return;
        }

        // Build mapping: old units.id => new branches.id (by description/name)
        $unitToBranch = [];
        DB::table('units')->orderBy('id')->chunk(500, function ($units) use (&$unitToBranch) {
            foreach ($units as $u) {
                $name = trim((string)($u->description ?? 'Unit ' . $u->id));
                if ($name === '') {
                    $name = 'Unit ' . $u->id;
                }
                $branch = Branch::updateOrCreate(['name' => $name], []);
                $unitToBranch[(int)$u->id] = $branch->id;
            }
        });

        // Assign users.branch_id and users.is_defaulter based on legacy members
        DB::table('members')->orderBy('id')->chunk(1000, function ($rows) use ($unitToBranch) {
            foreach ($rows as $m) {
                $memberno = (string)($m->memberno ?? '');
                if ($memberno === '') continue;
                $user = User::where('membership_number', $memberno)->first();
                if (!$user) continue;

                $updates = [];

                // branch mapping
                $legacyUnitId = (int)($m->unitid ?? 0);
                if ($legacyUnitId && isset($unitToBranch[$legacyUnitId])) {
                    if ($user->branch_id !== $unitToBranch[$legacyUnitId]) {
                        $updates['branch_id'] = $unitToBranch[$legacyUnitId];
                    }
                }

                // defaulter flag mapping (legacy is_default: 1 means defaulter)
                if (property_exists($m, 'is_default')) {
                    $isDef = (int)$m->is_default === 1;
                    if ((bool)$user->is_defaulter !== $isDef) {
                        $updates['is_defaulter'] = $isDef;
                    }
                }

                if (!empty($updates)) {
                    $user->fill($updates);
                    $user->save();
                }
            }
        });
    }
}
