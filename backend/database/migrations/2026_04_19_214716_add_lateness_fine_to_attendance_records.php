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
            $table->decimal('apology_fine_amount', 12, 2)->default(100.00)->after('fine_amount');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->boolean('lateness_fine_paid')->default(false)->after('attended_at');
            $table->decimal('lateness_fine_amount', 12, 2)->default(0.00)->after('lateness_fine_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn('apology_fine_amount');
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn(['lateness_fine_paid', 'lateness_fine_amount']);
        });
    }
};
