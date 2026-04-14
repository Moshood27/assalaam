<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class MigrationLoanImportTemplate implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return new Collection([
            [
                'membership_no' => 'MEM001',
                'original_loan_amount' => '100000',
                'remaining_principal' => '40000',
                'next_installment_amount' => '10000',
                'total_repaid_to_date' => '60000',
                'interval' => 'monthly',
                'total_installments' => '10',
            ]
        ]);
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
        ];
    }
}
