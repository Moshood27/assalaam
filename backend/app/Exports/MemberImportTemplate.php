<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;

class MemberImportTemplate implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return new Collection([
            [
                'name' => 'John',
                'surname' => 'Doe',
                'other_names' => 'M.',
                'email' => 'john.doe@example.com',
                'membership_number' => 'MEM001',
                'branch_id' => '1',
                'balance' => '5000.00',
                'ordinary_savings' => '10000.00',
                'special_savings_balance' => '2000.00',
                'shares_capital' => '5000.00',
                'is_defaulter' => 'no',
                'gender' => 'male',
                'native_place' => 'Kano',
                'dob' => '1990-01-01',
                'marital_status' => 'single',
                'occupation' => 'Software Engineer',
                'secondary_phone' => '08012345678',
                'residential_address' => '123 Street, Lagos',
                'permanent_address' => '456 Avenue, Abuja',
                'nature_of_business' => 'IT Services',
                'business_address' => 'IT Hub, Lagos',
                'has_other_cooperatives' => 'no',
                'other_cooperative_details' => '',
                'nok_name' => 'Jane Doe',
                'nok_address' => '123 Street, Lagos',
                'nok_phone' => '08087654321',
                'nok_relationship' => 'Spouse',
                'guarantor_name' => 'Bob Smith',
                'guarantor_address' => '789 Road, Lagos',
                'guarantor_phone' => '09012345678',
                'guarantor_occupation' => 'Businessman',
                'religious_society_name' => 'Muslim Society',
                'imam_name' => 'Imam Ali',
                'mosque_address' => 'Central Mosque',
                'imam_phone' => '07012345678',
                'duration_of_jamma_membership' => '5 years',
                'spouse_father_name' => 'Richard Doe',
                'spouse_father_phone' => '08011122233',
                'spouse_father_address' => 'Abuja',
                'spouse_father_business_address' => 'Abuja Central',
                'admission_form_number' => 'F-001',
                'admission_date' => '2023-01-01',
                'admission_officer_name' => 'Admin User',
                'approval_status' => 'approved',
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'name',
            'surname',
            'other_names',
            'email',
            'membership_number',
            'branch_id',
            'balance',
            'ordinary_savings',
            'special_savings_balance',
            'shares_capital',
            'is_defaulter',
            'gender',
            'native_place',
            'dob',
            'marital_status',
            'occupation',
            'secondary_phone',
            'residential_address',
            'permanent_address',
            'nature_of_business',
            'business_address',
            'has_other_cooperatives',
            'other_cooperative_details',
            'nok_name',
            'nok_address',
            'nok_phone',
            'nok_relationship',
            'guarantor_name',
            'guarantor_address',
            'guarantor_phone',
            'guarantor_occupation',
            'religious_society_name',
            'imam_name',
            'mosque_address',
            'imam_phone',
            'duration_of_jamma_membership',
            'spouse_father_name',
            'spouse_father_phone',
            'spouse_father_address',
            'spouse_father_business_address',
            'admission_form_number',
            'admission_date',
            'admission_officer_name',
            'approval_status',
        ];
    }
}
