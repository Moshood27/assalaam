<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agm_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('agm_sessions')->cascadeOnDelete();
            $table->string('position');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained('agm_candidates')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['session_id', 'position', 'user_id'], 'uniq_session_pos_user');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agm_votes');
    }
};
