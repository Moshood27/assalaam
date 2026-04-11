<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sharia_disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_order_id')->constrained()->cascadeOnDelete();
            $table->string('reason');
            $table->text('description')->nullable();
            $table->string('status')->default('pending'); // pending, mediation, resolved, rejected
            $table->text('mediation_notes')->nullable();
            $table->text('outcome_details')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sharia_disputes');
    }
};
