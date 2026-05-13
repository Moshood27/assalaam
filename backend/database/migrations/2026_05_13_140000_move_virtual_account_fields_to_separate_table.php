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
        // 1. Create the new table
        Schema::create('user_virtual_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('paystack_customer_code')->nullable()->unique()->index();
            $table->string('paystack_authorization_code')->nullable();
            $table->string('dva_account_number')->nullable()->index();
            $table->string('dva_bank_name')->nullable();
            $table->string('dva_account_name')->nullable();
            $table->json('dva_verification_meta')->nullable();
            $table->json('flw_dva_data')->nullable();
            $table->string('monnify_customer_reference')->nullable()->index();
            $table->json('monnify_dva_data')->nullable();
            $table->timestamps();
        });

        // 2. Migrate existing data
        $columnsToMigrate = [
            'paystack_customer_code',
            'paystack_authorization_code',
            'dva_account_number',
            'dva_bank_name',
            'dva_account_name',
            'dva_verification_meta',
            'flw_dva_data',
            'monnify_customer_reference',
            'monnify_dva_data',
        ];

        $existingColumns = [];
        foreach ($columnsToMigrate as $column) {
            if (Schema::hasColumn('users', $column)) {
                $existingColumns[] = $column;
            }
        }

        if (!empty($existingColumns)) {
            $users = DB::table('users')->where(function ($query) use ($existingColumns) {
                foreach ($existingColumns as $column) {
                    $query->orWhereNotNull($column);
                }
            })->get();

            foreach ($users as $user) {
                $insertData = [
                    'user_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                foreach ($columnsToMigrate as $column) {
                    if (property_exists($user, $column)) {
                        $insertData[$column] = $user->$column;
                    }
                }

                DB::table('user_virtual_accounts')->insert($insertData);
            }
        }

        // 3. Drop columns from users table
        Schema::table('users', function (Blueprint $table) use ($columnsToMigrate) {
            foreach ($columnsToMigrate as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('paystack_customer_code')->nullable()->unique()->after('membership_number');
            $table->string('paystack_authorization_code')->nullable()->after('paystack_customer_code');
            $table->string('dva_account_number')->nullable()->after('paystack_authorization_code');
            $table->string('dva_bank_name')->nullable()->after('dva_account_number');
            $table->string('dva_account_name')->nullable()->after('dva_bank_name');
            $table->json('dva_verification_meta')->nullable()->after('bvn_verified_at');
            $table->json('flw_dva_data')->nullable()->after('dva_verification_meta');
            // Monnify fields are NOT added back to 'users' table because it would exceed row size limit.
            // They will only exist in 'user_virtual_accounts' table.
        });

        $accounts = DB::table('user_virtual_accounts')->get();
        foreach ($accounts as $account) {
            $updateData = [
                'paystack_customer_code' => $account->paystack_customer_code,
                'paystack_authorization_code' => $account->paystack_authorization_code,
                'dva_account_number' => $account->dva_account_number,
                'dva_bank_name' => $account->dva_bank_name,
                'dva_account_name' => $account->dva_account_name,
                'dva_verification_meta' => $account->dva_verification_meta,
                'flw_dva_data' => $account->flw_dva_data,
            ];

            DB::table('users')->where('id', $account->user_id)->update($updateData);
        }

        Schema::dropIfExists('user_virtual_accounts');
    }
};
