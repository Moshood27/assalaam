<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FlutterwaveDvaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FlutterwaveRegenerateDvaTest extends TestCase
{
    use RefreshDatabase;

    public function test_assign_flutterwave_returns_existing_account_if_already_exists()
    {
        Config::set('services.flutterwave.secret_key', 'test_secret');
        Config::set('kyc.provider', 'mock');

        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '08012345678',
            'bvn' => '12345678902',
            'bvn_verified_at' => now(),
        ]);

        $user->virtualAccount()->create([
            'flw_dva_data' => [
                'account_number' => '1111111111',
                'bank_name' => 'Old Bank',
            ]
        ]);

        $this->actingAs($user);

        // This should not trigger a HTTP call because it returns existing
        Http::fake();

        $response = $this->postJson('/api/virtual-account/assign-flutterwave');

        $response->assertStatus(200);
        $response->assertJsonPath('flw_account_number', '1111111111');

        Http::assertNothingSent();
    }

    public function test_regenerate_flutterwave_creates_new_account_even_if_already_exists()
    {
        Config::set('services.flutterwave.secret_key', 'test_secret');
        Config::set('kyc.provider', 'mock');

        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '08012345678',
            'bvn' => '12345678902',
            'bvn_verified_at' => now(),
        ]);

        $user->virtualAccount()->create([
            'flw_dva_data' => [
                'account_number' => '1111111111',
                'bank_name' => 'Old Bank',
            ]
        ]);

        $this->actingAs($user);

        Http::fake([
            'api.flutterwave.com/v3/virtual-account-numbers' => Http::response([
                'status' => 'success',
                'data' => [
                    'account_number' => '2222222222',
                    'bank_name' => 'New Bank',
                    'account_name' => 'John Doe',
                    'bank_code' => '044',
                ]
            ], 200)
        ]);

        $response = $this->postJson('/api/virtual-account/regenerate-flutterwave');

        $response->assertStatus(200);
        $response->assertJsonPath('flw_account_number', '2222222222');
        $response->assertJsonPath('flw_bank_name', 'New Bank');

        $this->assertEquals('2222222222', $user->fresh()->flw_dva_account_number);
    }
}
