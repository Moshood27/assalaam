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
        Schema::table('project_proposals', function (Blueprint $table) {
            $table->string('sharia_status')->default('pending_review')->after('status'); // pending_review, compliant, non_compliant
            $table->text('sharia_notes')->nullable()->after('sharia_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_proposals', function (Blueprint $table) {
            $table->dropColumn(['sharia_status', 'sharia_notes']);
        });
    }
};
