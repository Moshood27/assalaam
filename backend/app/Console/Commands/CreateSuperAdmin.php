<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\User;
use App\Models\WhitelistedIp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
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

        // Ensure a branch exists
        try {
            $branch = Branch::first();
            if (!$branch) {
                $branch = Branch::create(['name' => 'Head Office']);
                $this->info('Created default "Head Office" branch.');
            }
        } catch (\Throwable $e) {
            $this->error("Error ensuring branch existence: " . $e->getMessage());
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
                'branch_id' => $branch->id,
                'membership_number' => User::generateMembershipNumber($branch->id),
            ]);
            $this->info("User {$email} created.");
        }

        $user->update([
            'is_admin' => true,
            'approval_status' => 'approved',
        ]);

        // Ensure super_admin role exists
        try {
            $role = Role::findOrCreate('super_admin', 'web');
            $user->assignRole($role);
        } catch (\Throwable $e) {
            $this->error("Error assigning super_admin role: " . $e->getMessage());
            $this->info("User was created/updated but role assignment failed. You may need to run 'php artisan shield:install' first.");
        }

        $this->info("User {$email} is now a Super Admin.");

        if (Schema::hasTable('whitelisted_ips') && $this->confirm('Do you want to whitelist an IP address for admin access?')) {
            $ip = $this->ask('Enter IP address (leave empty to skip)');
            if ($ip) {
                WhitelistedIp::updateOrCreate(
                    ['ip_address' => $ip],
                    ['label' => "Super Admin: {$name}", 'is_active' => true]
                );
                $this->info("IP address {$ip} whitelisted.");
            }
        }

        return 0;
    }
}
