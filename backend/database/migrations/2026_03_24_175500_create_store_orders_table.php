<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('store_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('reference')->unique();
            $table->decimal('total_amount', 15, 2); // Sum of selling prices
            $table->decimal('total_cost', 15, 2);   // Sum of costs
            $table->decimal('total_profit', 15, 2); // total_amount - total_cost
            $table->string('status')->default('paid'); // for future: pending, paid, cancelled
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_orders');
    }
};
