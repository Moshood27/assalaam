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
        $features = DB::table('features')->get();

        foreach ($features as $feature) {
            $value = $feature->value;

            if ($value === null) {
                continue;
            }

            // Check if it's already valid JSON
            json_decode($value);
            if (json_last_error() === JSON_ERROR_NONE) {
                continue;
            }

            // If not JSON, try to unserialize and convert to JSON
            try {
                $unserialized = @unserialize($value);
                // unserialize returns false on failure, but we must check if the original value was serialized false
                if ($unserialized !== false || $value === serialize(false)) {
                    DB::table('features')
                        ->where('id', $feature->id)
                        ->update(['value' => json_encode($unserialized)]);
                }
            } catch (\Throwable $e) {
                // If it's neither JSON nor serialized, it might be a raw string
                // but Pennant expects JSON, so we should probably wrap it in JSON if it's not
                DB::table('features')
                    ->where('id', $feature->id)
                    ->update(['value' => json_encode($value)]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keeping it as JSON is preferred as it's the correct format for Pennant
    }
};
