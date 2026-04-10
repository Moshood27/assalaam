<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\JuniorAccount;
use Illuminate\Support\Facades\DB;

class JuniorCooperativeController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'accounts' => $request->user()->juniorAccounts,
            'balance' => $request->user()->balance,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'child_name' => 'required|string|max:255',
            'child_dob' => 'required|date',
            'locked_until' => 'nullable|date|after:today',
            'purpose' => 'required|string|max:255',
        ]);

        $account = $request->user()->juniorAccounts()->create($validated);

        return response()->json([
            'message' => 'Junior account created successfully',
            'account' => $account
        ], 201);
    }

    public function deposit(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $account = $request->user()->juniorAccounts()->findOrFail($id);
        $user = $request->user();

        if ($user->balance < $request->amount) {
            return response()->json(['message' => 'Insufficient wallet balance'], 400);
        }

        DB::transaction(function () use ($user, $account, $request) {
            $user->decrement('balance', $request->amount);
            $account->increment('balance', $request->amount);

            // Log transaction for the user
            $user->walletTransactions()->create([
                'type' => 'debit',
                'amount' => $request->amount,
                'description' => "Deposit to Junior account: {$account->child_name}",
                'source' => 'junior_cooperative',
                'meta' => ['junior_account_id' => $account->id]
            ]);
        });

        return response()->json([
            'message' => 'Deposit successful',
            'account' => $account->fresh(),
            'wallet_balance' => $user->fresh()->balance
        ]);
    }

    public function withdraw(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $account = $request->user()->juniorAccounts()->findOrFail($id);
        $user = $request->user();

        if ($account->balance < $request->amount) {
            return response()->json(['message' => 'Insufficient junior account balance'], 400);
        }

        if ($account->locked_until && now()->lt($account->locked_until)) {
            return response()->json([
                'message' => "Account is locked until {$account->locked_until}. Withdrawal not allowed yet."
            ], 403);
        }

        DB::transaction(function () use ($user, $account, $request) {
            $account->decrement('balance', $request->amount);
            $user->increment('balance', $request->amount);

            // Log transaction for the user
            $user->walletTransactions()->create([
                'type' => 'credit',
                'amount' => $request->amount,
                'description' => "Withdrawal from Junior account: {$account->child_name}",
                'source' => 'junior_cooperative',
                'meta' => ['junior_account_id' => $account->id]
            ]);
        });

        return response()->json([
            'message' => 'Withdrawal successful',
            'account' => $account->fresh(),
            'wallet_balance' => $user->fresh()->balance
        ]);
    }

    public function history(Request $request, $id)
    {
        $account = $request->user()->juniorAccounts()->findOrFail($id);

        // Fetch transactions for this user where the meta contains this junior_account_id
        // Since meta is json/array, we can use whereJsonContains or filter in PHP
        $transactions = $request->user()->walletTransactions()
            ->where('source', 'junior_cooperative')
            ->get()
            ->filter(function ($tx) use ($id) {
                return isset($tx->meta['junior_account_id']) && $tx->meta['junior_account_id'] == $id;
            })
            ->values();

        return response()->json([
            'account' => $account,
            'transactions' => $transactions
        ]);
    }
}
