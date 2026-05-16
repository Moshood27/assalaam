<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'outstanding_loans')) {
                    $table->decimal('outstanding_loans', 20, 2)->default(0)->after('balance');
                }
                // Add index for branch_id if not exists
                $table->index('branch_id', 'idx_users_branch_id');
            });
        }

        if (Schema::hasTable('meetings')) {
            Schema::table('meetings', function (Blueprint $table) {
                $table->index('status', 'idx_meetings_status');
            });
        }

        if (Schema::hasTable('attendance_records')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->index(['user_id', 'status'], 'idx_attendance_user_status');
            });
        }

        // Index for performance on sharia disputes
        if (Schema::hasTable('sharia_disputes')) {
            Schema::table('sharia_disputes', function (Blueprint $table) {
                $table->index(['user_id', 'status'], 'idx_disputes_user_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('outstanding_loans');
                $table->dropIndex('idx_users_branch_id');
            });
        }

        if (Schema::hasTable('meetings')) {
            Schema::table('meetings', function (Blueprint $table) {
                $table->dropIndex('idx_meetings_status');
            });
        }

        if (Schema::hasTable('attendance_records')) {
            Schema::table('attendance_records', function (Blueprint $table) {
                $table->dropIndex('idx_attendance_user_status');
            });
        }

        if (Schema::hasTable('sharia_disputes')) {
            Schema::table('sharia_disputes', function (Blueprint $table) {
                $table->dropIndex('idx_disputes_user_status');
            });
        }
    }
};
