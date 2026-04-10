<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contributions', function (Blueprint $table) {
            $table->decimal('units', 18, 6)->nullable()->change();
        });

        Schema::table('project_investments', function (Blueprint $table) {
            $table->decimal('units', 18, 6)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('contributions', function (Blueprint $table) {
            $table->integer('units')->nullable()->change();
        });

        Schema::table('project_investments', function (Blueprint $table) {
            $table->integer('units')->nullable()->change();
        });
    }
};
