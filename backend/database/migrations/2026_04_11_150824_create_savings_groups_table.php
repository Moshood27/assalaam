<?php

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
        Schema::create('savings_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('purpose')->nullable();
            $table->decimal('monthly_contribution_amount', 15, 2)->default(10000.00);
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('creator_id')->constrained('users');
            $table->string('status')->default('active'); // active, completed, cancelled
            $table->timestamp('started_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('savings_groups');
    }
};
