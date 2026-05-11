<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Config;

class FlutterwaveWebhookTest extends TestCase
{
    public function test_flutterwave_webhook_invalid_signature()
    {
        Config::set('services.flutterwave.secret_hash', 'test_hash');

        $response = $this->postJson('/api/webhooks/flutterwave', [
            'event' => 'charge.completed',
            'data' => ['id' => 123, 'status' => 'successful']
        ], [
            'verif-hash' => 'wrong_hash'
        ]);

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Invalid Signature']);
    }

    public function test_flutterwave_webhook_missing_secret_hash_in_config()
    {
        Config::set('services.flutterwave.secret_hash', null);

        $response = $this->postJson('/api/webhooks/flutterwave', [
            'event' => 'charge.completed',
            'data' => ['id' => 123, 'status' => 'successful']
        ], [
            'verif-hash' => 'some_hash'
        ]);

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Invalid Signature']);
    }

    public function test_flutterwave_webhook_valid_signature()
    {
        Config::set('services.flutterwave.secret_hash', 'test_hash');
        Config::set('services.flutterwave.secret_key', 'test_secret_key');

        // Mock the verification call to Flutterwave
        \Illuminate\Support\Facades\Http::fake([
            'api.flutterwave.com/v3/transactions/*/verify' => \Illuminate\Support\Facades\Http::response([
                'status' => 'success',
                'data' => [
                    'status' => 'successful',
                    'id' => 123,
                    'tx_ref' => 'test_ref',
                    'amount' => 100,
                    'currency' => 'NGN'
                ]
            ], 200)
        ]);

        $response = $this->postJson('/api/webhooks/flutterwave', [
            'event' => 'charge.completed',
            'data' => [
                'id' => 123,
                'status' => 'successful',
                'tx_ref' => 'test_ref'
            ]
        ], [
            'verif-hash' => 'test_hash'
        ]);

        // It should NOT be 400
        $this->assertNotEquals(400, $response->getStatusCode());
    }
}
