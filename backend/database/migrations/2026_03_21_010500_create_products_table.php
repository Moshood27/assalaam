<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('cost_price', 15, 2);
            $table->decimal('markup_percent', 5, 2)->default(10.00);
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed a few default products so the store isn't empty after migration
        DB::table('products')->insert([
            [
                'name' => '50kg Bag of Rice',
                'description' => 'Quality long-grain rice. Household essential.',
                'cost_price' => 55000.00,
                'markup_percent' => 8.00,
                'image_url' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '2.5kVA Generator',
                'description' => 'Reliable portable generator for home/office.',
                'cost_price' => 285000.00,
                'markup_percent' => 10.00,
                'image_url' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Smartphone (64GB)',
                'description' => 'Mid-range Android smartphone, 4GB RAM / 64GB storage.',
                'cost_price' => 165000.00,
                'markup_percent' => 7.50,
                'image_url' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
