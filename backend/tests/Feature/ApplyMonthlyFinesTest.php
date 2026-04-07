<?php

namespace Tests\Feature;

use App\Models\Contribution;
use App\Models\Scheme;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ApplyMonthlyFinesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_applies_fines_to_users_who_didnt_contribute()
    {
        // Set time to the 5th of current month
        Carbon::setTestNow(Carbon::create(2026, 5, 5));

        // Create Lateness scheme
        $scheme = Scheme::create([
            'name' => 'Lateness',
            'min_amount' => 200,
            'active' => true,
        ]);

        // Create a user who joined before last month
        $user = User::factory()->create([
            'membership_number' => 'MEM001',
            'is_admin' => false,
            'created_at' => Carbon::create(2026, 3, 1),
        ]);

        // Run the command
        Artisan::call('app:apply-monthly-fines');

        // Check if fine was added
        $this->assertDatabaseHas('contributions', [
            'user_id' => $user->id,
            'scheme_id' => $scheme->id,
            'amount' => 200,
            'status' => 'pending',
        ]);
    }

    public function test_it_doesnt_apply_fines_to_users_who_contributed()
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 5));

        $scheme = Scheme::create([
            'name' => 'Lateness',
            'min_amount' => 200,
            'active' => true,
        ]);

        $savingsScheme = Scheme::create([
            'name' => 'Savings',
            'active' => true,
        ]);

        $user = User::factory()->create([
            'membership_number' => 'MEM001',
            'is_admin' => false,
            'created_at' => Carbon::create(2026, 3, 1),
        ]);

        // Add a contribution in the previous month (April)
        Contribution::create([
            'user_id' => $user->id,
            'scheme_id' => $savingsScheme->id,
            'amount' => 1000,
            'reference' => 'TEST-REF-1',
            'status' => 'success',
            'created_at' => Carbon::create(2026, 4, 15),
        ]);

        Artisan::call('app:apply-monthly-fines');

        // Check if fine was NOT added
        $this->assertDatabaseMissing('contributions', [
            'user_id' => $user->id,
            'scheme_id' => $scheme->id,
        ]);
    }

    public function test_it_doesnt_apply_fine_twice()
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 5));

        $scheme = Scheme::create([
            'name' => 'Lateness',
            'min_amount' => 200,
            'active' => true,
        ]);

        $user = User::factory()->create([
            'membership_number' => 'MEM001',
            'is_admin' => false,
            'created_at' => Carbon::create(2026, 3, 1),
        ]);

        // Run once
        Artisan::call('app:apply-monthly-fines');
        $this->assertEquals(1, Contribution::where('user_id', $user->id)->where('scheme_id', $scheme->id)->count());

        // Run again
        Artisan::call('app:apply-monthly-fines');
        $this->assertEquals(1, Contribution::where('user_id', $user->id)->where('scheme_id', $scheme->id)->count());
    }

    public function test_it_doesnt_fine_newly_joined_users()
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 5));

        $scheme = Scheme::create([
            'name' => 'Lateness',
            'min_amount' => 200,
            'active' => true,
        ]);

        // User who joined in the middle of last month (April)
        $user = User::factory()->create([
            'membership_number' => 'MEM002',
            'is_admin' => false,
            'created_at' => Carbon::create(2026, 4, 15),
        ]);

        Artisan::call('app:apply-monthly-fines');

        // Should NOT be fined because they haven't been a member for a full month yet
        // Based on the logic: 'created_at', '<', $startOfLastMonth (2026-04-01)
        $this->assertDatabaseMissing('contributions', [
            'user_id' => $user->id,
            'scheme_id' => $scheme->id,
        ]);
    }
}
