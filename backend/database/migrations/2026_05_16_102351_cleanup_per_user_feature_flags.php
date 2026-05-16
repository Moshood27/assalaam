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
        // Clear all per-user records to resolve stale overrides and reduce UI clutter
        // This ensures that our new global-first logic works on a clean slate.
        \Illuminate\Support\Facades\DB::table('features')->where('scope', '!=', 'global')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
