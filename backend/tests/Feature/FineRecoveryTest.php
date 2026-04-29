<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FineRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_fine_increase_triggers_recovery_if_balance_exists(): void
    {
        // Create a user with balance
        $user = User::factory()->create([
            'balance' => 1000,
            'outstanding_fines' => 0,
        ]);

        // Manually increase outstanding fines (as an admin would)
        $user->increment('outstanding_fines', 500);

        // Refresh and check if it was deducted
        $user->refresh();

        $this->assertEquals(500, $user->balance, 'Balance should have been decreased by 500');
        $this->assertEquals(0, $user->outstanding_fines, 'Outstanding fines should have been cleared');
    }

    public function test_balance_increase_triggers_recovery(): void
    {
        // Create a user with outstanding fines but 0 balance
        $user = User::factory()->create([
            'balance' => 0,
            'outstanding_fines' => 500,
        ]);

        // Increase balance
        $user->increment('balance', 1000);

        // Refresh and check if it was deducted
        $user->refresh();

        $this->assertEquals(500, $user->balance, 'Balance should have been decreased by 500');
        $this->assertEquals(0, $user->outstanding_fines, 'Outstanding fines should have been cleared');
    }
}
