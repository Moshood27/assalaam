<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;
use App\Models\User;

class MigrationLoanImportTemplate implements FromCollection, WithHeadings
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
                    'original_loan_amount' => '100000',
                    'remaining_principal' => '40000',
                    'next_installment_amount' => '10000',
                    'total_repaid_to_date' => '60000',
                    'interval' => 'monthly',
                    'total_installments' => '10',
                    'received_at' => now()->subMonths(2)->format('Y-m-d'),
                    'defaulted_at' => '',
                ]
            ]);
        }

        return $users->map(function ($user) {
            return [
                'membership_no' => $user->membership_number,
                'original_loan_amount' => '0',
                'remaining_principal' => '0',
                'next_installment_amount' => '0',
                'total_repaid_to_date' => '0',
                'interval' => 'monthly',
                'total_installments' => '0',
                'received_at' => '',
                'defaulted_at' => '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'membership_no',
            'original_loan_amount',
            'remaining_principal',
            'next_installment_amount',
            'total_repaid_to_date',
            'interval',
            'total_installments',
            'received_at',
            'defaulted_at',
        ];
    }
}
