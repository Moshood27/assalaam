<?php

namespace App\Support;

use Carbon\Carbon;

class DurationHelper
{
    /**
     * Format a duration in days into a human-readable string (years, months, days).
     *
     * @param int|float $days
     * @return string
     */
    public static function format($days): string
    {
        $days = (int) $days;
        if ($days <= 0) {
            return "0 days";
        }

        // We use a fixed date and add days to it to use Carbon's diff
        // because months have different number of days.
        $startDate = Carbon::now();
        $endDate = $startDate->copy()->addDays($days);
        $diff = $startDate->diff($endDate);

        $parts = [];
        if ($diff->y > 0) {
            $parts[] = $diff->y . ' ' . ($diff->y > 1 ? 'years' : 'year');
        }
        if ($diff->m > 0) {
            $parts[] = $diff->m . ' ' . ($diff->m > 1 ? 'months' : 'month');
        }
        if ($diff->d > 0 || empty($parts)) {
            $parts[] = $diff->d . ' ' . ($diff->d > 1 ? 'days' : 'day');
        }

        return implode(', ', $parts);
    }

    /**
     * Get the allowed loan duration in months based on amount and date.
     *
     * @param float $amount
     * @param Carbon|null $date
     * @return int
     */
    public static function getLoanDuration(float $amount, ?Carbon $date = null): int
    {
        $date = $date ?: now();
        // The rule changes "Beginning from July, 2025"
        $isNewRule = $date->greaterThanOrEqualTo(Carbon::parse('2025-07-01'));

        if (!$isNewRule) {
            // Before July, 2025
            if ($amount <= 500000) {
                return 12;
            } elseif ($amount <= 1099000) {
                return 14;
            } else {
                return 16;
            }
        } else {
            // Beginning from July, 2025 till date
            if ($amount <= 1000000) {
                return 12;
            } elseif ($amount <= 2000000) {
                return 15;
            } else {
                return 18;
            }
        }
    }
}
