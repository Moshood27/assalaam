<?php

namespace App\Console\Commands;

use App\Models\Scheme;
use App\Models\User;
use App\Services\GoldSilverPriceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ZakatCheckNisabHawl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zakat:check-nisab-hawl';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Daily check to track Zakat Nisab (85g Gold Rule) and Hawl (Lunar Year)';

    protected $priceService;

    public function __construct(GoldSilverPriceService $priceService)
    {
        parent::__construct();
        $this->priceService = $priceService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $goldPrice = $this->priceService->getSellPrice();
        if (!$goldPrice) {
            $this->error('Could not fetch gold price. Skipping Zakat check.');
            return;
        }

        $nisabGrams = (float) config('zakat.nisab_gold_grams', 85);
        $nisabValue = round($goldPrice * $nisabGrams, 2);
        $lunarDays = (int) config('zakat.lunar_days', 354);
        $rate = (float) config('zakat.rate', 0.025);

        $schemes = Scheme::whereIn('name', ['Savings', 'Shares', 'Special Savings', 'Ordinary Savings', 'Share Capital', 'Digital Gold'])->pluck('id', 'name');

        $this->info("Checking Zakat Hawl for users. Current Gold Nisab: " . number_format($nisabValue, 2) . " NGN (Gold price: " . number_format($goldPrice, 2) . " NGN/g)");

        User::query()->whereNull('deceased_at')->chunk(200, function ($users) use ($nisabValue, $lunarDays, $goldPrice, $schemes, $rate) {
            foreach ($users as $user) {
                $totalAssets = $this->calculateTotalAssets($user, $goldPrice, $schemes);

                if ($totalAssets >= $nisabValue) {
                    if (!$user->zakat_nisab_crossed_at) {
                        $user->update(['zakat_nisab_crossed_at' => now()]);
                        Log::info("User {$user->id} crossed Zakat Nisab threshold.");
                    } else {
                        // Check if Hawl is completed
                        $days = now()->diffInDays($user->zakat_nisab_crossed_at);
                        if ($days >= $lunarDays) {
                            // Check if already paid or notified for this cycle
                            // We allow paying once every lunar year.
                            $canNotify = !$user->zakat_last_paid_at || now()->diffInDays($user->zakat_last_paid_at) >= $lunarDays;

                            if ($canNotify) {
                                $this->notifyZakatDue($user, $totalAssets, $nisabValue, $rate);
                            }
                        }
                    }
                } else {
                    if ($user->zakat_nisab_crossed_at) {
                        $user->update(['zakat_nisab_crossed_at' => null]);
                        Log::info("User {$user->id} fell below Zakat Nisab threshold. Resetting Hawl.");
                    }
                }
            }
        });

        $this->info('Zakat Nisab and Hawl check completed.');
    }

    protected function calculateTotalAssets(User $user, $goldPrice, $schemes)
    {
        $savingsNames = ['Savings', 'Ordinary Savings', 'Special Savings'];
        $sharesNames = ['Shares', 'Share Capital'];

        $savingsIds = [];
        foreach ($savingsNames as $name) {
            if (isset($schemes[$name])) $savingsIds[] = $schemes[$name];
        }

        $sharesIds = [];
        foreach ($sharesNames as $name) {
            if (isset($schemes[$name])) $sharesIds[] = $schemes[$name];
        }

        $savings = (float) $user->contributions()->where('status', 'success')
            ->whereIn('scheme_id', $savingsIds)
            ->sum('amount');
        $shares = (float) $user->contributions()->where('status', 'success')
            ->whereIn('scheme_id', $sharesIds)
            ->sum('amount');

        $currentGoldValue = round($user->gold_balance * $goldPrice, 2);
        $walletBalance = (float) $user->balance;

        return round($savings + $shares + $currentGoldValue + $walletBalance, 2);
    }

    protected function notifyZakatDue(User $user, $assets, $nisab, $rate)
    {
        $zakatDue = round($assets * $rate, 2);
        $currency = config('cooperative.currency', '₦');

        $title = "Zakat Due Report";
        $message = "Your total assets ({$currency}" . number_format($assets) . ") have remained above the Nisab ({$currency}" . number_format($nisab) . ") for a full lunar year. Your estimated Zakat is {$currency}" . number_format($zakatDue) . ". Visit the Gold Savings screen to view the report and pay.";

        $user->notifyMember($title, $message, [
            'type' => 'zakat_due',
            'amount' => $zakatDue,
            'assets' => $assets,
            'nisab' => $nisab
        ]);

        Log::info("User {$user->id} reached Hawl. Zakat due: {$zakatDue}");
    }
}
