<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('is_unit_based')->default(false)->after('management_fee_percent');
            $table->decimal('unit_price', 15, 2)->nullable()->after('is_unit_based');
            $table->integer('total_units')->nullable()->after('unit_price');
            $table->integer('available_units')->nullable()->after('total_units');
        });

        Schema::create('project_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('content')->nullable();
            $table->json('media_urls')->nullable();
            $table->string('type')->default('info'); // info, photo, video, financial
            $table->timestamps();
        });

        Schema::table('project_investments', function (Blueprint $table) {
            $table->integer('units')->nullable()->after('amount');
        });

        Schema::table('contributions', function (Blueprint $table) {
            $table->integer('units')->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('contributions', function (Blueprint $table) {
            $table->dropColumn('units');
        });

        Schema::table('project_investments', function (Blueprint $table) {
            $table->dropColumn('units');
        });

        Schema::dropIfExists('project_updates');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['is_unit_based', 'unit_price', 'total_units', 'available_units']);
        });
    }
};
