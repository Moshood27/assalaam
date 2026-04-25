<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sync pregnancy_request_status for users who have active grace markers but null status
        DB::table('users')
            ->whereNull('pregnancy_request_status')
            ->where(function ($query) {
                $query->where('is_pregnant', true)
                    ->orWhereNotNull('baby_birth_date')
                    ->orWhereNotNull('pregnancy_grace_until');
            })
            ->update(['pregnancy_request_status' => 'approved']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
