<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

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

        // Guard against missing tables on fresh environments
        $transactions = collect();
        if (Schema::hasTable('contributions')) {
            $transactions = $user->contributions()
                ->with('scheme')
                ->orderByDesc('created_at')
                ->take(5)
                ->get();
        }

        $utility = collect();
        if (Schema::hasTable('utility_transactions')) {
            $utility = $user->utilityTransactions()
                ->orderByDesc('created_at')
                ->take(5)
                ->get();
        }

        return response()->json([
            'full_name' => $user->name,
            'email' => $user->email,
            'membership_id' => $user->membership_number,
            'passport_url' => $passportUrl,
            'balance' => (float) $user->balance,
            'joined_at' => optional($user->created_at)->toISOString(),
            'virtual_account' => [
                'account_number' => $user->dva_account_number,
                'bank_name' => $user->dva_bank_name,
                'account_name' => $user->dva_account_name,
            ],
            'transactions' => $transactions,
            'utility_transactions' => $utility,
        ]);
    }
}
