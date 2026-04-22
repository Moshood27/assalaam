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
        // MySQL specific change for ENUM
        DB::statement("ALTER TABLE qard_hasans MODIFY COLUMN status ENUM('pending', 'active', 'completed', 'cancelled', 'defaulted', 'rejected') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: this might fail if there are records with 'defaulted' or 'rejected' status
        DB::statement("UPDATE qard_hasans SET status = 'active' WHERE status = 'defaulted'");
        DB::statement("UPDATE qard_hasans SET status = 'cancelled' WHERE status = 'rejected'");
        DB::statement("ALTER TABLE qard_hasans MODIFY COLUMN status ENUM('pending', 'active', 'completed', 'cancelled') NOT NULL DEFAULT 'pending'");
    }
};
