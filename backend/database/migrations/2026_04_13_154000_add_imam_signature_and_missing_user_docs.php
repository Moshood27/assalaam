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
        Schema::table('member_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('member_applications', 'imam_signature_path')) {
                $table->string('imam_signature_path')->nullable()->after('imam_approved_at');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'imam_signature_path')) {
                $table->string('imam_signature_path')->nullable();
            }
            if (!Schema::hasColumn('users', 'id_card_path')) {
                $table->string('id_card_path')->nullable();
            }
            if (!Schema::hasColumn('users', 'proof_of_address_path')) {
                $table->string('proof_of_address_path')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('member_applications', function (Blueprint $table) {
            if (Schema::hasColumn('member_applications', 'imam_signature_path')) {
                $table->dropColumn('imam_signature_path');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $columns = ['imam_signature_path', 'id_card_path', 'proof_of_address_path'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
