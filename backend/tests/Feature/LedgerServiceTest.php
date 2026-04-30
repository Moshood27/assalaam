<?php

namespace Tests\Feature;

use App\Models\LedgerAccount;
use App\Models\LedgerJournal;
use App\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LedgerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LedgerService();
    }

    public function test_can_record_balanced_journal_entry()
    {
        $cash = LedgerAccount::where('code', '1000')->first();
        $income = LedgerAccount::where('code', '4000')->first();

        $journal = $this->service->record([
            'date' => now(),
            'description' => 'Test Transaction',
        ], [
            ['ledger_account_id' => $cash->id, 'debit' => 100, 'credit' => 0],
            ['ledger_account_id' => $income->id, 'debit' => 0, 'credit' => 100],
        ]);

        $this->assertInstanceOf(LedgerJournal::class, $journal);
        $this->assertEquals(100, $cash->refresh()->balance);
        $this->assertEquals(100, $income->refresh()->balance);
    }

    public function test_cannot_record_unbalanced_journal_entry()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Journal entry is not balanced");

        $cash = LedgerAccount::where('code', '1000')->first();
        $income = LedgerAccount::where('code', '4000')->first();

        $this->service->record([
            'date' => now(),
        ], [
            ['ledger_account_id' => $cash->id, 'debit' => 100, 'credit' => 0],
            ['ledger_account_id' => $income->id, 'debit' => 0, 'credit' => 90], // Unbalanced
        ]);
    }

    public function test_can_record_by_code()
    {
        $journal = $this->service->recordByCode([
            'date' => now(),
        ], [
            ['code' => '1000', 'debit' => 500],
            ['code' => '2200', 'credit' => 500],
        ]);

        $this->assertInstanceOf(LedgerJournal::class, $journal);
        $this->assertEquals(500, $this->service->getBalance('1000'));
        $this->assertEquals(500, $this->service->getBalance('2200'));
    }
}
