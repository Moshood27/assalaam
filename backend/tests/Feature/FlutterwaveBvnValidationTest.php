<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FlutterwaveDvaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FlutterwaveBvnValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_virtual_account_fails_if_bvn_name_mismatch()
    {
        Config::set('services.flutterwave.secret_key', 'test_secret');
        Config::set('kyc.provider', 'dojah'); // anything but mock

        $user = User::factory()->create([
            'name' => 'John Doe',
            'bvn' => null,
            'bvn_verified_at' => null,
        ]);

        Http::fake([
            'api.flutterwave.com/v3/kyc/bvns/12345678901' => Http::response([
                'status' => 'success',
                'data' => [
                    'first_name' => 'Jane',
                    'last_name' => 'Smith',
                ]
            ], 200)
        ]);

        $service = new FlutterwaveDvaService();
        $result = $service->createVirtualAccount($user, '12345678901');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('BVN name mismatch', $result['message']);
    }

    public function test_create_virtual_account_succeeds_if_bvn_name_matches()
    {
        Config::set('services.flutterwave.secret_key', 'test_secret');
        Config::set('kyc.provider', 'dojah');

        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '08012345678',
        ]);

        Http::fake([
            'api.flutterwave.com/v3/kyc/bvns/12345678901' => Http::response([
                'status' => 'success',
                'data' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                ]
            ], 200),
            'api.flutterwave.com/v3/virtual-account-numbers' => Http::response([
                'status' => 'success',
                'data' => [
                    'account_number' => '1234567890',
                    'bank_name' => 'Test Bank',
                ]
            ], 200)
        ]);

        $service = new FlutterwaveDvaService();
        $result = $service->createVirtualAccount($user, '12345678901');

        $this->assertTrue($result['success']);
        $this->assertNotNull($user->fresh()->bvn_verified_at);
        $this->assertEquals('12345678901', $user->fresh()->bvn);
    }

    public function test_mock_provider_validation()
    {
        Config::set('kyc.provider', 'mock');
        Config::set('services.flutterwave.secret_key', 'test_secret');

        $user = User::factory()->create(['name' => 'John Doe']);

        $service = new FlutterwaveDvaService();

        // Odd digit fails in mock
        $result = $service->createVirtualAccount($user, '12345678901');
        $this->assertFalse($result['success']);

        // Even digit passes in mock
        Http::fake([
             'api.flutterwave.com/v3/virtual-account-numbers' => Http::response(['status' => 'success', 'data' => []])
        ]);
        $result = $service->createVirtualAccount($user, '12345678902');
        $this->assertTrue($result['success']);
    }
}
