<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('qard_hasan_guarantors')) {
            Schema::table('qard_hasan_guarantors', function (Blueprint $table) {
                if (!Schema::hasColumn('qard_hasan_guarantors', 'status')) {
                    $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending')->after('guarantor_id');
                }
                if (!Schema::hasColumn('qard_hasan_guarantors', 'token')) {
                    $table->string('token', 100)->nullable()->after('status');
                }
                if (!Schema::hasColumn('qard_hasan_guarantors', 'responded_at')) {
                    $table->timestamp('responded_at')->nullable()->after('token');
                }
                $table->index(['qard_hasan_id', 'guarantor_id'], 'qh_g_idx');
                $table->index(['qard_hasan_id', 'status'], 'qh_g_status_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('qard_hasan_guarantors')) {
            Schema::table('qard_hasan_guarantors', function (Blueprint $table) {
                if (Schema::hasColumn('qard_hasan_guarantors', 'responded_at')) {
                    $table->dropColumn('responded_at');
                }
                if (Schema::hasColumn('qard_hasan_guarantors', 'token')) {
                    $table->dropColumn('token');
                }
                if (Schema::hasColumn('qard_hasan_guarantors', 'status')) {
                    $table->dropColumn('status');
                }
                try { $table->dropIndex('qh_g_idx'); } catch (\Throwable $e) {}
                try { $table->dropIndex('qh_g_status_idx'); } catch (\Throwable $e) {}
            });
        }
    }
};
