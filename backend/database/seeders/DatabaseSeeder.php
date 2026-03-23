<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Always ensure base test user exists for sanity
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password']
        );

        // Conditionally import old data if enabled
        if (env('SEED_OLD_DATA', false)) {
            $this->call([
                ImportOldSqlSeeder::class,
                SchemesSeederOldMap::class,
                MembersFromOldSeeder::class,
                MemberPassportsFromOldSeeder::class,
                UnitsAndBranchMappingSeeder::class,
                LoansFromOldSeeder::class,
                LoanGuarantorsFromOldSeeder::class,
                InvestmentRecordsFromOldSeeder::class,
                MigrateLoanRepaymentsSeeder::class,
            ]);
        }

        // Optionally reset all user passwords (DANGEROUS - opt-in via env)
        if (env('SEED_RESET_PASSWORDS', false)) {
            $this->call([
                SetAllUserPasswordsSeeder::class,
            ]);
        }
    }
}
