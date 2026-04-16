<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;
use App\Models\User;

class MigrationTransactionImportTemplate implements FromCollection, WithHeadings
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

        return $users->map(function ($user) {
            return [
                'membership_no' => $user->membership_number,
                'amount' => '0.00',
                'type' => 'credit',
                'reference' => 'PREV-TX-001',
                'source' => 'manual',
                'date' => date('Y-m-d'),
                'description' => 'Opening Balance',
            ];
        });
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
