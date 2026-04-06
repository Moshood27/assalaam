<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\Models\User;

class UtilityMerchantVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_merchant_fails_when_provider_returns_na_customer_name()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        // Mocking ClubKonnect/Nellobyte response
        Http::fake([
            '*' => Http::response([
                'customer_name' => 'N/A',
                'customer_address' => 'N/A',
                'status' => '100'
            ], 200)
        ]);

        $response = $this->postJson('/api/vtu/verify-merchant', [
            'serviceID' => 'eko-electric',
            'billersCode' => '10101010101',
            'type' => 'prepaid'
        ]);

        // It should NOT be successful
        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Verification failed');
    }

    public function test_verify_merchant_fails_when_provider_returns_invalid_customer_name()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        Http::fake([
            '*' => Http::response([
                'customer_name' => 'INVALID METER NUMBER',
                'status' => '200'
            ], 200)
        ]);

        $response = $this->postJson('/api/vtu/verify-merchant', [
            'serviceID' => 'eko-electric',
            'billersCode' => '10101010101',
            'type' => 'prepaid'
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Verification failed');
    }

    public function test_verify_merchant_succeeds_with_valid_name()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');

        Http::fake([
            '*' => Http::response([
                'customer_name' => 'JOHN DOE',
                'customer_address' => '123 Test St',
                'status' => '200'
            ], 200)
        ]);

        $response = $this->postJson('/api/vtu/verify-merchant', [
            'serviceID' => 'eko-electric',
            'billersCode' => '10101010101',
            'type' => 'prepaid'
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('customer_name', 'JOHN DOE');
    }
}
