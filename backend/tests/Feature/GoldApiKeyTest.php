<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\GoldSilverPriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class GoldApiKeyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_handles_invalid_api_key_gracefully()
    {
        // Set an API key in config
        config(['zakat.goldapi_key' => 'invalid-key']);

        // Mock GoldAPI to return Invalid API Key error
        Http::fake([
            'https://www.goldapi.io/api/XAU/NGN' => Http::response([
                'error' => 'Invalid API Key'
            ], 403),
        ]);

        $service = new GoldSilverPriceService();
        $price = $service->getPrice('XAU');

        // Should return mock price
        $this->assertEquals(85000, $price);

        // Should have cached the invalid status
        $this->assertTrue(Cache::has('gold_api_key_invalid'));

        // Subsequent calls should NOT trigger another Http request
        Http::assertSentCount(1);

        $price2 = $service->getPrice('XAU');
        $this->assertEquals(85000, $price2);

        // Count should still be 1
        Http::assertSentCount(1);
    }

    public function test_history_handles_invalid_api_key_gracefully()
    {
        config(['zakat.goldapi_key' => 'invalid-key']);

        Http::fake([
            'https://www.goldapi.io/api/XAU/NGN/*' => Http::response([
                'error' => 'Invalid API Key'
            ], 403),
        ]);

        $service = new GoldSilverPriceService();
        $history = $service->getHistory('XAU', 1);

        $this->assertCount(2, $history); // Today and yesterday
        $this->assertTrue(Cache::has('gold_api_key_invalid'));

        // Should only have attempted once even if history is for multiple days
        // Wait, in my implementation, I set $isInvalid = true inside the loop, so it should only send once.
        Http::assertSentCount(1);
    }
}
