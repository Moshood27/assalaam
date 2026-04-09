<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\QardHasan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_index_returns_success()
    {
        $user = User::factory()->create([
            'balance' => 1000,
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_with_active_loan()
    {
        $user = User::factory()->create([
            'balance' => 1000,
        ]);

        QardHasan::create([
            'user_id' => $user->id,
            'qard_id_string' => 'QH-001',
            'principal_amount' => 5000,
            'paid_amount' => 1000,
            'status' => 'active',
            'total_installments' => 5,
            'per_installment' => 1000,
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('kpis.loans', 4000.0);
    }
}
