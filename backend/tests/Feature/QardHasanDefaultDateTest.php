<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\QardHasan;
use App\Models\User;
use App\Services\AccountingReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QardHasanDefaultDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_period_of_default_handles_epoch_date()
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $loan = QardHasan::create([
            'user_id' => $user->id,
            'qard_id_string' => 'TEST-001',
            'principal_amount' => 1000,
            'paid_amount' => 0,
            'status' => 'defaulted',
            'defaulted_at' => '1970-01-01 00:00:00',
            'total_installments' => 10,
            'per_installment' => 100,
            'interval' => 'monthly',
        ]);

        $this->assertEquals('1970-01-01', $loan->defaulted_at->toDateString());

        $period = $loan->period_of_default;

        // The issue is that it returns "01/01/1970 (20565 days)" or similar
        // We want it to handle it gracefully.
        $this->assertStringNotContainsString('01/01/1970', $period);
        $this->assertEquals('None', $period);
    }

    public function test_loan_analysis_report_handles_epoch_date()
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $loan = QardHasan::create([
            'user_id' => $user->id,
            'qard_id_string' => 'TEST-002',
            'principal_amount' => 1000,
            'paid_amount' => 0,
            'status' => 'defaulted',
            'defaulted_at' => '1970-01-01 00:00:00',
            'total_installments' => 10,
            'per_installment' => 100,
            'interval' => 'monthly',
        ]);

        $service = new AccountingReportService();
        $report = $service->buildLoanAnalysisReport($branch->id, now()->toDateString());

        $this->assertNotEmpty($report['rows']);
        $this->assertEquals('None', $report['rows'][0]['period_of_default']);
    }
}
