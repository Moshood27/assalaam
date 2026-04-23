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
        Schema::table('meetings', function (Blueprint $table) {
            $table->integer('radius_meters')->default(100)->change();
        });

        // Update existing meetings that still have the old default of 50
        DB::table('meetings')
            ->where('radius_meters', 50)
            ->update(['radius_meters' => 100]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->integer('radius_meters')->default(50)->change();
        });

        DB::table('meetings')
            ->where('radius_meters', 100)
            ->update(['radius_meters' => 50]);
    }
};
