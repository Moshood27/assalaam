<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_approvals', function (Blueprint $table) {
            $table->id();
            $table->morphs('approvable'); // Link to QardHasan or other high-value entities
            $table->foreignId('approver_id')->constrained('users')->onDelete('cascade');
            $table->string('role')->nullable(); // e.g., 'Chairman', 'Sharia Auditor'
            $table->string('status')->default('pending'); // 'pending', 'approved', 'rejected'
            $table->text('comment')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['approvable_id', 'approvable_type', 'approver_id'], 'unique_transaction_approver');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_approvals');
    }
};
