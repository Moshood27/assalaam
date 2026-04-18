<?php

namespace App\Console\Commands;

use App\Models\Contribution;
use App\Models\Scheme;
use App\Models\User;
use App\Services\PushService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ApplyMonthlyFines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:apply-monthly-fines {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically add a Lateness Fine for members who haven\'t contributed in the previous month.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');

        $lastMonth = Carbon::now()->subMonth();
        $startOfLastMonth = $lastMonth->copy()->startOfMonth();
        $endOfLastMonth = $lastMonth->copy()->endOfMonth();

        $latenessScheme = Scheme::where('name', 'Lateness')->first()
            ?? Scheme::where('name', 'Fine')->first();

        if (!$latenessScheme) {
            $this->error('Lateness or Fine scheme not found. Please ensure it is seeded.');
            return self::FAILURE;
        }

        $fineAmount = (float) $latenessScheme->min_amount > 0 ? (float) $latenessScheme->min_amount : 200.00;

        $this->info("Checking for members who didn't contribute between {$startOfLastMonth->toDateString()} and {$endOfLastMonth->toDateString()}.");

        $users = User::whereNotNull('membership_number')
            ->where('is_admin', false)
            ->whereNull('deceased_at')
            ->where('created_at', '<', $startOfLastMonth) // Members joined before last month started
            ->get();

        $finesAppliedCount = 0;

        foreach ($users as $user) {
            $hasContributed = Contribution::where('user_id', $user->id)
                ->where('status', 'success')
                ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
                ->exists();

            if (!$hasContributed) {
                // Check if fine already applied for this month to avoid duplicates if run multiple times
                $fineExists = Contribution::where('user_id', $user->id)
                    ->where('scheme_id', $latenessScheme->id)
                    ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->exists();

                if (!$fineExists) {
                    if ($dryRun) {
                        $this->line("[DRY-RUN] User {$user->id} ({$user->name}): Lateness fine of ₦" . number_format($fineAmount, 2) . " would be added.");
                        $finesAppliedCount++;
                        continue;
                    }

                    Contribution::create([
                        'user_id' => $user->id,
                        'scheme_id' => $latenessScheme->id,
                        'amount' => $fineAmount,
                        'reference' => 'FINE-' . now()->format('Ymd') . '-' . $user->id . '-' . Str::upper(Str::random(4)),
                        'status' => 'pending', // Fines are usually pending until paid or deducted
                    ]);

                    // Best-effort push notification to the user
                    try {
                        $push = app(PushService::class);
                        $token = $user->fcm_token ?: ($user->device_token ?? null);
                        if ($token) {
                            $title = 'Lateness Fine Applied';
                            $body = "A lateness fine of ₦" . number_format($fineAmount, 2) . " has been added to your account for not contributing in the previous month.";
                            $push->send($token, $title, $body, [
                                'type' => 'lateness_fine',
                                'amount' => (string) $fineAmount,
                            ]);
                        }
                    } catch (\Throwable $e) {
                        // ignore push errors, but log if needed
                    }

                    $finesAppliedCount++;
                }
            }
        }

        if ($dryRun) {
            $this->info("Summary: $finesAppliedCount members would be fined (Dry Run).");
        } else {
            $this->info("Summary: $finesAppliedCount members were fined successfully.");
        }

        return self::SUCCESS;
    }
}
