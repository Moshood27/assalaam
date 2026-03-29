<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class SecurityController extends Controller
{
    /**
     * Set or change 4-digit Transaction PIN (requires current password).
     */
    public function setPin(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_pin' => ['required','regex:/^\\d{4}$/'],
            'confirm_pin' => 'required|same:new_pin',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['message' => 'Current password is incorrect'], 403);
        }

        $user->transaction_pin_hash = Hash::make($validated['new_pin']);
        $user->pin_set_at = now();
        $user->save();

        return response()->json(['message' => 'Transaction PIN set successfully']);
    }

    /**
     * Verify a submitted PIN; returns 200 if ok, 403 if invalid, 409 if not set.
     */
    public function verifyPin(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'pin' => ['required','regex:/^\\d{4}$/'],
        ]);

        if (empty($user->transaction_pin_hash)) {
            return response()->json(['message' => 'Transaction PIN not set'], 409);
        }

        if (!$user->verifyTransactionPin($validated['pin'])) {
            return response()->json(['message' => 'Invalid PIN'], 403);
        }

        return response()->json(['message' => 'OK']);
    }
}
