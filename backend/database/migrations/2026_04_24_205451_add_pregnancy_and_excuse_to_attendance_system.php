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
            $table->string('pregnancy_request_status')->nullable()->after('deceased_at'); // null, pending, approved, rejected
            $table->timestamp('pregnancy_grace_until')->nullable()->after('pregnancy_request_status');
            $table->string('pregnancy_proof_path')->nullable()->after('pregnancy_grace_until');
            // Keep these for backward compatibility in case they are already in some code
            $table->boolean('is_pregnant')->default(false)->after('pregnancy_proof_path');
            $table->date('baby_birth_date')->nullable()->after('is_pregnant');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->text('excuse_reason')->nullable()->after('status');
            $table->string('excuse_type')->nullable()->after('excuse_reason'); // medical, travel, official, other
            $table->string('excuse_proof_path')->nullable()->after('excuse_type');
            $table->timestamp('excused_at')->nullable()->after('excuse_proof_path');
        });

        // Add 'excused' and 'pending_excuse' to the status enum for attendance_records
        DB::statement("ALTER TABLE attendance_records MODIFY COLUMN status ENUM('present', 'absent', 'apology_paid', 'fine_paid', 'fine_pending', 'excused', 'pending_excuse') DEFAULT 'absent'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pregnancy_request_status', 'pregnancy_grace_until', 'pregnancy_proof_path', 'is_pregnant', 'baby_birth_date']);
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn(['excuse_reason', 'excuse_type', 'excuse_proof_path', 'excused_at']);
        });

        DB::statement("ALTER TABLE attendance_records MODIFY COLUMN status ENUM('present', 'absent', 'apology_paid', 'fine_paid', 'fine_pending') DEFAULT 'absent'");
    }
};
