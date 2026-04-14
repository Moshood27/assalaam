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
                'name' => 'John Doe',
                'surname' => 'Doe',
                'other_names' => 'John',
                'membership_no' => 'MEM001',
                'phone' => '08012345678',
                'secondary_phone' => '08098765432',
                'email' => 'member@example.com',
                'gender' => 'Male',
                'native_place' => 'Lagos',
                'dob' => '1990-01-01',
                'marital_status' => 'Married',
                'occupation' => 'Engineer',
                'address' => '123 Main St, Lagos',
                'residential_address' => '123 Main St, Lagos',
                'permanent_address' => '456 West St, Abuja',
                'branch' => 'Head Office',
                'bvn' => '12345678901',
                'nature_of_business' => 'Retail',
                'business_address' => 'Market Square, Lagos',
                'has_other_cooperatives' => '0',
                'other_cooperative_details' => '',
                'nok_name' => 'Jane Doe',
                'nok_phone' => '08022233344',
                'nok_relationship' => 'Spouse',
                'nok_address' => '123 Main St, Lagos',
                'guarantor_name' => 'Smith James',
                'guarantor_phone' => '08055566677',
                'guarantor_occupation' => 'Accountant',
                'guarantor_address' => '789 East St, Lagos',
                'religious_society_name' => 'MSSN',
                'imam_name' => 'Imam Hassan',
                'imam_phone' => '08044455566',
                'mosque_address' => 'Central Mosque, Lagos',
                'spouse_father_name' => '',
                'spouse_father_phone' => '',
                'spouse_father_address' => '',
                'spouse_father_business_address' => '',
                'admission_form_number' => 'FRM101',
                'admission_date' => '2024-01-01',
                'admission_officer_name' => 'Admin User',
                'approval_status' => 'Approved',
                'date_joined' => '2024-01-01',
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'name',
            'surname',
            'other_names',
            'membership_no',
            'phone',
            'secondary_phone',
            'email',
            'gender',
            'native_place',
            'dob',
            'marital_status',
            'occupation',
            'address',
            'residential_address',
            'permanent_address',
            'branch',
            'bvn',
            'nature_of_business',
            'business_address',
            'has_other_cooperatives',
            'other_cooperative_details',
            'nok_name',
            'nok_phone',
            'nok_relationship',
            'nok_address',
            'guarantor_name',
            'guarantor_phone',
            'guarantor_occupation',
            'guarantor_address',
            'religious_society_name',
            'imam_name',
            'imam_phone',
            'mosque_address',
            'spouse_father_name',
            'spouse_father_phone',
            'spouse_father_address',
            'spouse_father_business_address',
            'admission_form_number',
            'admission_date',
            'admission_officer_name',
            'approval_status',
            'date_joined',
        ];
    }
}
