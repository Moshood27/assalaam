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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_room_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // sender
            $table->string('type')->default('text'); // text, image, file, voice_note, transaction, approval
            $table->text('body')->nullable();
            $table->string('attachment')->nullable();
            $table->string('attachment_name')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('chat_rooms', function (Blueprint $table) {
            $table->foreign('last_message_id')->references('id')->on('chat_messages')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_rooms', function (Blueprint $table) {
            $table->dropForeign(['last_message_id']);
        });
        Schema::dropIfExists('chat_messages');
    }
};
