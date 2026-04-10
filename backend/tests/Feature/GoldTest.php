<?php

namespace Tests\Feature;

use App\Models\Scheme;
use App\Models\User;
use App\Services\GoldSilverPriceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class GoldTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure Digital Gold scheme exists
        Scheme::create([
            'name' => 'Digital Gold',
            'min_amount' => 5000,
            'active' => true
        ]);
    }

    public function test_can_get_gold_price()
    {
        $user = User::factory()->create();

        $this->mock(GoldSilverPriceService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getGoldPrice')->andReturn(35000.00);
            $mock->shouldReceive('getBuyPrice')->andReturn(35175.00); // 35000 * 1.005
            $mock->shouldReceive('getSellPrice')->andReturn(34825.00); // 35000 * 0.995
            $mock->shouldReceive('getHistory')->andReturn([
                ['date' => '2024-01-01', 'price' => 34000],
                ['date' => '2024-01-02', 'price' => 35000]
            ]);
        });

        $response = $this->actingAs($user)->getJson('/api/gold/price');

        $response->assertStatus(200)
            ->assertJson([
                'base_price' => 35000.00,
                'buy_price' => 35175.00,
                'sell_price' => 34825.00,
                'gold_balance' => 0,
                'naira_balance' => (float)$user->balance,
                'performance' => [
                    'avg_buy_price' => 0,
                    'total_profit_loss' => 0,
                    'roi_percent' => 0
                ],
                'zakat' => [
                    'nisab_grams' => 85,
                    'is_eligible' => false
                ],
                'price_history' => [
                    ['date' => '2024-01-01', 'price' => 34000],
                    ['date' => '2024-01-02', 'price' => 35000]
                ]
            ]);
    }

    public function test_can_export_history()
    {
        $user = User::factory()->create();
        $scheme = Scheme::where('name', 'Digital Gold')->first();

        \App\Models\Contribution::create([
            'user_id' => $user->id,
            'scheme_id' => $scheme->id,
            'amount' => 50000,
            'units' => 1.0,
            'status' => 'success',
            'reference' => 'TEST-1'
        ]);

        $response = $this->actingAs($user)->getJson('/api/gold/export');

        $response->assertStatus(200);
        $this->assertEquals('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('gold_transactions.csv', $response->headers->get('Content-Disposition'));
    }

    public function test_can_buy_gold()
    {
        $user = User::factory()->create([
            'balance' => 100000,
            'transaction_pin_hash' => bcrypt('1234')
        ]);

        $this->mock(GoldSilverPriceService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getBuyPrice')->andReturn(40000.00);
        });

        // Pay 40,000 NGN. Fee 0.5% is 200. Net is 39,800. Grams = 39,800 / 40,000 = 0.995
        $response = $this->actingAs($user)->postJson('/api/gold/buy', [
            'amount_naira' => 40000,
            'pin' => '1234'
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertEquals(60000, $user->balance);
        $this->assertEquals(0.995000, $user->gold_balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'type' => 'debit',
            'amount' => 40000,
            'source' => 'gold_purchase'
        ]);

        $this->assertDatabaseHas('contributions', [
            'user_id' => $user->id,
            'amount' => 40000,
            'units' => 0.995000
        ]);
    }

    public function test_can_sell_gold()
    {
        $user = User::factory()->create([
            'balance' => 0,
            'gold_balance' => 2.0,
            'transaction_pin_hash' => bcrypt('1234')
        ]);

        $this->mock(GoldSilverPriceService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getSellPrice')->andReturn(50000.00);
        });

        // Sell 1.0 gram at 50,000. Gross = 50,000. Fee 0.5% = 250. Net = 49,750.
        $response = $this->actingAs($user)->postJson('/api/gold/sell', [
            'grams' => 1.0,
            'pin' => '1234'
        ]);

        $response->assertStatus(200);

        $user->refresh();
        $this->assertEquals(49750, $user->balance);
        $this->assertEquals(1.0, $user->gold_balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $user->id,
            'type' => 'credit',
            'amount' => 49750,
            'source' => 'gold_sale'
        ]);

        $this->assertDatabaseHas('contributions', [
            'user_id' => $user->id,
            'amount' => -49750,
            'units' => -1.0
        ]);
    }

    public function test_cannot_buy_with_insufficient_balance()
    {
        $user = User::factory()->create([
            'balance' => 5000,
            'transaction_pin_hash' => bcrypt('1234')
        ]);

        $this->mock(GoldSilverPriceService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getBuyPrice')->andReturn(40000.00);
        });

        $response = $this->actingAs($user)->postJson('/api/gold/buy', [
            'amount_naira' => 10000,
            'pin' => '1234'
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Insufficient wallet balance.']);
    }

    public function test_cannot_sell_more_than_owned()
    {
        $user = User::factory()->create([
            'gold_balance' => 0.5,
            'transaction_pin_hash' => bcrypt('1234')
        ]);

        $this->mock(GoldSilverPriceService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getSellPrice')->andReturn(50000.00);
        });

        $response = $this->actingAs($user)->postJson('/api/gold/sell', [
            'grams' => 1.0,
            'pin' => '1234'
        ]);

        $response->assertStatus(400)
            ->assertJson(['message' => 'Insufficient gold balance.']);
    }

    public function test_can_get_history()
    {
        $user = User::factory()->create();
        $scheme = Scheme::where('name', 'Digital Gold')->first();

        \App\Models\Contribution::create([
            'user_id' => $user->id,
            'scheme_id' => $scheme->id,
            'amount' => 50000,
            'units' => 1.0,
            'status' => 'success',
            'reference' => 'TEST-1'
        ]);

        $response = $this->actingAs($user)->getJson('/api/gold/history');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}
