<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SetMemberPasswordsTo123Seeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * This seeder will reset the password of all registered members to '123'.
     * To execute:
     *  php artisan db:seed --class=Database\\Seeders\\SetMemberPasswordsTo123Seeder
     */
    public function run(): void
    {
        $newPassword = '123';

        $updated = 0;
        User::whereNotNull('membership_number')
            ->where('is_admin', false)
            ->orderBy('id')
            ->chunkById(500, function ($users) use (&$updated, $newPassword) {
                /** @var User $user */
                foreach ($users as $user) {
                    // Use the model instance so Laravel's 'hashed' cast applies automatically
                    $user->password = $newPassword;
                    $user->save();
                    $updated++;
                }
            });

        if (isset($this->command)) {
            $this->command->info("Set password for {$updated} members to '{$newPassword}'.");
        }
    }
}
