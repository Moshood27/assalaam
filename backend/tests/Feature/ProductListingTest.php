<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_created_vendor_product_visibility(): void
    {
        // 1. Setup: Admin, Category, Vendor
        $admin = User::factory()->create(['is_admin' => true]);
        $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics', 'is_active' => true]);
        $vendorUser = User::factory()->create();
        $vendor = Vendor::create([
            'name' => 'Tech Hub',
            'owner_user_id' => $vendorUser->id,
            'is_active' => true,
            'is_approved' => true,
        ]);

        // 2. Create product as if via Admin (simulating Filament Resource defaults/behavior)
        // vendor_id is NOT NULL, so is_approved might default to false in Filament.
        $product = Product::create([
            'category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'name' => 'Smartphone',
            'description' => 'A smart phone',
            'cost_price' => 50000,
            'markup_percent' => 10,
            'stock_quantity' => 10,
            'track_stock' => true,
            'is_active' => true,
            'is_approved' => false, // Default if vendor is set in Filament
        ]);

        // 3. Check public API
        $response = $this->getJson('/api/products');
        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'), 'Product should NOT be visible when not approved');

        // 4. Approve product
        $product->update(['is_approved' => true, 'approved_at' => now(), 'approved_by_id' => $admin->id]);

        // 5. Check public API again
        $response = $this->getJson('/api/products');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'), 'Product should be visible when approved');
        $this->assertEquals('Tech Hub', $response->json('data.0.vendor.name'));
    }

    public function test_product_visibility_with_inactive_vendor(): void
    {
        $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics', 'is_active' => true]);
        $vendorUser = User::factory()->create();
        $vendor = Vendor::create([
            'name' => 'Tech Hub',
            'owner_user_id' => $vendorUser->id,
            'is_active' => false, // Inactive
            'is_approved' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'vendor_id' => $vendor->id,
            'name' => 'Smartphone',
            'is_active' => true,
            'is_approved' => true,
            'cost_price' => 50000,
        ]);

        $response = $this->getJson('/api/products');
        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'), 'Product should NOT be visible when vendor is inactive');
    }
}
