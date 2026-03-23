<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Branches
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Schemes (Savings types)
        Schema::create('schemes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('min_amount', 15, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Users add branch, membership number and balance
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'branch_id')) {
                $table->foreignId('branch_id')->after('id')->nullable()->constrained();
            }
            if (!Schema::hasColumn('users', 'membership_number')) {
                $table->string('membership_number')->unique()->nullable()->after('email');
            }
            if (!Schema::hasColumn('users', 'balance')) {
                $table->decimal('balance', 15, 2)->default(0)->after('password');
            }
        });

        // Contributions (Transactions)
        Schema::create('contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('scheme_id')->constrained('schemes');
            $table->decimal('amount', 15, 2);
            $table->string('reference');
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contributions');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'balance')) {
                $table->dropColumn('balance');
            }
            if (Schema::hasColumn('users', 'membership_number')) {
                $table->dropColumn('membership_number');
            }
            if (Schema::hasColumn('users', 'branch_id')) {
                $table->dropConstrainedForeignId('branch_id');
            }
        });

        Schema::dropIfExists('schemes');
        Schema::dropIfExists('branches');
    }
};
