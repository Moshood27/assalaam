<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ImportOldSqlSeeder extends Seeder
{
    public function run(): void
    {
        $path = base_path('database' . DIRECTORY_SEPARATOR . 'old_databse.sql');
        if (!is_file($path)) {
            Log::warning('Old SQL dump not found at: ' . $path);
            return;
        }

        $targets = [
            'members',
            'loan',
            'investment_record_details',
            'units',
        ];

        foreach ($targets as $table) {
            if (!Schema::hasTable($table)) {
                // Legacy table not present at all — stage it from dump
                $this->importTable($path, $table);
                continue;
            }

            // If table exists but is empty, (re)import its data from the dump
            try {
                $hasRows = DB::table($table)->limit(1)->count() > 0;
                if (!$hasRows) {
                    $this->importTable($path, $table);
                }
            } catch (\Throwable $e) {
                // If counting fails for any reason, try importing to self-heal
                Log::warning("Could not inspect legacy table '{$table}': " . $e->getMessage() . ' — attempting import.');
                $this->importTable($path, $table);
            }
        }
    }

    private function importTable(string $filePath, string $table): void
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            Log::error('Unable to open old SQL dump: ' . $filePath);
            return;
        }

        $collecting = false;
        $buffer = '';
        $mode = null; // 'create' or 'insert'

        while (($line = fgets($handle)) !== false) {
            $trim = ltrim($line);

            // Start of a CREATE or INSERT for the target table (only if not already collecting)
            if (!$collecting) {
                if (preg_match('/^CREATE TABLE\s+`' . preg_quote($table, '/') . '`/i', $trim)) {
                    $collecting = true;
                    $mode = 'create';
                    $buffer = $line;
                    continue;
                }
                if (preg_match('/^INSERT INTO\s+`' . preg_quote($table, '/') . '`/i', $trim)) {
                    $collecting = true;
                    $mode = 'insert';
                    $buffer = $line;
                    continue;
                }
            } else {
                // Already collecting: keep appending until we reach a terminator
                $buffer .= $line;

                $rtrim = rtrim($line);
                $isTerminated = false;
                if ($mode === 'create') {
                    // CREATE statements in dumps usually end with a semicolon (may not be ");")
                    $isTerminated = str_ends_with($rtrim, ';');
                } elseif ($mode === 'insert') {
                    // INSERT ... VALUES (...) may end with ");" or just ";" depending on dump format
                    $isTerminated = str_ends_with($rtrim, ');') || str_ends_with($rtrim, ';');
                }

                if ($isTerminated) {
                    try {
                        DB::unprepared($buffer);
                    } catch (\Throwable $e) {
                        // Include a small preview to aid debugging but avoid logging the whole huge SQL
                        $preview = substr(preg_replace('/\s+/', ' ', $buffer), 0, 300);
                        Log::error("Failed executing old SQL for table {$table}: " . $e->getMessage() . " | SQL: " . $preview . '...');
                    }
                    // Reset for next block
                    $collecting = false;
                    $buffer = '';
                    $mode = null;
                }
            }
        }

        // Flush any remaining buffer at EOF
        if ($collecting && $buffer !== '') {
            try {
                DB::unprepared($buffer);
            } catch (\Throwable $e) {
                $preview = substr(preg_replace('/\s+/', ' ', $buffer), 0, 300);
                Log::error("Failed executing old SQL at EOF for table {$table}: " . $e->getMessage() . " | SQL: " . $preview . '...');
            }
        }

        fclose($handle);
    }
}
