<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class CreateSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:super-admin
                            {--name= : The name of the super admin}
                            {--email= : The email of the super admin}
                            {--password= : The password of the super admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new super admin user or upgrade an existing user to super admin';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->option('name') ?: $this->ask('Enter name');
        $email = $this->option('email') ?: $this->ask('Enter email');
        $password = $this->option('password') ?: $this->secret('Enter password');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            $this->error('Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->error("- {$error}");
            }
            return 1;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $this->info("User with email {$email} already exists.");
            if (!$this->confirm('Do you want to upgrade this user to Super Admin?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        } else {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]);
            $this->info("User {$email} created.");
        }

        $user->update([
            'is_admin' => true,
            'approval_status' => 'approved',
        ]);

        // Ensure super_admin role exists
        $role = Role::findOrCreate('super_admin', 'web');
        $user->assignRole($role);

        $this->info("User {$email} is now a Super Admin.");

        return 0;
    }
}
