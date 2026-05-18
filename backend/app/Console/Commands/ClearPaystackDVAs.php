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

        $query = \App\Models\UserVirtualAccount::query();

        if ($userId) {
            $query->where('user_id', $userId);
            $this->info("Clearing Paystack DVA for user ID: {$userId}");
        } else {
            $this->info("Clearing Paystack DVA for ALL users");
            if (!$this->confirm('Are you sure you want to clear Paystack DVA fields for ALL users?', false)) {
                $this->warn('Operation cancelled.');
                return;
            }
        }

        $count = $query->update([
            'paystack_customer_code' => null,
            'paystack_authorization_code' => null,
            'dva_account_number' => null,
            'dva_bank_name' => null,
            'dva_account_name' => null,
            'dva_verification_meta' => null,
        ]);

        $this->info("Successfully cleared Paystack DVA fields for {$count} record(s).");
    }
}
