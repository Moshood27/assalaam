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
        Schema::create('project_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->decimal('target_amount', 20, 2)->nullable();
            $table->string('status')->default('pending'); // pending, approved, voting, closed, rejected
            $table->string('voting_type')->default('one_member_one_vote');
            $table->timestamp('voting_start_at')->nullable();
            $table->timestamp('voting_end_at')->nullable();
            $table->timestamps();
        });

        Schema::create('project_proposal_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_proposal_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('choice'); // yes, no
            $table->decimal('weight', 20, 2)->default(1.00);
            $table->timestamps();
            $table->unique(['project_proposal_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_proposal_votes');
        Schema::dropIfExists('project_proposals');
    }
};
