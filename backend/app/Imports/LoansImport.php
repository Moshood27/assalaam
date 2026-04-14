<?php

namespace App\Imports;

use App\Models\QardHasan;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Str;

class LoansImport implements ToModel, WithHeadingRow, WithValidation
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
        $user = User::where('membership_number', $row['membership_no'])->first();
        if (!$user) {
            return null;
        }

        // Calculate total installments based on remaining principal and installment amount
        $remaining = (float) $row['remaining_principal'];
        $perInstallment = (float) $row['next_installment_amount'];
        $installmentsLeft = ($perInstallment > 0) ? ceil($remaining / $perInstallment) : 1;

        $totalRepaid = (float) ($row['total_repaid_to_date'] ?? 0);
        $originalAmount = (float) $row['original_loan_amount'];

        // We want the system to know how many installments there were in total
        $installmentsRepaid = ($perInstallment > 0) ? floor($totalRepaid / $perInstallment) : 0;
        $totalInstallments = $installmentsRepaid + $installmentsLeft;

        return new QardHasan([
            'user_id' => $user->id,
            'qard_id_string' => 'MIG-' . Str::upper(Str::random(8)),
            'principal_amount' => $originalAmount,
            'paid_amount' => $totalRepaid,
            'total_installments' => $totalInstallments,
            'per_installment' => $perInstallment,
            'status' => 'active',
            'approved_at' => $this->migrationDate,
            'created_at' => $this->migrationDate,
        ]);
    }

    public function rules(): array
    {
        return [
            'membership_no' => 'required|exists:users,membership_number',
            'original_loan_amount' => 'required|numeric',
            'remaining_principal' => 'required|numeric',
            'next_installment_amount' => 'required|numeric',
        ];
    }
}
