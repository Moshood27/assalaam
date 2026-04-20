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
            $table->timestamp('received_at')->nullable()->after('approved_at')->comment('Date member received the loan funds');
            $table->timestamp('defaulted_at')->nullable()->after('received_at')->comment('Date member was marked as a defaulter for this loan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qard_hasans', function (Blueprint $table) {
            $table->dropColumn(['received_at', 'defaulted_at']);
        });
    }
};
