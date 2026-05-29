<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Carbon\Carbon;

class AutosaveChargeCommand extends Command
{
    protected $signature = 'autosave:charge {--dry-run} {--force-today} {--user=}';

    protected $description = 'Charge users weekly via Paystack authorization_code for Smart Savings (Autosave).';

    public function handle(): int
    {
        $secret = config('services.paystack.secret_key');
        if (!$secret) {
            $this->error('PAYSTACK secret key is not configured.');
            Log::warning('AutosaveCharge: Paystack secret key missing');
            return self::FAILURE;
        }

        $tz = 'Africa/Lagos';
        $now = Carbon::now($tz);
        $todayDow = (int) $now->dayOfWeek; // 0=Sun..6=Sat

        $dryRun = (bool) $this->option('dry-run');
        $forceToday = (bool) $this->option('force-today');
        $specificUser = $this->option('user');

        $query = User::query()
            ->where('autosave_enabled', true)
            ->whereNotNull('paystack_authorization_code')
            ->whereNotNull('email');

        if (!$forceToday) {
            $query->where('autosave_weekday', $todayDow);
        }

        if ($specificUser) {
            $query->where('id', (int) $specificUser);
        }

        // Only one run per calendar day per user
        $query->where(function ($q) use ($now) {
            $q->whereNull('autosave_last_run_at')
              ->orWhereDate('autosave_last_run_at', '<', $now->toDateString());
        });

        $users = $query->limit(500)->get();
        if ($users->isEmpty()) {
            $this->info('No eligible users for autosave at this time.');
            return self::SUCCESS;
        }

        $this->info('Autosave: processing '.$users->count().' users');

        foreach ($users as $user) {
            $amount = (float) ($user->autosave_amount ?? 5000.00);
            if ($amount <= 0) {
                $this->warn("User {$user->id}: autosave_amount <= 0, skipping");
                $user->autosave_last_run_at = $now; // avoid repeated tries today
                $user->save();
                continue;
            }

            $amountKobo = (int) round($amount * 100);
            $reference = 'AUTOSAVE-'.$now->format('Ymd').'-'.$user->id.'-'.bin2hex(random_bytes(3));

            $payload = [
                'email' => $user->email,
                'amount' => $amountKobo,
                'authorization_code' => $user->paystack_authorization_code,
                'reference' => $reference,
                'currency' => 'NGN',
                'metadata' => [
                    'type' => 'autosave',
                    'user_id' => $user->id,
                    'reason' => 'weekly_savings',
                ],
            ];

            if ($dryRun) {
                $this->line("[DRY-RUN] Would charge user {$user->id} â‚¦".number_format($amount, 2)." ref=$reference");
                continue;
            }

            try {
                $resp = Http::withToken($secret)
                    ->acceptJson()
                    ->timeout(20)
                    ->connectTimeout(5)
                    ->post('https://api.paystack.co/transaction/charge_authorization', $payload);

                // Mark last run to avoid re-charging multiple times same day
                $user->autosave_last_run_at = Carbon::now($tz);
                $user->save();

                if (!$resp->ok() || ($resp->json('status') !== true)) {
                    $body = $resp->json() ?: ['raw' => $resp->body()];
                    Log::warning('AutosaveCharge: Paystack charge failed', [
                        'user_id' => $user->id,
                        'reference' => $reference,
                        'response' => $body,
                    ]);
                    $this->warn("User {$user->id} charge failed: ".($resp->json('message') ?? 'HTTP '.$resp->status()));
                    continue;
                }

                $data = $resp->json('data') ?? [];
                $status = $data['status'] ?? 'pending';
                Log::info('AutosaveCharge: initiated', [
                    'user_id' => $user->id,
                    'reference' => $reference,
                    'amount' => $amount,
                    'status' => $status,
                ]);
                $this->info("User {$user->id} autosave initiated: â‚¦".number_format($amount, 2)." ref=$reference status=$status");
            } catch (\Throwable $e) {
                // Mark last run to avoid reattempt spamming in the same minute; next day will retry
                $user->autosave_last_run_at = Carbon::now($tz);
                $user->save();

                Log::error('AutosaveCharge: exception', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("User {$user->id} autosave exception: ".$e->getMessage());
                continue;
            }
        }

        return self::SUCCESS;
    }
}
