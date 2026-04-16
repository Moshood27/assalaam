<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        User::all()->each(function (User $user) {
            if (!empty($user->surname) && !empty($user->name)) {
                $parts = explode(' ', trim($user->name));

                if (count($parts) >= 2) {
                    $user->surname = $parts[0];
                    $user->name = $parts[1];

                    if (count($parts) > 2) {
                        $user->other_names = implode(' ', array_slice($parts, 2));
                    }

                    $user->save();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // To reverse, we combine them back into the name column
        User::all()->each(function (User $user) {
            if (!empty($user->surname)) {
                $user->name = trim("{$user->surname} {$user->name} {$user->other_names}");
                $user->surname = null;
                $user->other_names = null;
                $user->save();
            }
        });
    }
};
