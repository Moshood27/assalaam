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
        Schema::table('agm_sessions', function (Blueprint $table) {
            $table->integer('minimum_quorum')->nullable()->after('voting_type')->comment('Minimum number of voters required for a valid session');
        });

        Schema::table('project_proposals', function (Blueprint $table) {
            $table->integer('minimum_quorum')->nullable()->after('voting_type')->comment('Minimum number of voters required for a valid proposal');
            $table->string('sharia_certificate_path')->nullable()->after('sharia_notes');
            $table->text('fatwa_summary')->nullable()->after('sharia_certificate_path');
        });
    }

    public function down(): void
    {
        Schema::table('agm_sessions', function (Blueprint $table) {
            $table->dropColumn('minimum_quorum');
        });

        Schema::table('project_proposals', function (Blueprint $table) {
            $table->dropColumn(['minimum_quorum', 'sharia_certificate_path', 'fatwa_summary']);
        });
    }
};
