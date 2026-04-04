<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('deceased_at')->nullable()->after('autosave_last_run_at');
            $table->timestamp('major_loss_at')->nullable()->after('deceased_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['deceased_at', 'major_loss_at']);
        });
    }
};
