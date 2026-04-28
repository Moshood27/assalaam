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
        Schema::create('loan_penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('qard_hasan_id')->constrained()->onDelete('cascade');
            $table->integer('months_defaulted')->default(0);
            $table->timestamp('default_started_at')->nullable();
            $table->timestamp('default_cleared_at')->nullable();
            $table->timestamp('penalty_until')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('loan_penalty_until')->nullable()->after('is_defaulter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('loan_penalty_until');
        });
        Schema::dropIfExists('loan_penalties');
    }
};
