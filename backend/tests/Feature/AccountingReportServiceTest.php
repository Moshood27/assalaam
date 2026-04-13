<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AccountingReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_trial_balance_executes_successfully()
    {
        // Ensure there's a user
        User::factory()->create([
            'ordinary_savings' => 1000,
            'shares_capital' => 500,
            'gold_balance' => 10,
        ]);

        $service = new AccountingReportService();

        // This should not throw a QueryException
        $result = $service->buildTrialBalance(null, now()->toDateString(), 85000);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('accounts', $result);
    }
}
