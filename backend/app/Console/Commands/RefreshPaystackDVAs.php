<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RefreshPaystackDVAs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:refresh-paystack-d-v-as';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate Paystack Dedicated Virtual Accounts for members';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Get all users who need a new account
        // We look for users who either don't have a virtual account record
        // or have one where the Paystack customer code or DVA account number is missing.
        $users = User::where(function ($query) {
            $query->whereDoesntHave('virtualAccount')
                  ->orWhereHas('virtualAccount', function ($q) {
                      $q->whereNull('dva_account_number')
                        ->orWhereNull('paystack_customer_code');
                  });
        })->get();

        $this->info("Regenerating accounts for " . $users->count() . " members...");

        if ($users->isEmpty()) {
            $this->info("No members found needing DVA regeneration.");
            return;
        }

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        $secretKey = config('services.paystack.secret_key');

        foreach ($users as $user) {
            try {
                // 2. Create/Get Customer on the NEW Paystack account
                $customerResponse = Http::withToken($secretKey)
                    ->post('https://api.paystack.co/customer', [
                        'email' => $user->email,
                        'first_name' => $user->name, // Mapping 'name' to first_name
                        'last_name' => $user->surname ?? 'Member', // Mapping 'surname' to last_name
                        'phone' => $user->phone,
                    ]);

                if ($customerResponse->successful()) {
                    $customerCode = $customerResponse->json()['data']['customer_code'];

                    // 3. Generate the Dedicated Virtual Account
                    $dvaResponse = Http::withToken($secretKey)
                        ->post('https://api.paystack.co/dedicated_account', [
                            'customer' => $customerCode,
                            'preferred_bank' => 'wema-bank', // options: 'wema-bank' or 'vfd-microfinance-bank'
                        ]);

                    if ($dvaResponse->successful()) {
                        $dvaData = $dvaResponse->json()['data'];

                        // 4. Update the Database
                        $user->virtualAccount()->updateOrCreate([], [
                            'paystack_customer_code' => $customerCode,
                            'dva_account_number' => $dvaData['account_number'],
                            'dva_bank_name' => $dvaData['bank']['name'],
                            'dva_account_name' => $dvaData['account_name'] ?? ($user->name . ' ' . $user->surname),
                        ]);
                    } else {
                        Log::error("DVA Generation failed for {$user->email}: " . $dvaResponse->body());
                    }
                } else {
                    Log::error("Customer Creation failed for {$user->email}: " . $customerResponse->body());
                }
            } catch (\Exception $e) {
                Log::error("DVA Refresh failed for {$user->email}: " . $e->getMessage());
            }

            // 5. IMPORTANT: Pause for 200ms to avoid hitting Paystack's rate limit
            usleep(200000);
            $bar->advance();
        }

        $bar->finish();
        $this->info("\nAll accounts have been refreshed!");
    }
}
