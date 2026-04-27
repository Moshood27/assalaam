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
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('pregnancy_request_status', 'nursing_mother_status');
            $table->renameColumn('pregnancy_grace_until', 'nursing_mother_grace_until');
            $table->renameColumn('pregnancy_proof_path', 'nursing_mother_proof_path');
            $table->renameColumn('is_pregnant', 'is_nursing_mother');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('nursing_mother_status', 'pregnancy_request_status');
            $table->renameColumn('nursing_mother_grace_until', 'pregnancy_grace_until');
            $table->renameColumn('nursing_mother_proof_path', 'pregnancy_proof_path');
            $table->renameColumn('is_nursing_mother', 'is_pregnant');
        });
    }
};
