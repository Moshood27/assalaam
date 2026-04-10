<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VendorPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_can_create_profile_and_list_products(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create profile
        $resp = $this->postJson('/api/vendor/profile', [
            'name' => 'Halal Home Appliances',
            'phone' => '08012345678',
            'address' => '12, Market Road',
            'description' => 'Trusted Muslim-owned vendor',
        ]);
        $resp->assertStatus(201);

        $vendor = Vendor::where('owner_user_id', $user->id)->firstOrFail();
        // Simulate admin approval
        $vendor->is_approved = true;
        $vendor->save();

        $cat = Category::create([
            'name' => 'Generators',
            'slug' => 'generators',
            'is_active' => true,
        ]);

        // Create a product under vendor
        $resp2 = $this->postJson('/api/vendor/products', [
            'category_id' => $cat->id,
            'name' => 'Sumec Firman SPG2900',
            'description' => '2.5kVA generator',
            'cost_price' => 120000.00,
            'markup_percent' => 15.0,
            'stock_quantity' => 5,
            'track_stock' => true,
            'is_active' => true,
        ]);
        $resp2->assertStatus(201);
        $productId = $resp2->json('product.id');

        // Simulate admin approval for the product
        \App\Models\Product::where('id', $productId)->update([
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by_id' => User::factory()->create(['is_admin' => true])->id,
        ]);

        // Public products should include vendor info
        $list = $this->getJson('/api/products');
        $list->assertStatus(200);
        $payload = $list->json('data');
        $this->assertNotEmpty($payload);
        $first = $payload[0];
        $this->assertArrayHasKey('vendor', $first);
        $this->assertEquals('Halal Home Appliances', $first['vendor']['name']);
    }
}
