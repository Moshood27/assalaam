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
                // Excel dates are usually < 100,000 (45058 is 2023)
                // Unix timestamps for current years are > 1,000,000,000
                if ($numValue < 2000000) {
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
