<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearPaystackDVAs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:clear-paystack-dvas {user_id? : The ID of the user whose Paystack DVA should be cleared}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear Paystack DVA fields from the database for one or all users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user_id');

        if ($userId) {
            $user = \App\Models\User::find($userId);
            if (!$user) {
                $this->error("User not found: {$userId}");
                return self::FAILURE;
            }
            $this->info("Clearing Paystack records for user: {$user->email} (ID: {$userId})");

            // Clear User fields
            $userUpdate = ['autosave_enabled' => false];
            foreach (['paystack_customer_code', 'paystack_authorization_code', 'dva_account_number', 'dva_bank_name', 'dva_account_name'] as $col) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('users', $col)) {
                    $userUpdate[$col] = null;
                }
            }
            $user->update($userUpdate);

            // Clear Virtual Account fields
            if ($user->virtualAccount) {
                $user->virtualAccount->update([
                    'paystack_customer_code' => null,
                    'paystack_authorization_code' => null,
                    'dva_account_number' => null,
                    'dva_bank_name' => null,
                    'dva_account_name' => null,
                    'dva_verification_meta' => null,
                ]);
            }

            $this->info("Successfully cleared Paystack records for user ID: {$userId}");
        } else {
            $this->info("Clearing Paystack records for ALL users");
            if (!$this->confirm('Are you sure you want to clear Paystack records and disable Autosave for ALL users?', false)) {
                $this->warn('Operation cancelled.');
                return self::SUCCESS;
            }

            // Clear User fields for all
            $userUpdate = ['autosave_enabled' => false];
            foreach (['paystack_customer_code', 'paystack_authorization_code', 'dva_account_number', 'dva_bank_name', 'dva_account_name'] as $col) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('users', $col)) {
                    $userUpdate[$col] = null;
                }
            }
            \App\Models\User::query()->update($userUpdate);

            // Clear Virtual Account fields for all
            $count = \App\Models\UserVirtualAccount::query()->update([
                'paystack_customer_code' => null,
                'paystack_authorization_code' => null,
                'dva_account_number' => null,
                'dva_bank_name' => null,
                'dva_account_name' => null,
                'dva_verification_meta' => null,
            ]);

            $this->info("Successfully cleared Paystack records for {$count} record(s).");
        }

        return self::SUCCESS;
    }
}
