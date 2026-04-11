<?php

namespace Database\Seeders;

use App\Models\Scheme;
use Illuminate\Database\Seeder;

class SchemesSeederOldMap extends Seeder
{
    public function run(): void
    {
        $names = [
            'Savings',
            'Shares',
            'Building',
            'Development',
            'AGM',
            'Loan Repayment',
            'Fine',
            'Welfare',
            'Lateness',
            'Stationery',
            'Loan Form',
            'Others',
            'ID Card',
            'Emergency',
            'Entrance',
            'H Savings',
            'Investment',
            'Sav',
            'Group Savings',
        ];

        foreach ($names as $name) {
            Scheme::firstOrCreate(
                ['name' => $name],
                ['min_amount' => 0, 'active' => true]
            );
        }
    }
}
