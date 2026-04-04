<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('takaful_pool_entries', function (Blueprint $table) {
            $table->id();
            $table->enum('direction', ['credit', 'debit']);
            $table->decimal('amount', 15, 2);
            $table->string('reference')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('takaful_pool_entries');
    }
};
