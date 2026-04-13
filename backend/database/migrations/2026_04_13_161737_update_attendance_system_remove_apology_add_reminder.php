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
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn('apology_fee_amount');
            $table->timestamp('reminder_sent_at')->nullable();
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            // Drop apology_paid_at column
            $table->dropColumn('apology_paid_at');
            // We should ideally change status enum but SQLite doesn't support that easily.
            // Let's just keep the enum as is in migration, but we won't use 'apology_paid' status anymore.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->decimal('apology_fee_amount', 12, 2)->default(500);
            $table->dropColumn('reminder_sent_at');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->timestamp('apology_paid_at')->nullable();
        });
    }
};
