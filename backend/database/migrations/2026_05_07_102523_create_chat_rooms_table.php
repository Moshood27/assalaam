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
        Schema::create('chat_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('type')->default('private'); // private, group, support, board, audit, project
            $table->text('description')->nullable();
            $table->string('avatar')->nullable();
            $table->foreignId('creator_id')->nullable()->constrained('users')->onDelete('set null');
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('last_message_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_rooms');
    }
};
