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
            $table->string('agreement_template')->nullable();
            $table->string('signed_agreement')->nullable();
            $table->timestamp('agreement_uploaded_at')->nullable();
            $table->timestamp('agreement_verified_at')->nullable();
            $table->text('agreement_rejection_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qard_hasans', function (Blueprint $table) {
            $table->dropColumn(['agreement_template', 'signed_agreement', 'agreement_uploaded_at', 'agreement_verified_at', 'agreement_rejection_reason']);
        });
    }
};
