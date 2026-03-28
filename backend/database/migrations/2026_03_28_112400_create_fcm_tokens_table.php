<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('fcm_tokens')) {
            Schema::create('fcm_tokens', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('token', 255)->unique();
                $table->string('platform', 32)->nullable(); // android | ios | web
                $table->timestamp('last_seen_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'platform']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('fcm_tokens')) {
            Schema::dropIfExists('fcm_tokens');
        }
    }
};
