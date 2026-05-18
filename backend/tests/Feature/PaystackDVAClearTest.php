<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserVirtualAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaystackDVAClearTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_clear_paystack_dva_via_api()
    {
        $user = User::factory()->create();
        $va = UserVirtualAccount::create([
            'user_id' => $user->id,
            'paystack_customer_code' => 'CUS_123',
            'dva_account_number' => '1234567890',
            'dva_bank_name' => 'Test Bank',
            'dva_account_name' => 'Test Account',
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->deleteJson('/api/virtual-account/paystack');

        $response->assertStatus(200);
        $this->assertDatabaseHas('user_virtual_accounts', [
            'user_id' => $user->id,
            'paystack_customer_code' => null,
            'dva_account_number' => null,
        ]);
    }

    public function test_can_clear_paystack_dva_via_command()
    {
        $user = User::factory()->create();
        UserVirtualAccount::create([
            'user_id' => $user->id,
            'paystack_customer_code' => 'CUS_123',
            'dva_account_number' => '1234567890',
            'dva_bank_name' => 'Test Bank',
            'dva_account_name' => 'Test Account',
        ]);

        $this->artisan('app:clear-paystack-dvas', ['user_id' => $user->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('user_virtual_accounts', [
            'user_id' => $user->id,
            'paystack_customer_code' => null,
            'dva_account_number' => null,
        ]);
    }
}
