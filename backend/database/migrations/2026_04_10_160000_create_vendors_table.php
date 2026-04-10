<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('owner_user_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('settlement_bank_name')->nullable();
            $table->string('settlement_bank_code')->nullable();
            $table->string('settlement_account_number')->nullable();
            $table->string('settlement_account_name')->nullable();
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('owner_user_id');
            $table->index('is_approved');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
