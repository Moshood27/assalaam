<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Str;

class UsersImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $migrationDate;

    public function __construct($migrationDate = null)
    {
        $this->migrationDate = $migrationDate ?: now();
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $branch = Branch::where('name', 'like', '%' . $row['branch'] . '%')->first();

        return new User([
            'name' => $row['name'],
            'membership_number' => $row['membership_no'],
            'phone' => $row['phone'],
            'email' => $row['email'],
            'address' => $row['address'],
            'branch_id' => $branch?->id,
            'password' => Hash::make($row['phone']), // Default password as phone number
            'migrated_at' => $this->migrationDate,
            'created_at' => $this->migrationDate,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'membership_no' => 'required|unique:users,membership_number',
            'phone' => 'required|string',
            'email' => 'nullable|email|unique:users,email',
            'branch' => 'required|string',
        ];
    }
}
