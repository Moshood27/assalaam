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
        Schema::table('attendance_records', function (Blueprint $table) {
            // We use string instead of enum to avoid SQLite change issues and provide flexibility
            $table->string('status')->default('absent')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->enum('status', ['present', 'absent', 'apology_paid', 'fine_paid'])->default('absent')->change();
        });
    }
};
