<?php

namespace Tests\Feature;

use App\Models\SavingsGroup;
use App\Models\SavingsGroupMember;
use App\Models\Scheme;
use App\Models\User;
use App\Models\Contribution;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SavingsGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_automated_monthly_charge_works_correctly()
    {
        // 1. Setup - Create Scheme
        $scheme = Scheme::create([
            'name' => 'Group Savings',
            'active' => true,
        ]);

        // 2. Setup - Create Creator and Member
        $creator = User::factory()->create();
        $member = User::factory()->create(['balance' => 20000]);

        // 3. Setup - Create Savings Group
        $group = SavingsGroup::create([
            'name' => 'Hajj Group',
            'creator_id' => $creator->id,
            'monthly_contribution_amount' => 10000,
            'status' => 'active',
        ]);

        // 4. Setup - Add Member to Group
        SavingsGroupMember::create([
            'savings_group_id' => $group->id,
            'user_id' => $member->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        // 5. Action - Run Charge Command
        Artisan::call('savings-groups:charge');

        // 6. Verification - Contribution created
        $this->assertDatabaseHas('contributions', [
            'user_id' => $member->id,
            'savings_group_id' => $group->id,
            'scheme_id' => $scheme->id,
            'amount' => 10000,
            'status' => 'success',
        ]);

        // 7. Verification - Wallet balance decreased
        $this->assertEquals(10000, $member->fresh()->balance);

        // 8. Verification - Wallet transaction recorded
        $this->assertDatabaseHas('wallet_transactions', [
            'user_id' => $member->id,
            'type' => 'debit',
            'amount' => 10000,
            'source' => 'savings_group_contribution',
        ]);
    }

    public function test_it_does_not_charge_members_with_insufficient_balance()
    {
        $scheme = Scheme::create([
            'name' => 'Group Savings',
            'active' => true,
        ]);

        $creator = User::factory()->create();
        $member = User::factory()->create(['balance' => 5000]); // Less than 10000

        $group = SavingsGroup::create([
            'name' => 'Hajj Group',
            'creator_id' => $creator->id,
            'monthly_contribution_amount' => 10000,
            'status' => 'active',
        ]);

        SavingsGroupMember::create([
            'savings_group_id' => $group->id,
            'user_id' => $member->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        Artisan::call('savings-groups:charge');

        // Verification - NO Contribution created
        $this->assertDatabaseMissing('contributions', [
            'user_id' => $member->id,
            'savings_group_id' => $group->id,
        ]);

        // Verification - Wallet balance UNCHANGED
        $this->assertEquals(5000, $member->fresh()->balance);
    }

    public function test_it_does_not_charge_twice_for_same_period()
    {
        $scheme = Scheme::create([
            'name' => 'Group Savings',
            'active' => true,
        ]);

        $creator = User::factory()->create();
        $member = User::factory()->create(['balance' => 30000]);

        $group = SavingsGroup::create([
            'name' => 'Hajj Group',
            'creator_id' => $creator->id,
            'monthly_contribution_amount' => 10000,
            'status' => 'active',
        ]);

        SavingsGroupMember::create([
            'savings_group_id' => $group->id,
            'user_id' => $member->id,
            'status' => 'active',
            'joined_at' => now(),
        ]);

        // Run once
        Artisan::call('savings-groups:charge');
        $this->assertEquals(1, Contribution::where('user_id', $member->id)->count());
        $this->assertEquals(20000, $member->fresh()->balance);

        // Run again
        Artisan::call('savings-groups:charge');
        $this->assertEquals(1, Contribution::where('user_id', $member->id)->count());
        $this->assertEquals(20000, $member->fresh()->balance);
    }
}
