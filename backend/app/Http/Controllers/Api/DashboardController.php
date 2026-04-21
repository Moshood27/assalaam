<?php

namespace App\Http\Controllers\Api;

use App\Models\WalletTransaction;
use App\Models\QardHasan;
use App\Models\Contribution;
use App\Http\Controllers\Api\ZakatController;
use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Services\GoldSilverPriceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected $priceService;

    public function __construct(GoldSilverPriceService $priceService)
    {
        $this->priceService = $priceService;
    }

    public function index(Request $request)
    {
        $user = $request->user();

        // Calculate Zakat status
        $zakatStatus = null;
        try {
            // Instantiate ZakatController to reuse estimate logic
            $zakatController = app(ZakatController::class);
            $estimate = $zakatController->estimate($request)->getData(true);

            if ($estimate && isset($estimate['base'], $estimate['nisab'])) {
                $zakatStatus = [
                    'eligible' => (bool) ($estimate['eligible'] ?? false),
                    'zakat_due' => (float) ($estimate['zakat_due'] ?? 0),
                    'nisab' => (float) $estimate['nisab'],
                    'reached_nisab' => (bool) ($estimate['base'] >= $estimate['nisab']),
                ];
            }
        } catch (\Exception $e) {
            \Log::error('Failed to calculate Zakat status for dashboard: ' . $e->getMessage());
        }

        // Compute profile passport URL if available
        $passportUrl = null;
        if (!empty($user->passport_path)) {
            $publicPath = public_path($user->passport_path);
            if (is_file($publicPath)) {
                $passportUrl = '/' . ltrim($user->passport_path, '/');
            } else {
                $passportUrl = Storage::disk('public')->url($user->passport_path);
                if (str_starts_with($passportUrl, 'http://') || str_starts_with($passportUrl, 'https://')) {
                    $parsed = parse_url($passportUrl);
                    $passportUrl = ($parsed['path'] ?? '/') . (isset($parsed['query']) ? ('?' . $parsed['query']) : '');
                }
            }
        }

        // Recent Wallet Transactions (Holistic activity)
        $walletTransactions = collect();
        if (Schema::hasTable('wallet_transactions')) {
            $walletTransactions = $user->walletTransactions()
                ->orderByDesc('created_at')
                ->take(10)
                ->get();

            // Calculate running balance for these 10 transactions
            // Note: This is an approximation starting from current balance
            $currentBalance = (float) $user->balance;
            foreach ($walletTransactions as $tx) {
                $tx->setAttribute('balance_after', (float) $currentBalance);
                $tx->setAttribute('running_balance', (float) $currentBalance); // Alias for frontend
                if (strtolower((string)$tx->type) === 'credit') {
                    $currentBalance -= (float) $tx->amount;
                } else {
                    $currentBalance += (float) $tx->amount;
                }
            }
        }

        // Recent Utility Transactions
        $utility = collect();
        if (Schema::hasTable('utility_transactions')) {
            $utility = $user->utilityTransactions()
                ->orderByDesc('created_at')
                ->take(5)
                ->get();
        }

        // Aggregates for KPIs
        $totalContributions = 0;
        if (Schema::hasTable('contributions')) {
            $totalContributions = (float) $user->contributions()->where('status', 'success')->sum('amount');
        }

        $outstandingLoans = 0;
        if (Schema::hasTable('qard_hasans')) {
            $outstandingLoans = (float) $user->qardHasans()
                ->where('status', 'active')
                ->sum(DB::raw('principal_amount - paid_amount'));
        }

        $goldBasePrice = $this->priceService->getGoldPrice();
        $goldSellPrice = $this->priceService->getSellPrice();

        $activeDisputesCount = 0;
        if (Schema::hasTable('sharia_disputes')) {
            $activeDisputesCount = $user->shariaDisputes()->whereIn('status', ['pending', 'mediation'])->count();
        }

        // Attendance
        $ongoingMeeting = null;
        if (Schema::hasTable('meetings')) {
            $ongoingMeeting = Meeting::where('status', 'ongoing')
                ->where(function ($query) use ($user) {
                    $query->whereDoesntHave('branches')
                        ->orWhereHas('branches', function ($q) use ($user) {
                            $q->where('branches.id', $user->branch_id);
                        });
                })
                ->whereDoesntHave('attendanceRecords', function ($q) use ($user) {
                    $q->where('user_id', $user->id)->whereIn('status', ['present', 'apology_paid']);
                })
                ->first();
        }

        $vendor = $user->vendor;
        $vendorStatus = null;
        if ($vendor) {
            $vendorStatus = [
                'is_vendor' => true,
                'is_approved' => (bool) $vendor->is_approved,
                'name' => $vendor->name,
            ];
        } else {
            $vendorStatus = [
                'is_vendor' => false,
            ];
        }

        $eligibility = $user->adjustedLoanEligibility();
        $kpis = [
            'contributions' => $totalContributions,
            'loans' => $outstandingLoans,
            'wallet_balance' => (float) $user->balance,
            'withdrawable' => method_exists($user, 'availableForWithdrawal') ? (float) $user->availableForWithdrawal() : (float) $user->balance,
            'has_pin' => !empty($user->transaction_pin_hash),
            'attaqwa_score' => (int) $user->attaqwa_score,
            'gold_balance' => (float) $user->gold_balance,
            'gold_value_naira' => $goldSellPrice ? round($user->gold_balance * $goldSellPrice, 2) : null,
            'gold_price_per_gram' => $goldBasePrice,
            'vendor' => $vendorStatus,
            'active_disputes_count' => $activeDisputesCount,
            'outstanding_fines' => (float) $user->outstanding_fines,
            'has_ongoing_meeting' => (bool) $ongoingMeeting,
            'savings_balance' => (float) ($eligibility['savings'] ?? 0),
            'shares_balance' => (float) ($eligibility['shares'] ?? 0),
            'loan_limit' => (float) ($eligibility['eligibility_adjusted'] ?? 0),
        ];

        return response()->json([
            'full_name' => $user->full_name,
            'email' => $user->email,
            'membership_id' => $user->membership_number,
            'passport_url' => $passportUrl,
            'balance' => (float) $user->balance,
            'joined_at' => optional($user->created_at)->toISOString(),
            'branch' => $user->branch ? [
                'id' => $user->branch->id,
                'name' => $user->branch->name,
            ] : null,
            'virtual_account' => [
                'account_number' => $user->dva_account_number,
                'bank_name' => $user->dva_bank_name,
                'account_name' => $user->dva_account_name,
                'bvn_assigned' => (bool) ($user->bvn || $user->bvn_verified_at || ($user->dva_account_number && $user->dva_bank_name)),
                'verification_details' => ($user->dva_bank_name && $user->dva_account_number)
                    ? ($user->dva_bank_name . ' - ' . $user->dva_account_number . (
                        $user->dva_account_name ? (' (' . $user->dva_account_name . ')') : ''
                    ))
                    : null,
            ],
            'migration' => [
                'migrated_at' => $user->migrated_at,
                'verified_at' => $user->verified_at,
                'discrepancy_reported_at' => $user->discrepancy_reported_at,
                'total_balance' => (float) $user->balance +
                                  (float) $user->ordinary_savings +
                                  (float) $user->shares_capital +
                                  (float) $user->takafulContributions()->where('reference', 'LIKE', 'MIG-TAKF-%')->sum('amount') +
                                  (float) $user->building_balance +
                                  (float) $user->development_fund_balance +
                                  (float) $user->agm_balance +
                                  (float) $user->loan_repayment_balance +
                                  (float) $user->fine_balance +
                                  (float) $user->welfare_balance +
                                  (float) $user->lateness_balance +
                                  (float) $user->stationery_balance +
                                  (float) $user->loan_form_balance +
                                  (float) $user->others_balance +
                                  (float) $user->id_card_balance +
                                  (float) $user->emergency_balance +
                                  (float) $user->entrance_balance +
                                  (float) $user->h_savings_balance +
                                  (float) $user->special_savings_balance +
                                  (float) $user->investment_balance +
                                  (float) $user->group_savings_balance,
                'breakdown' => [
                    'Wallet' => (float) $user->balance,
                    'Ordinary Savings' => (float) $user->ordinary_savings,
                    'Shares Capital' => (float) $user->shares_capital,
                    'Takaful' => (float) $user->takafulContributions()->where('reference', 'LIKE', 'MIG-TAKF-%')->sum('amount'),
                    'Building' => (float) $user->building_balance,
                    'Development' => (float) $user->development_fund_balance,
                    'AGM' => (float) $user->agm_balance,
                    'Loan Repayment' => (float) $user->loan_repayment_balance,
                    'Fine' => (float) $user->fine_balance,
                    'Welfare' => (float) $user->welfare_balance,
                    'Lateness' => (float) $user->lateness_balance,
                    'Stationery' => (float) $user->stationery_balance,
                    'Loan Form' => (float) $user->loan_form_balance,
                    'Others' => (float) $user->others_balance,
                    'ID Card' => (float) $user->id_card_balance,
                    'Emergency' => (float) $user->emergency_balance,
                    'Entrance' => (float) $user->entrance_balance,
                    'H Savings' => (float) $user->h_savings_balance,
                    'Special Savings' => (float) $user->special_savings_balance,
                    'Investment' => (float) $user->investment_balance,
                    'Group Savings' => (float) $user->group_savings_balance,
                ]
            ],
            'kpis' => $kpis,
            'zakat_status' => $zakatStatus,
            'is_ramadan' => $this->priceService->isRamadan(),
            'is_admin' => (bool) $user->is_admin,
            'fitr_amount' => (float) config('zakat.fitr_amount', 3500),
            'transactions' => $walletTransactions,
            'utility_transactions' => $utility,
        ]);
    }
}
