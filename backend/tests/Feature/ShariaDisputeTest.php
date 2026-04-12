<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\StoreOrder;
use App\Models\StoreOrderItem;
use App\Models\ShariaDispute;
use App\Filament\Resources\ShariaDisputeResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;
use App\Filament\Resources\ShariaDisputeResource\Pages\EditShariaDispute;

class ShariaDisputeTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_load_edit_sharia_dispute_page()
    {
        $user = User::factory()->create(['is_admin' => true]);

        $order = StoreOrder::create([
            'user_id' => $user->id,
            'reference' => 'ORD-123',
            'total_amount' => 1000,
            'status' => 'pending',
        ]);

        StoreOrderItem::create([
            'store_order_id' => $order->id,
            'product_name' => 'Test Product',
            'quantity' => 1,
            'line_total' => 1000,
            'unit_price' => 1000,
        ]);

        $dispute = ShariaDispute::create([
            'user_id' => $user->id,
            'store_order_id' => $order->id,
            'reason' => 'Test reason',
            'description' => 'Test description',
            'status' => 'pending',
        ]);

        $this->actingAs($user);

        // This should trigger the error
        Livewire::test(EditShariaDispute::class, [
            'record' => $dispute->id,
        ])->assertStatus(200);
    }
}
