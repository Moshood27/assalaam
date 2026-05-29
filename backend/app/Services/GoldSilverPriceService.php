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
     * Get Gold Nisab specifically (85g)
     */
    public function getGoldNisab()
    {
        $goldPricePerGram = $this->getGoldPrice();
        if ($goldPricePerGram) {
            return $goldPricePerGram * config('zakat.nisab_gold_grams', 85);
        }
        return config('zakat.nisab_ngn');
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
     * Get comprehensive gold price data including USD and NGN
     */
    public function getGoldPriceData()
    {
        $currency = config('zakat.goldapi_currency', 'USD');
        $goldPrice = $this->getGoldPrice(); // This is already NGN if currency is USD

        if (!$goldPrice) return null;

        $rate = 1.0;
        $priceUsd = $goldPrice;

        if ($currency === 'USD') {
            $rate = $this->getUsdNgnRate();
            $priceUsd = $goldPrice / $rate;
        }

        $spread = config('zakat.gold_spread', 0.01) / 2;

        return [
            'base_price_ngn' => $goldPrice,
            'base_price_usd' => $priceUsd,
            'exchange_rate' => $rate,
            'buy_price_ngn' => $goldPrice * (1 + $spread),
            'sell_price_ngn' => $goldPrice * (1 - $spread),
            'currency' => $currency
        ];
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
     * Get the USD to NGN exchange rate.
     */
    public function getUsdNgnRate()
    {
        $rate = config('zakat.usd_ngn_rate', 'auto');

        if (is_numeric($rate)) {
            return (float) $rate;
        }

        return Cache::remember('usd_ngn_exchange_rate', now()->addHours(12), function () {
            try {
                // Using a free, no-key-required exchange rate API
                $response = Http::timeout(10)->get('https://open.er-api.com/v6/latest/USD');
                if ($response->successful()) {
                    return (float) $response->json('rates.NGN', 1500.0);
                }
            } catch (\Exception $e) {
                Log::error('Failed to fetch USD/NGN exchange rate: ' . $e->getMessage());
            }

            // Fallback rate if API fails
            return 1500.0;
        });
    }

    /**
     * Get price per gram for a given symbol (XAU or XAG)
     */
    public function getPrice($symbol)
    {
        // Check if API key was marked invalid
        $isInvalid = Cache::get('gold_api_key_v1_invalid', false);

        if (!$this->apiKey || $isInvalid) {
            // Mock price for development if no API key or it's known invalid
            $mockBase = $symbol === 'XAU' ? 85000 : 1200;
            return $mockBase;
        }

        try {
            $currency = config('zakat.goldapi_currency', 'USD');
            $response = Http::withHeaders([
                'x-access-token' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->timeout(10)->get("{$this->baseUrl}/{$symbol}/{$currency}");

            if ($response->successful()) {
                $price = $response->json('price_gram_24k') ?? ($response->json('price') ? $response->json('price') / 31.1034768 : null);
                if ($price) {
                    if ($currency === 'USD') {
                        $rate = $this->getUsdNgnRate();
                        $price = $price * $rate;
                    }
                    return $price;
                }
            }

            $body = $response->body();
            if (str_contains($body, 'Invalid API Key')) {
                Log::error("GoldAPI Error: Invalid API Key. Please check GOLDAPI_KEY in .env. Falling back to mock prices.");
                // Cache the invalid status for 24 hours to avoid repeated failed calls
                Cache::put('gold_api_key_v1_invalid', true, now()->addDay());
            } elseif (str_contains($body, 'quota exceeded')) {
                Log::warning("GoldAPI Quota Exceeded. Falling back to mock prices.");
                // Cache the invalid status for 6 hours
                Cache::put('gold_api_key_v1_invalid', true, now()->addHours(6));
            } else {
                Log::warning("Failed to fetch price for {$symbol}: " . $body);
            }
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

            // Check if API key was marked invalid
            $isInvalid = Cache::get('gold_api_key_v1_invalid', false);

            for ($i = $days; $i >= 0; $i--) {
                $date = $today->copy()->subDays($i);
                $formattedDate = $date->format('Ymd');
                $price = null;

                if ($this->apiKey && !$isInvalid) {
                    try {
                        $currency = config('zakat.goldapi_currency', 'USD');
                        $response = Http::withHeaders([
                            'x-access-token' => $this->apiKey,
                        ])->timeout(5)->get("{$this->baseUrl}/{$symbol}/{$currency}/{$formattedDate}");

                        if ($response->successful()) {
                            $price = $response->json('price_gram_24k') ?? ($response->json('price') ? $response->json('price') / 31.1034768 : null);
                            if ($price && $currency === 'USD') {
                                $rate = $this->getUsdNgnRate();
                                $price = $price * $rate;
                            }
                        } else if (str_contains($response->body(), 'Invalid API Key')) {
                            Log::error("GoldAPI History Error: Invalid API Key. Please check GOLDAPI_KEY in .env.");
                            Cache::put('gold_api_key_v1_invalid', true, now()->addDay());
                            $isInvalid = true; // Avoid checking again for subsequent dates in this loop
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
                    $variation = (rand(-200, 200) / 10000); // Â±2%
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
