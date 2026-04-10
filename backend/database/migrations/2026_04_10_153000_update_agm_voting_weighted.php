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
            $table->string('voting_type')->default('one_member_one_vote')->after('status');
        });

        Schema::table('agm_votes', function (Blueprint $table) {
            $table->decimal('weight', 20, 2)->default(1.00)->after('candidate_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agm_sessions', function (Blueprint $table) {
            $table->dropColumn('voting_type');
        });

        Schema::table('agm_votes', function (Blueprint $table) {
            $table->dropColumn('weight');
        });
    }
};
