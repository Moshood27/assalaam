<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add Full-Text indexes for fast searching at scale
        // Note: Full-text indexes are supported in MySQL 5.6+ (InnoDB) and PostgreSQL

        Schema::table('users', function (Blueprint $table) {
            $table->fullText(['name', 'email', 'phone', 'membership_number'], 'users_search_fulltext');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->fullText(['name', 'description'], 'products_search_fulltext');
        });

        // Add additional performance indexes that might be missing for high-concurrency queries
        Schema::table('wallet_transactions', function (Blueprint $table) {
            // Index for fast balance calculation by type/user
            $table->index(['user_id', 'type', 'created_at'], 'idx_wallet_user_type_date');
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            // Index for fast chat history retrieval
            $table->index(['chat_room_id', 'created_at'], 'idx_chat_room_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropFullText('users_search_fulltext');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropFullText('products_search_fulltext');
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_wallet_user_type_date');
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropIndex('idx_chat_room_date');
        });
    }
};
