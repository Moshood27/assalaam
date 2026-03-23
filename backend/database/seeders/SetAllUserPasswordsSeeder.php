<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SetAllUserPasswordsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder will reset the password of every user to a known value.
     * To execute:
     *  php artisan db:seed --class=Database\\Seeders\\SetAllUserPasswordsSeeder
     */
    public function run(): void
    {
        $newPassword = 'password123';

        $updated = 0;
        User::query()->orderBy('id')->chunkById(500, function ($users) use (&$updated, $newPassword) {
            /** @var User $user */
            foreach ($users as $user) {
                // Use the model instance so Laravel's 'hashed' cast applies automatically
                $user->password = $newPassword; // Will be hashed due to cast in User model
                $user->save();
                $updated++;
            }
        });

        $this->command?->info("Set password for {$updated} users to '{$newPassword}'.");
    }
}
