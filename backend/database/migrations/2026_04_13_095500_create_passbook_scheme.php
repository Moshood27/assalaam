<?php

use App\Models\Scheme;
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
        Scheme::firstOrCreate(
            ['name' => 'Passbook'],
            ['min_amount' => 0, 'active' => true]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Scheme::where('name', 'Passbook')->delete();
    }
};
