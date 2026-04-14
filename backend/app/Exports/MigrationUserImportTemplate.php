<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;

class MigrationUserImportTemplate implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return new Collection([
            [
                'name' => 'Member Name',
                'membership_no' => 'MEM001',
                'phone' => '08012345678',
                'email' => 'member@example.com',
                'address' => 'Lagos, Nigeria',
                'branch' => 'Head Office',
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'name',
            'membership_no',
            'phone',
            'email',
            'address',
            'branch',
        ];
    }
}
