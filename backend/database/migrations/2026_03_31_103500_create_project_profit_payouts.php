<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('project_profit_payouts')) {
            Schema::create('project_profit_payouts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_profit_id')->constrained('project_profits');
                $table->foreignId('project_id')->constrained('projects');
                $table->foreignId('user_id')->constrained('users');
                $table->decimal('amount', 15, 2);
                $table->string('status')->default('success');
                $table->timestamp('notified_at')->nullable();
                $table->timestamps();
                $table->unique(['project_profit_id', 'user_id'], 'payouts_profit_user_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_profit_payouts');
    }
};
