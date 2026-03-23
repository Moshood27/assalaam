<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('qard_hasan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qard_hasan_id')->constrained('qard_hasans')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('reference')->unique();
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qard_hasan_repayments');
    }
};
