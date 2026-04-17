<?php

namespace Database\Seeders;

use App\Models\AdministrativeCharge;
use Illuminate\Database\Seeder;

class AdministrativeChargeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $charges = [
            [
                'name' => 'Monthly Administrative Charge',
                'slug' => 'monthly_admin_charge',
                'amount' => 300,
                'frequency' => 'monthly',
                'description' => 'Regular monthly maintenance fee for members.',
            ],
            [
                'name' => 'Attendance Fine',
                'slug' => 'attendance_fine',
                'amount' => 500,
                'frequency' => 'one-time',
                'description' => 'Fine for missing meetings.',
            ],
            [
                'name' => 'Member Registration Fee',
                'slug' => 'member_registration_fee',
                'amount' => 1000,
                'frequency' => 'one-time',
                'description' => 'One-time fee for new member registration.',
            ],
            [
                'name' => 'Loan Admin Fee (Flat)',
                'slug' => 'loan_admin_fee_flat',
                'amount' => 1,
                'frequency' => 'one-time',
                'description' => 'Flat administrative fee for Qard Hasan loans.',
            ],
            [
                'name' => 'Loan Admin Fee (%)',
                'slug' => 'loan_admin_fee_pct',
                'amount' => 0,
                'percentage' => 0,
                'frequency' => 'one-time',
                'description' => 'Percentage administrative fee for Qard Hasan loans.',
            ],
            [
                'name' => 'Wallet Top-up Charge',
                'slug' => 'wallet_topup_charge',
                'amount' => 0,
                'percentage' => 0.1,
                'max_amount' => 500,
                'frequency' => 'one-time',
                'description' => 'Maintenance charge for wallet deposits.',
            ],
            [
                'name' => 'Monthly Takaful Contribution',
                'slug' => 'takaful_monthly_contribution',
                'amount' => 200,
                'frequency' => 'monthly',
                'description' => 'Regular monthly contribution to the Takaful pool.',
            ],
            [
                'name' => 'Contribution Lateness Fine',
                'slug' => 'contribution_lateness_fine',
                'amount' => 200,
                'frequency' => 'one-time',
                'description' => 'Fine for missing monthly contribution.',
            ],
            [
                'name' => 'VTU Convenience Fee',
                'slug' => 'vtu_convenience_fee',
                'amount' => 0,
                'frequency' => 'one-time',
                'description' => 'Flat fee added on data and utility purchases.',
            ],
            [
                'name' => 'Gold Purchase Fee (%)',
                'slug' => 'gold_buy_fee',
                'amount' => 0,
                'percentage' => 0.5,
                'frequency' => 'one-time',
                'description' => 'Percentage fee charged on digital gold purchases.',
            ],
            [
                'name' => 'Gold Sale Fee (%)',
                'slug' => 'gold_sell_fee',
                'amount' => 0,
                'percentage' => 0.5,
                'frequency' => 'one-time',
                'description' => 'Percentage fee charged on digital gold sales.',
            ],
        ];

        foreach ($charges as $charge) {
            AdministrativeCharge::updateOrCreate(
                ['slug' => $charge['slug']],
                $charge
            );
        }
    }
}
