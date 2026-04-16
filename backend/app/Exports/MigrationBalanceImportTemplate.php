<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;
use App\Models\User;

class MigrationBalanceImportTemplate implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $users = User::select('membership_number')->get();

        if ($users->isEmpty()) {
            return new Collection([
                [
                    'membership_no' => 'MEM001',
                    'savings_balance' => '10000',
                    'shares_balance' => '5000',
                    'takaful_balance' => '2000',
                    'development_fund_balance' => '0',
                    'outstanding_fines' => '0',
                    'wallet_balance' => '1000',
                    'building_balance' => '0',
                    'agm_balance' => '0',
                    'loan_repayment_balance' => '0',
                    'fine_balance' => '0',
                    'welfare_balance' => '0',
                    'lateness_balance' => '0',
                    'stationery_balance' => '0',
                    'loan_form_balance' => '0',
                    'others_balance' => '0',
                    'id_card_balance' => '0',
                    'emergency_balance' => '0',
                    'entrance_balance' => '0',
                    'h_savings_balance' => '0',
                    'investment_balance' => '0',
                    'digital_gold_balance' => '0',
                    'group_savings_balance' => '0',
                ]
            ]);
        }

        return $users->map(function ($user) {
            return [
                'membership_no' => $user->membership_number,
                'savings_balance' => '0',
                'shares_balance' => '0',
                'takaful_balance' => '0',
                'development_fund_balance' => '0',
                'outstanding_fines' => '0',
                'wallet_balance' => '0',
                'building_balance' => '0',
                'agm_balance' => '0',
                'loan_repayment_balance' => '0',
                'fine_balance' => '0',
                'welfare_balance' => '0',
                'lateness_balance' => '0',
                'stationery_balance' => '0',
                'loan_form_balance' => '0',
                'others_balance' => '0',
                'id_card_balance' => '0',
                'emergency_balance' => '0',
                'entrance_balance' => '0',
                'h_savings_balance' => '0',
                'investment_balance' => '0',
                'digital_gold_balance' => '0',
                'group_savings_balance' => '0',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'membership_no',
            'savings_balance',
            'shares_balance',
            'takaful_balance',
            'development_fund_balance',
            'outstanding_fines',
            'wallet_balance',
            'building_balance',
            'agm_balance',
            'loan_repayment_balance',
            'fine_balance',
            'welfare_balance',
            'lateness_balance',
            'stationery_balance',
            'loan_form_balance',
            'others_balance',
            'id_card_balance',
            'emergency_balance',
            'entrance_balance',
            'h_savings_balance',
            'investment_balance',
            'digital_gold_balance',
            'group_savings_balance',
        ];
    }
}
