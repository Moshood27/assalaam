<?php

namespace App\Http\Controllers\Api;

use App\Models\WalletTransaction;
use App\Models\QardHasan;
use App\Models\Contribution;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

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
                ->where('status', 'approved')
                ->sum('balance');
        }

        $kpis = [
            'contributions' => $totalContributions,
            'loans' => $outstandingLoans,
            'wallet_balance' => (float) $user->balance,
            'withdrawable' => method_exists($user, 'availableForWithdrawal') ? (float) $user->availableForWithdrawal() : (float) $user->balance,
            'has_pin' => !empty($user->transaction_pin_hash),
        ];

        return response()->json([
            'full_name' => $user->name,
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
            'kpis' => $kpis,
            'transactions' => $walletTransactions,
            'utility_transactions' => $utility,
        ]);
    }
}
