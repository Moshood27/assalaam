<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GoldSilverPriceService
{
    protected $apiKey;
    protected $baseUrl = 'https://www.goldapi.io/api';

    public function __construct()
    {
        $this->apiKey = config('zakat.goldapi_key');
    }

    /**
     * Get the current Nisab based on Gold and Silver prices.
     * Usually the lower of the two is used for Nisab.
     */
    public function getDynamicNisab()
    {
        if (!$this->apiKey) {
            return config('zakat.nisab_ngn');
        }

        return Cache::remember('zakat_dynamic_nisab', now()->addHours(12), function () {
            try {
                $goldPricePerGram = $this->getPrice('XAU');
                $silverPricePerGram = $this->getPrice('XAG');

                if ($goldPricePerGram && $silverPricePerGram) {
                    $goldNisab = $goldPricePerGram * config('zakat.nisab_gold_grams');
                    $silverNisab = $silverPricePerGram * config('zakat.nisab_silver_grams');

                    // Standard practice is to use the silver nisab as it's lower,
                    // making more people eligible for Zakat (benefit of the poor).
                    // But some scholars prefer gold. Let's return the minimum or
                    // let the config decide. Here we'll return the silver one as it's common.
                    return min($goldNisab, $silverNisab);
                }
            } catch (\Exception $e) {
                Log::error('Failed to fetch Gold/Silver prices: ' . $e->getMessage());
            }

            return config('zakat.nisab_ngn');
        });
    }

    /**
     * Get price per gram for a given symbol (XAU or XAG)
     */
    public function getPrice($symbol)
    {
        $response = Http::withHeaders([
            'x-access-token' => $this->apiKey,
            'Content-Type' => 'application/json'
        ])->get("{$this->baseUrl}/{$symbol}/NGN");

        if ($response->successful()) {
            return $response->json('price_gram-24k') ?? $response->json('price') / 31.1035; // Convert oz to gram if needed
        }

        Log::warning("Failed to fetch price for {$symbol}: " . $response->body());
        return null;
    }

    /**
     * Check if today is in Ramadan (Hijri month 9)
     */
    public function isRamadan()
    {
        return Cache::remember('is_ramadan', now()->addDay(), function () {
            try {
                $response = Http::get('https://api.aladhan.com/v1/gToH/' . now()->format('d-m-Y'));
                if ($response->successful()) {
                    $month = $response->json('data.hijri.month.number');
                    return (int)$month === 9;
                }
            } catch (\Exception $e) {
                Log::error('Failed to check Ramadan: ' . $e->getMessage());
            }
            return false;
        });
    }
}
