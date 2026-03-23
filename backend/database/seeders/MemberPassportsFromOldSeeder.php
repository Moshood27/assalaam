<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class MemberPassportsFromOldSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure legacy members table was staged
        if (!DB::getSchemaBuilder()->hasTable('members')) {
            Log::warning('MemberPassportsFromOldSeeder: legacy members table not found. Run ImportOldSqlSeeder first.');
            return;
        }

        $uploadDir = public_path('upload');
        if (!is_dir($uploadDir)) {
            Log::warning('MemberPassportsFromOldSeeder: public/upload directory not found at ' . $uploadDir);
            return;
        }

        // Build a case-insensitive map of available files in upload directory
        $fileMap = [];
        foreach (File::files($uploadDir) as $file) {
            $fileMap[strtolower($file->getFilename())] = 'upload/' . $file->getFilename();
        }

        $total = 0;
        $updated = 0;
        $missingFile = 0;
        $missingUser = 0;

        DB::table('members')
            ->whereNotNull('passport')
            ->where('passport', '!=', '')
            ->orderBy('id')
            ->chunk(1000, function ($rows) use (&$fileMap, &$total, &$updated, &$missingFile, &$missingUser) {
                foreach ($rows as $row) {
                    $total++;

                    $membership = $row->memberno ?: null;
                    if (!$membership) {
                        continue;
                    }

                    $passport = trim((string) $row->passport);
                    if ($passport === '') {
                        continue;
                    }

                    $key = strtolower(basename($passport));
                    $relPath = $fileMap[$key] ?? null;

                    if (!$relPath) {
                        $missingFile++;
                        continue;
                    }

                    $affected = DB::table('users')
                        ->where('membership_number', (string) $membership)
                        ->update(['passport_path' => $relPath]);

                    if ($affected > 0) {
                        $updated++;
                    } else {
                        $missingUser++;
                    }
                }
            });

        Log::info("MemberPassportsFromOldSeeder: processed={$total}, updated={$updated}, missingFile={$missingFile}, missingUser={$missingUser}");
    }
}
