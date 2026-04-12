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
        // Move existing branch_id to branch_meeting pivot table
        $meetings = DB::table('meetings')->whereNotNull('branch_id')->get();
        foreach ($meetings as $meeting) {
            DB::table('branch_meeting')->insert([
                'meeting_id' => $meeting->id,
                'branch_id' => $meeting->branch_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('meetings', function (Blueprint $table) {
            // Check if column exists before dropping (safe migration)
            if (Schema::hasColumn('meetings', 'branch_id')) {
                // Drop foreign key if it exists.
                // Note: The foreign key name might vary, but dropping by array works in most cases.
                try {
                    $table->dropForeign(['branch_id']);
                } catch (\Exception $e) {
                    // Ignore if foreign key doesn't exist
                }
                $table->dropColumn('branch_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->constrained()->cascadeOnDelete();
        });

        // Optionally move back from pivot if single branch
        // But many-to-many to many-to-one is lossy, so we might skip this
    }
};
