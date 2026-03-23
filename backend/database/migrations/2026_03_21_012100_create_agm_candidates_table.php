<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agm_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('agm_sessions')->cascadeOnDelete();
            $table->string('name');
            $table->string('position');
            $table->text('manifesto')->nullable();
            $table->string('photo_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agm_candidates');
    }
};
