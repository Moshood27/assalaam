<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class MemberBalanceImportTemplate implements FromCollection, WithHeadings
{
    public function collection()
    {
        return new Collection([
            [
                'membership_number' => 'MEM001',
                'email' => 'john.doe@example.com',
                'branch_id' => '1',
                'balance' => '5000.00',
                'is_defaulter' => 'no',
            ],
        ]);
    }

    public function headings(): array
    {
        return [
            'membership_number', // required if no email
            'email',             // optional if membership_number is provided
            'branch_id',         // optional
            'balance',           // required to update
            'is_defaulter',      // optional: yes/no
        ];
    }
}
