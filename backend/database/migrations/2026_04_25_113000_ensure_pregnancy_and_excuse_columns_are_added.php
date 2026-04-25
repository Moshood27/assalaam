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
            if (!Schema::hasColumn('users', 'pregnancy_request_status')) {
                $table->string('pregnancy_request_status')->nullable()->after('deceased_at');
            }
            if (!Schema::hasColumn('users', 'pregnancy_grace_until')) {
                $table->timestamp('pregnancy_grace_until')->nullable()->after('pregnancy_request_status');
            }
            if (!Schema::hasColumn('users', 'pregnancy_proof_path')) {
                $table->string('pregnancy_proof_path')->nullable()->after('pregnancy_grace_until');
            }
            if (!Schema::hasColumn('users', 'is_pregnant')) {
                $table->boolean('is_pregnant')->default(false)->after('pregnancy_proof_path');
            }
            if (!Schema::hasColumn('users', 'baby_birth_date')) {
                $table->date('baby_birth_date')->nullable()->after('is_pregnant');
            }
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            if (!Schema::hasColumn('attendance_records', 'excuse_reason')) {
                $table->text('excuse_reason')->nullable()->after('status');
            }
            if (!Schema::hasColumn('attendance_records', 'excuse_type')) {
                $table->string('excuse_type')->nullable()->after('excuse_reason');
            }
            if (!Schema::hasColumn('attendance_records', 'excuse_proof_path')) {
                $table->string('excuse_proof_path')->nullable()->after('excuse_type');
            }
            if (!Schema::hasColumn('attendance_records', 'excused_at')) {
                $table->timestamp('excused_at')->nullable()->after('excuse_proof_path');
            }
        });

        // Ensure the ENUM is updated
        try {
            DB::statement("ALTER TABLE attendance_records MODIFY COLUMN status ENUM('present', 'absent', 'apology_paid', 'fine_paid', 'fine_pending', 'excused', 'pending_excuse') DEFAULT 'absent'");
        } catch (\Exception $e) {
            // Ignore if already applied
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op or safe removal if needed, but since this is a fixup migration, we might not want to drop columns that might be in use
    }
};
