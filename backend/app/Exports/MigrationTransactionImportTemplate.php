<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class MigrationTransactionImportTemplate implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return new Collection([
            [
                'membership_no' => 'MEM001',
                'amount' => '1000.00',
                'type' => 'credit',
                'reference' => 'PREV-TX-001',
                'source' => 'manual',
                'date' => '2026-01-15',
                'description' => 'Opening Balance from Paper Books',
            ],
            [
                'membership_no' => 'MEM001',
                'amount' => '500.00',
                'type' => 'debit',
                'reference' => 'PREV-TX-002',
                'source' => 'manual',
                'date' => '2026-02-10',
                'description' => 'Monthly Withdrawal Charge',
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'membership_no',
            'amount',
            'type',
            'reference',
            'source',
            'date',
            'description',
        ];
    }
}
