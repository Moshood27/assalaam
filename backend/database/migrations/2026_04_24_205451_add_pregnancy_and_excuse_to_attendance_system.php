<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_pregnant')->default(false)->after('deceased_at');
            $table->date('baby_birth_date')->nullable()->after('is_pregnant');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->text('excuse_reason')->nullable()->after('status');
            $table->timestamp('excused_at')->nullable()->after('excuse_reason');
        });

        // Add 'excused' to the status enum for attendance_records
        // Since it's MySQL, we can use DB::statement
        DB::statement("ALTER TABLE attendance_records MODIFY COLUMN status ENUM('present', 'absent', 'apology_paid', 'fine_paid', 'fine_pending', 'excused') DEFAULT 'absent'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_pregnant', 'baby_birth_date']);
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn(['excuse_reason', 'excused_at']);
        });

        DB::statement("ALTER TABLE attendance_records MODIFY COLUMN status ENUM('present', 'absent', 'apology_paid', 'fine_paid', 'fine_pending') DEFAULT 'absent'");
    }
};
