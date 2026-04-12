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
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('outstanding_fines', 12, 2)->default(0)->after('balance');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->string('status')->default('absent')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('outstanding_fines');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            // We'll keep it as string to avoid issues when reverting back to enum
        });
    }
};
