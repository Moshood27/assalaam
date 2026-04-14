<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Str;

class UsersImport implements OnEachRow, WithHeadingRow, WithValidation, WithChunkReading
{
    protected $migrationDate;

    public function __construct($migrationDate = null)
    {
        $this->migrationDate = $migrationDate ?: now();
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function onRow(Row $row)
    {
        $data = $row->toArray();
        $branch = Branch::where('name', 'like', '%' . trim($data['branch']) . '%')->first();

        // Use updateOrCreate to make it idempotent and allow re-runs to update data
        User::updateOrCreate(
            ['membership_number' => $data['membership_no']],
            [
                'name' => $data['name'],
                'surname' => $data['surname'] ?? null,
                'other_names' => $data['other_names'] ?? null,
                'phone' => $data['phone'],
                'secondary_phone' => $data['secondary_phone'] ?? null,
                'email' => $data['email'] ?? null,
                'gender' => strtolower($data['gender'] ?? 'male'),
                'native_place' => $data['native_place'] ?? null,
                'dob' => isset($data['dob']) ? \Carbon\Carbon::parse($data['dob']) : null,
                'marital_status' => strtolower($data['marital_status'] ?? 'single'),
                'occupation' => $data['occupation'] ?? null,
                'address' => $data['address'] ?? null,
                'residential_address' => $data['residential_address'] ?? null,
                'permanent_address' => $data['permanent_address'] ?? null,
                'branch_id' => $branch?->id,
                'bvn' => $data['bvn'] ?? null,
                'bvn_verified_at' => isset($data['bvn']) ? $this->migrationDate : null,

                // Business Info
                'nature_of_business' => $data['nature_of_business'] ?? null,
                'business_address' => $data['business_address'] ?? null,
                'has_other_cooperatives' => (bool) ($data['has_other_cooperatives'] ?? false),
                'other_cooperative_details' => $data['other_cooperative_details'] ?? null,

                // Next of Kin
                'nok_name' => $data['nok_name'] ?? null,
                'nok_phone' => $data['nok_phone'] ?? null,
                'nok_relationship' => $data['nok_relationship'] ?? null,
                'nok_address' => $data['nok_address'] ?? null,

                // Guarantor
                'guarantor_name' => $data['guarantor_name'] ?? null,
                'guarantor_phone' => $data['guarantor_phone'] ?? null,
                'guarantor_occupation' => $data['guarantor_occupation'] ?? null,
                'guarantor_address' => $data['guarantor_address'] ?? null,

                // Religious
                'religious_society_name' => $data['religious_society_name'] ?? null,
                'imam_name' => $data['imam_name'] ?? null,
                'imam_phone' => $data['imam_phone'] ?? null,
                'mosque_address' => $data['mosque_address'] ?? null,

                // Spousal (Female members)
                'spouse_father_name' => $data['spouse_father_name'] ?? null,
                'spouse_father_phone' => $data['spouse_father_phone'] ?? null,
                'spouse_father_address' => $data['spouse_father_address'] ?? null,
                'spouse_father_business_address' => $data['spouse_father_business_address'] ?? null,

                // Official
                'admission_form_number' => $data['admission_form_number'] ?? null,
                'admission_date' => isset($data['admission_date']) ? \Carbon\Carbon::parse($data['admission_date']) : null,
                'admission_officer_name' => $data['admission_officer_name'] ?? null,
                'approval_status' => strtolower($data['approval_status'] ?? 'approved'),

                'password' => Hash::make($data['phone']), // Default password as phone number
                'migrated_at' => $this->migrationDate,
                'created_at' => isset($data['date_joined']) ? \Carbon\Carbon::parse($data['date_joined']) : $this->migrationDate,
            ]
        );
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'membership_no' => 'required',
            'phone' => 'required',
            'branch' => 'required|string',
        ];
    }
}
