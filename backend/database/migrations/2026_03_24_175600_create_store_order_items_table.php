<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('store_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_order_id');
            $table->unsignedBigInteger('product_id');
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 15, 2); // selling
            $table->decimal('unit_cost', 15, 2);  // cost
            $table->decimal('line_total', 15, 2); // unit_price * qty
            $table->decimal('line_cost', 15, 2);  // unit_cost * qty
            $table->decimal('line_profit', 15, 2);// line_total - line_cost
            $table->timestamps();

            $table->index('store_order_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_order_items');
    }
};
