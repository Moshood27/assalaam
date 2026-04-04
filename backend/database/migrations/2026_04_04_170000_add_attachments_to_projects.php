<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                if (!Schema::hasColumn('projects', 'report_url')) {
                    $table->string('report_url')->nullable()->after('description');
                }
                if (!Schema::hasColumn('projects', 'media_urls')) {
                    $table->json('media_urls')->nullable()->after('report_url');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                if (Schema::hasColumn('projects', 'media_urls')) {
                    $table->dropColumn('media_urls');
                }
                if (Schema::hasColumn('projects', 'report_url')) {
                    $table->dropColumn('report_url');
                }
            });
        }
    }
};
