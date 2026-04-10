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
     * Get gold price per gram in NGN
     */
    public function getGoldPrice()
    {
        if (!$this->apiKey) {
            Log::warning("Gold API Key is missing. Returning null for gold price.");
            return null;
        }

        return Cache::remember('current_gold_price_ngn', now()->addMinutes(10), function () {
            return $this->getPrice('XAU');
        });
    }

    /**
     * Get buy price (with optional fee or spread)
     */
    public function getBuyPrice()
    {
        $base = $this->getGoldPrice();
        if (!$base) return null;

        // Buying is at a slightly higher price (spread)
        $spread = config('zakat.gold_spread', 0.01) / 2;
        return $base * (1 + $spread);
    }

    /**
     * Get sell price (with optional fee or spread)
     */
    public function getSellPrice()
    {
        $base = $this->getGoldPrice();
        if (!$base) return null;

        // Selling is at a slightly lower price (spread)
        $spread = config('zakat.gold_spread', 0.01) / 2;
        return $base * (1 - $spread);
    }

    /**
     * Get price per gram for a given symbol (XAU or XAG)
     */
    public function getPrice($symbol)
    {
        if (!$this->apiKey) {
            // Mock price for development if no API key
            return $symbol === 'XAU' ? 85000 : 1200;
        }

        try {
            $response = Http::withHeaders([
                'x-access-token' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->timeout(10)->get("{$this->baseUrl}/{$symbol}/NGN");

            if ($response->successful()) {
                return $response->json('price_gram-24k') ?? $response->json('price') / 31.1035; // Convert oz to gram if needed
            }

            Log::warning("Failed to fetch price for {$symbol}: " . $response->body());
        } catch (\Exception $e) {
            Log::error("Exception when fetching price for {$symbol}: " . $e->getMessage());
        }

        // Mock price as fallback
        return $symbol === 'XAU' ? 85000 : 1200;
    }

    /**
     * Get historical price data for the last X days.
     */
    public function getHistory($symbol = 'XAU', $days = 7)
    {
        return Cache::remember("gold_history_{$symbol}_{$days}", now()->addHours(6), function () use ($symbol, $days) {
            $history = [];
            $today = now();

            for ($i = $days; $i >= 0; $i--) {
                $date = $today->copy()->subDays($i);
                $formattedDate = $date->format('Ymd');
                $price = null;

                if ($this->apiKey) {
                    try {
                        $response = Http::withHeaders([
                            'x-access-token' => $this->apiKey,
                        ])->timeout(5)->get("{$this->baseUrl}/{$symbol}/NGN/{$formattedDate}");

                        if ($response->successful()) {
                            $price = $response->json('price_gram-24k') ?? $response->json('price') / 31.1035;
                        }
                    } catch (\Exception $e) {
                        Log::warning("Failed to fetch history for {$formattedDate}: " . $e->getMessage());
                    }
                }

                // If API fails or no API key, generate slightly randomized mock data based on current price
                if (!$price) {
                    $basePrice = $symbol === 'XAU' ? 85000 : 1200;
                    // Seed the randomizer with the date so it's consistent for the same date
                    srand(strtotime($date->format('Y-m-d')));
                    $variation = (rand(-200, 200) / 10000); // ±2%
                    $price = $basePrice * (1 + $variation);
                }

                $history[] = [
                    'date' => $date->format('Y-m-d'),
                    'price' => round($price, 2)
                ];
            }

            return $history;
        });
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
