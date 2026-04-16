<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection;
use App\Models\User;

class MigrationPassbookImportTemplate implements FromCollection, WithHeadings
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
                    'scheme_name' => 'Savings',
                    'year' => date('Y'),
                    'january' => '500',
                    'february' => '500',
                    'march' => '500',
                    'april' => '500',
                    'may' => '500',
                    'june' => '500',
                    'july' => '500',
                    'august' => '500',
                    'september' => '500',
                    'october' => '500',
                    'november' => '500',
                    'december' => '500',
                ]
            ]);
        }

        return $users->map(function ($user) {
            return [
                'membership_no' => $user->membership_number,
                'scheme_name' => 'Savings',
                'year' => date('Y'),
                'january' => '0',
                'february' => '0',
                'march' => '0',
                'april' => '0',
                'may' => '0',
                'june' => '0',
                'july' => '0',
                'august' => '0',
                'september' => '0',
                'october' => '0',
                'november' => '0',
                'december' => '0',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'membership_no',
            'scheme_name',
            'year',
            'january',
            'february',
            'march',
            'april',
            'may',
            'june',
            'july',
            'august',
            'september',
            'october',
            'november',
            'december',
        ];
    }
}
