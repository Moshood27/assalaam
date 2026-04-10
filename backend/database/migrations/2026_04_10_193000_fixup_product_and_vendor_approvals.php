<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fixup: All existing products should be approved by default if they were created by admin or before the approval system
        // Since there was no approval system before, everything existing should be approved.
        DB::table('products')->update([
            'is_approved' => true,
            'approved_at' => now(),
        ]);

        // Same for existing vendors (if any)
        DB::table('vendors')->update([
            'is_approved' => true,
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        // No turning back for fixup migrations
    }
};
