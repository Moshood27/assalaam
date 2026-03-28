<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'bvn')) {
                $table->string('bvn', 20)->nullable()->after('dva_account_name');
            }
            if (!Schema::hasColumn('users', 'bvn_verified_at')) {
                $table->timestamp('bvn_verified_at')->nullable()->after('bvn');
            }
            if (!Schema::hasColumn('users', 'dva_verification_meta')) {
                $table->json('dva_verification_meta')->nullable()->after('bvn_verified_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'dva_verification_meta')) {
                $table->dropColumn('dva_verification_meta');
            }
            if (Schema::hasColumn('users', 'bvn_verified_at')) {
                $table->dropColumn('bvn_verified_at');
            }
            if (Schema::hasColumn('users', 'bvn')) {
                $table->dropColumn('bvn');
            }
        });
    }
};
