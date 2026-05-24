<?php

namespace App\Support;

use App\Models\Setting;
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
        }

        // Try to get configurable rules for the current period (New Rule)
        $rulesJson = Setting::get('loan_duration_rules');
        if ($rulesJson) {
            $rules = is_array($rulesJson) ? $rulesJson : json_decode($rulesJson, true);
            if (is_array($rules) && !empty($rules)) {
                // Sort rules: numeric max_amount first (ascending), then null (above)
                usort($rules, function ($a, $b) {
                    $maxA = $a['max_amount'] ?? null;
                    $maxB = $b['max_amount'] ?? null;
                    if ($maxA === null && $maxB === null) return 0;
                    if ($maxA === null) return 1;
                    if ($maxB === null) return -1;
                    return $maxA <=> $maxB;
                });

                foreach ($rules as $rule) {
                    $maxAmount = $rule['max_amount'] ?? null;
                    if ($maxAmount === null || $amount <= $maxAmount) {
                        return (int) $rule['duration'];
                    }
                }
            }
        }

        // Fallback to hardcoded New Rules
        if ($amount <= 1000000) {
            return 12;
        } elseif ($amount <= 2000000) {
            return 15;
        } else {
            return 18;
        }
    }
}
