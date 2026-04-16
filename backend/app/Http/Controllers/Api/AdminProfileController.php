<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminProfileController extends Controller
{
    /**
     * Return the authenticated admin's profile.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        if (! (bool) ($user->is_admin ?? false)) {
            return response()->json(['message' => 'Only admins can access this endpoint.'], 403);
        }

        return response()->json([
            'full_name' => $user->full_name,
            'email' => $user->email,
            'role' => 'Admin',
            'created_at' => $user->created_at ? $user->created_at->toDateTimeString() : null,
        ]);
    }

    /**
     * Update the authenticated admin's email (requires current password).
     */
    public function updateEmail(Request $request)
    {
        $user = $request->user();
        if (! (bool) ($user->is_admin ?? false)) {
            return response()->json(['message' => 'Only admins can access this endpoint.'], 403);
        }

        $data = $request->validate([
            'email' => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['required'],
        ]);

        if (! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided password is incorrect.'],
            ]);
        }

        $user->email = $data['email'];
        $user->save();

        return response()->json([
            'message' => 'Email updated successfully.',
            'email' => $user->email,
        ]);
    }

    /**
     * Update the authenticated admin's password.
     */
    public function updatePassword(Request $request)
    {
        $user = $request->user();
        if (! (bool) ($user->is_admin ?? false)) {
            return response()->json(['message' => 'Only admins can access this endpoint.'], 403);
        }

        $data = $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'min:6'],
            'confirm_password' => ['required'],
        ]);

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        if ($data['new_password'] !== $data['confirm_password']) {
            throw ValidationException::withMessages([
                'confirm_password' => ['Password confirmation does not match.'],
            ]);
        }

        $user->password = Hash::make($data['new_password']);
        $user->save();

        return response()->json([
            'message' => 'Password updated successfully.'
        ]);
    }
}
