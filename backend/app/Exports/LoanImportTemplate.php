<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class LoanImportTemplate implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return new Collection([
            [
                'membership_number' => 'MEM001',
                'email' => 'member@example.com',
                'qard_id_string' => 'QH-2026-ABC123',
                'principal_amount' => '50000',
                'total_installments' => '10',
                'per_installment' => '5000',
                'interval' => 'monthly',
                'admin_fee_flat' => '0',
                'admin_fee_pct' => '1',
                'paid_amount' => '0',
                'status' => 'active',
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'membership_number',
            'email',
            'qard_id_string',
            'principal_amount',
            'total_installments',
            'per_installment',
            'interval',
            'admin_fee_flat',
            'admin_fee_pct',
            'paid_amount',
            'status',
        ];
    }
}
