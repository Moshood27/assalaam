<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goal_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('savings_goal_id');
            $table->string('partner_name');
            $table->string('package')->nullable();
            $table->decimal('booking_amount', 14, 2);
            $table->decimal('commission_rate', 5, 4)->default(0.05); // e.g., 0.0500 = 5%
            $table->decimal('commission_amount', 14, 2)->default(0);
            $table->string('reference')->unique();
            $table->enum('status', ['booked', 'completed', 'cancelled'])->default('booked');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('savings_goal_id')->references('id')->on('savings_goals')->onDelete('cascade');
            $table->index(['user_id', 'savings_goal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goal_bookings');
    }
};
