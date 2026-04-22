<?php

namespace App\Imports\Concerns;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date;

trait HandlesExcelDates
{
    /**
     * Parse a date from Excel, handling both string formats and Excel serial numbers.
     *
     * @param mixed $value
     * @param Carbon|null $fallback
     * @return Carbon|null
     */
    protected function parseExcelDate($value, $fallback = null)
    {
        if (empty($value)) {
            return $fallback;
        }

        // Clean up strings
        if (is_string($value)) {
            $value = trim($value);
            if (empty($value) || strtolower($value) === 'null') {
                return $fallback;
            }

            // Explicitly handle DD/MM/YYYY or DD-MM-YYYY formats which are common
            // but can be misparsed by Carbon::parse as MM/DD/YYYY if slashes are used.
            if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $value, $matches)) {
                $day = (int)$matches[1];
                $month = (int)$matches[2];
                $year = (int)$matches[3];

                // If it looks like DD/MM/YYYY (where day > 12), or just assume it is DD/MM/YYYY
                // Since this is likely for a Nigerian/African context, DD/MM/YYYY is standard.
                try {
                    return Carbon::create($year, $month, $day, 0, 0, 0);
                } catch (\Exception $e) {
                    // Fall back if invalid date
                }
            }
        }

        // If it's a numeric value, it might be an Excel serial date or YYYYMMDD
        if (is_numeric($value)) {
            $numValue = (int)$value;

            // Check if it's in YYYYMMDD format (e.g. 20230512)
            if ($numValue >= 19000101 && $numValue <= 20991231) {
                try {
                    return Carbon::createFromFormat('Ymd', (string)$numValue)->startOfDay();
                } catch (\Exception $e) {
                    // Fall through if not a valid Ymd
                }
            }

            try {
                // Excel dates are usually < 100,000 (45058 is 2023-05-12)
                // Unix timestamps for current years (2020+) are > 1,500,000,000
                // This range covers all reasonable Excel dates and avoids confusion with small timestamps
                if ($numValue > 0 && $numValue < 2000000) {
                    return Carbon::instance(Date::excelToDateTimeObject($value));
                }
            } catch (\Exception $e) {
                // Fallback to normal parsing if it fails
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return $fallback;
        }
    }
}
