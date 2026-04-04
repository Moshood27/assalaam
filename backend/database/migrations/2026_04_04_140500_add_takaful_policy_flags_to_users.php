<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('takaful_exempt')->default(false)->after('major_loss_at');
            $table->boolean('takaful_notify_contacts')->default(true)->after('takaful_exempt');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['takaful_exempt', 'takaful_notify_contacts']);
        });
    }
};
