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
        Schema::table('qard_hasans', function (Blueprint $table) {
            if (!Schema::hasColumn('qard_hasans', 'agreement_template')) {
                $table->string('agreement_template')->nullable()->after('approved_at');
            }
            if (!Schema::hasColumn('qard_hasans', 'signed_agreement')) {
                $table->string('signed_agreement')->nullable()->after('agreement_template');
            }
            if (!Schema::hasColumn('qard_hasans', 'agreement_uploaded_at')) {
                $table->timestamp('agreement_uploaded_at')->nullable()->after('signed_agreement');
            }
            if (!Schema::hasColumn('qard_hasans', 'agreement_verified_at')) {
                $table->timestamp('agreement_verified_at')->nullable()->after('agreement_uploaded_at');
            }
            if (!Schema::hasColumn('qard_hasans', 'agreement_rejection_reason')) {
                $table->text('agreement_rejection_reason')->nullable()->after('agreement_verified_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qard_hasans', function (Blueprint $table) {
            $columns = [
                'agreement_template',
                'signed_agreement',
                'agreement_uploaded_at',
                'agreement_verified_at',
                'agreement_rejection_reason',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('qard_hasans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
