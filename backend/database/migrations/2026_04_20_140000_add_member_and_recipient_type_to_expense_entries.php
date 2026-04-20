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
        Schema::table('expense_entries', function (Blueprint $table) {
            $table->string('recipient_type')->default('vendor')->after('vendor_id');
            $table->foreignId('member_id')->nullable()->after('recipient_type')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_entries', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
            $table->dropColumn(['recipient_type', 'member_id']);
        });
    }
};
