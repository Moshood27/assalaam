<?php

namespace Tests\Feature;

use App\Console\Commands\SweepMurabahaInstallments;
use App\Models\StoreOrder;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class StoreMurabahaAutoDeductionTest extends TestCase
{
    use RefreshDatabase;

    public function test_auto_sweep_deducts_due_installment_when_balance_available(): void
    {
        $user = User::factory()->create([
            'balance' => 5000.00,
        ]);

        $order = StoreOrder::create([
            'user_id' => $user->id,
            'reference' => 'REF123',
            'total_amount' => 3000.00,
            'total_cost' => 2500.00,
            'total_profit' => 500.00,
            'status' => 'murabaha_pending',
            'meta' => [
                'financing' => [
                    'type' => 'murabaha',
                    'months' => 3,
                    'profit_rate' => 0.2,
                    'autopay_enabled' => true,
                    'schedule' => [
                        ['installment' => 1, 'due_date' => now()->subDay()->toDateString(), 'amount' => 1000.00, 'status' => 'pending'],
                        ['installment' => 2, 'due_date' => now()->addMonth()->toDateString(), 'amount' => 1000.00, 'status' => 'pending'],
                        ['installment' => 3, 'due_date' => now()->addMonths(2)->toDateString(), 'amount' => 1000.00, 'status' => 'pending'],
                    ],
                ],
            ],
        ]);

        $this->assertEquals(5000.00, (float)$user->fresh()->balance);

        // Run the sweep command
        Artisan::call('murabaha:sweep');

        $user->refresh();
        $order->refresh();

        $this->assertEquals(4000.00, (float)$user->balance, 'User balance should be debited by first installment');

        $meta = $order->meta;
        $this->assertEquals('murabaha_active', $order->status);
        $this->assertEquals('paid', strtolower((string)($meta['financing']['schedule'][0]['status'] ?? '')));

        $tx = WalletTransaction::where('user_id', $user->id)
            ->where('source', 'store_installment_auto')
            ->first();
        $this->assertNotNull($tx, 'Auto debit wallet transaction should be recorded');
        $this->assertEquals(1000.00, (float)$tx->amount);
    }
}
