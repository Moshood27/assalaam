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

        // If it's a numeric value, it might be an Excel serial date
        if (is_numeric($value)) {
            try {
                // Excel dates are usually > 20000 (roughly 1954)
                // Unix timestamps for current years are > 1,000,000,000
                // If it's small, it's likely an Excel serial number
                if ($value < 1000000) {
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
