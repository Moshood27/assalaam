<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // List of branches for the login dropdown
    public function branches()
    {
        return response()->json(Branch::orderBy('name')->get());
    }

    // Branch-based login with membership number
    public function login(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'membership_number' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('membership_number', $validated['membership_number'])
            ->where('branch_id', $validated['branch_id'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'membership_number' => ['The credentials do not match our records for this branch.'],
            ]);
        }

        $token = $user->createToken('mobile_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }
}
