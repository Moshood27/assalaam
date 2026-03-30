<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Projects (Pooled investment projects)
        if (!Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('target_amount', 15, 2)->default(0);
                $table->decimal('management_fee_percent', 5, 2)->default(0); // e.g., 10 for 10%
                $table->boolean('active')->default(true);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
            });
        }

        // Project investments (member shares per project)
        if (!Schema::hasTable('project_investments')) {
            Schema::create('project_investments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained();
                $table->foreignId('project_id')->constrained('projects');
                $table->foreignId('contribution_id')->nullable()->constrained('contributions');
                $table->decimal('amount', 15, 2);
                $table->string('reference')->nullable()->index();
                $table->timestamps();
                $table->unique('contribution_id', 'project_investments_contribution_unique');
            });
        }

        // Project profits (recorded profit events for a project)
        if (!Schema::hasTable('project_profits')) {
            Schema::create('project_profits', function (Blueprint $table) {
                $table->id();
                $table->foreignId('project_id')->constrained('projects');
                $table->decimal('gross_profit', 15, 2);
                $table->decimal('management_fee_percent', 5, 2)->default(0);
                $table->decimal('management_fee_amount', 15, 2)->default(0);
                $table->decimal('net_distributable', 15, 2)->default(0);
                $table->string('note')->nullable();
                $table->timestamps();
            });
        }

        // Add optional project_id to contributions to tag scheme payments to a project
        if (Schema::hasTable('contributions') && !Schema::hasColumn('contributions', 'project_id')) {
            Schema::table('contributions', function (Blueprint $table) {
                $table->foreignId('project_id')->nullable()->after('scheme_id')->constrained('projects');
                $table->index('project_id', 'contributions_project_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('contributions') && Schema::hasColumn('contributions', 'project_id')) {
            Schema::table('contributions', function (Blueprint $table) {
                $table->dropIndex('contributions_project_index');
                $table->dropConstrainedForeignId('project_id');
            });
        }

        Schema::dropIfExists('project_profits');
        Schema::dropIfExists('project_investments');
        Schema::dropIfExists('projects');
    }
};
