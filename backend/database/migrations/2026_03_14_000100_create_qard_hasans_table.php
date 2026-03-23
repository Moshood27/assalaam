<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('qard_hasans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('qard_id_string')->unique();
            $table->decimal('principal_amount', 15, 2);
            $table->integer('total_installments');
            $table->decimal('per_installment', 15, 2);
            $table->string('interval')->default('monthly');
            $table->decimal('admin_fee_flat', 15, 2)->default(0);
            $table->decimal('admin_fee_pct', 5, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qard_hasans');
    }
};
