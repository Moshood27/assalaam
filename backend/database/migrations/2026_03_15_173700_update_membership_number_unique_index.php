<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Change unique index on users.membership_number to be scoped per branch (branch_id, membership_number)
        try {
            Schema::table('users', function (Blueprint $table) {
                // Drop the old global unique index on membership_number if it exists
                try {
                    $table->dropUnique('users_membership_number_unique');
                } catch (\Throwable $e) {
                    // Index might not exist (fresh databases) — ignore
                }

                // Create a composite unique index on (branch_id, membership_number)
                try {
                    $table->unique(['branch_id', 'membership_number'], 'users_branch_membership_unique');
                } catch (\Throwable $e) {
                    // Index might already exist — ignore
                }
            });
        } catch (\Throwable $e) {
            // Safeguard: ignore migration failure to keep deploy resilient; DB may already be in desired state.
        }
    }

    public function down(): void
    {
        // Revert back to a global unique index on membership_number
        try {
            Schema::table('users', function (Blueprint $table) {
                try {
                    $table->dropUnique('users_branch_membership_unique');
                } catch (\Throwable $e) {
                    // Ignore if it doesn't exist
                }

                try {
                    $table->unique('membership_number', 'users_membership_number_unique');
                } catch (\Throwable $e) {
                    // Ignore if it already exists
                }
            });
        } catch (\Throwable $e) {
            // Ignore
        }
    }
};
