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
        $users = DB::table('users')->whereNotNull('paystack_customer_code')
            ->orWhereNotNull('dva_account_number')
            ->orWhereNotNull('flw_dva_data')
            ->orWhereNotNull('monnify_customer_reference')
            ->get();

        foreach ($users as $user) {
            DB::table('user_virtual_accounts')->insert([
                'user_id' => $user->id,
                'paystack_customer_code' => $user->paystack_customer_code ?? null,
                'paystack_authorization_code' => $user->paystack_authorization_code ?? null,
                'dva_account_number' => $user->dva_account_number ?? null,
                'dva_bank_name' => $user->dva_bank_name ?? null,
                'dva_account_name' => $user->dva_account_name ?? null,
                'dva_verification_meta' => $user->dva_verification_meta ?? null,
                'flw_dva_data' => $user->flw_dva_data ?? null,
                'monnify_customer_reference' => $user->monnify_customer_reference ?? null,
                'monnify_dva_data' => $user->monnify_dva_data ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Drop columns from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'paystack_customer_code',
                'paystack_authorization_code',
                'dva_account_number',
                'dva_bank_name',
                'dva_account_name',
                'dva_verification_meta',
                'flw_dva_data',
                'monnify_customer_reference',
                'monnify_dva_data',
            ]);
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
            $table->string('monnify_customer_reference')->nullable()->after('paystack_customer_code');
            $table->json('monnify_dva_data')->nullable()->after('flw_dva_data');
        });

        $accounts = DB::table('user_virtual_accounts')->get();
        foreach ($accounts as $account) {
            DB::table('users')->where('id', $account->user_id)->update([
                'paystack_customer_code' => $account->paystack_customer_code,
                'paystack_authorization_code' => $account->paystack_authorization_code,
                'dva_account_number' => $account->dva_account_number,
                'dva_bank_name' => $account->dva_bank_name,
                'dva_account_name' => $account->dva_account_name,
                'dva_verification_meta' => $account->dva_verification_meta,
                'flw_dva_data' => $account->flw_dva_data,
                'monnify_customer_reference' => $account->monnify_customer_reference,
                'monnify_dva_data' => $account->monnify_dva_data,
            ]);
        }

        Schema::dropIfExists('user_virtual_accounts');
    }
};
