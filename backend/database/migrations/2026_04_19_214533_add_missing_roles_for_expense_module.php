<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Role::findOrCreate('Chairman', 'web');
        Role::findOrCreate('Treasurer', 'web');
        Role::findOrCreate('Sharia Auditor', 'web');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We don't necessarily want to delete roles on rollback as they might be in use
    }
};
