<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('qard_hasans', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('status');
            $table->foreignId('approved_by')->nullable()->after('rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('qard_hasans', function (Blueprint $table) {
            if (Schema::hasColumn('qard_hasans', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('qard_hasans', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }
            if (Schema::hasColumn('qard_hasans', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
        });
    }
};
