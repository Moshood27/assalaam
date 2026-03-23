<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utility_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // airtime, data, electricity
            $table->string('network'); // airtel, mtn, glo, 9mobile
            $table->string('phone_number');
            $table->decimal('amount', 15, 2);
            $table->decimal('cost_price', 15, 2)->default(0); // What the Coop paid the API
            $table->decimal('profit', 15, 2)->default(0);     // Amount - Cost Price
            $table->string('reference')->unique();
            $table->string('status')->default('pending'); // pending, success, failed
            $table->json('provider_response')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utility_transactions');
    }
};
