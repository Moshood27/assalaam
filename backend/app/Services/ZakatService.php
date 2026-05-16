<?php

namespace App\Services;

use App\Models\User;
use App\Models\Scheme;
use App\Services\GoldSilverPriceService;
use Carbon\Carbon;

class ZakatService
{
    protected $priceService;

    public function __construct(GoldSilverPriceService $priceService)
    {
        $this->priceService = $priceService;
    }

    public function getEstimate(User $user)
    {
        // Resolve scheme IDs for Savings, Shares and Digital Gold
        $schemes = Scheme::whereIn('name', ['Savings', 'Shares', 'Special Savings', 'Ordinary Savings', 'Share Capital', 'Digital Gold'])->pluck('id', 'name');

        // Gold market value (Sell price)
        $goldPrice = $this->priceService->getSellPrice();

        // Use the common helper to get base wealth
        $base = $user->zakatBaseWealth($goldPrice ?? 0);

        // Individual components for report (Optimized: Using cached balance columns)
        $savings = (float) ($user->ordinary_savings ?? 0) + (float) ($user->special_savings_balance ?? 0);
        $shares = (float) ($user->shares_capital ?? 0);

        $currentGoldValue = $goldPrice ? round($user->gold_balance * $goldPrice, 2) : 0;
        $walletBalance = (float) $user->balance;

        $nisab = (float) $this->priceService->getGoldNisab();
        $rate = (float) config('zakat.rate', 0.025);
        $lunarDays = (int) config('zakat.lunar_days', 354);
        $isRamadan = $this->priceService->isRamadan();
        $fitrAmount = (float) config('zakat.fitr_amount');

        $eligible = false;
        $crossedOn = $user->zakat_nisab_crossed_at;
        $eligibleOn = null;
        $daysSinceCrossed = 0;

        // If we don't have tracking data yet, fallback to the old cumulative contribution estimation
        if (!$crossedOn && $base >= $nisab) {
            // Optimization: Fetch and calculate. We limit to avoid memory issues at scale.
            // Persisting the result ensures this is a one-time calculation.
            $contribs = $user->contributions()
                ->where('status', 'success')
                ->whereIn('scheme_id', array_values($schemes->toArray()))
                ->orderBy('created_at', 'asc')
                ->get(['amount', 'created_at']);

            $running = 0.0;
            foreach ($contribs as $c) {
                $running += (float) $c->amount;
                if ($running >= $nisab) {
                    $crossedOn = $c->created_at;
                    break;
                }
            }

            if (!$crossedOn && $base >= $nisab) {
                $crossedOn = now();
            }

            // Persist the result so we don't re-calculate this expensive history scan
            if ($crossedOn) {
                $user->zakat_nisab_crossed_at = $crossedOn;
                $user->saveQuietly();
            }
        }

        if ($crossedOn) {
            $eligibleOn = $crossedOn->copy()->addDays($lunarDays);
            $daysSinceCrossed = (int) now()->diffInDays($crossedOn);
            $eligible = now()->greaterThanOrEqualTo($eligibleOn) && $base >= $nisab;
        }

        $zakatDue = round($base * $rate, 2);

        return [
            'user' => $user,
            'base' => $base,
            'savings' => $savings,
            'shares' => $shares,
            'gold_value' => $currentGoldValue,
            'wallet_balance' => $walletBalance,
            'nisab' => $nisab,
            'gold_price' => $goldPrice,
            'rate' => $rate,
            'eligible' => $eligible,
            'crossed_on' => $crossedOn,
            'eligible_on' => $eligibleOn,
            'days_since_crossed' => $daysSinceCrossed,
            'zakat_due' => $zakatDue,
            'is_ramadan' => $isRamadan,
            'fitr_amount' => $fitrAmount,
            'last_paid_at' => $user->zakat_last_paid_at,
        ];
    }
}
