<?php

namespace App\Imports;

use App\Models\QardHasan;
use App\Models\QardHasanRepayment;
use App\Models\User;
use App\Imports\Concerns\HandlesExcelDates;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Str;

class LoansImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading
{
    use HandlesExcelDates;

    protected $migrationDate;
    protected static $sweptUsers = [];

    public function __construct($migrationDate = null)
    {
        $this->migrationDate = $migrationDate ?: now();
        self::$sweptUsers = []; // Reset for this instance
    }

    public function chunkSize(): int
    {
        return 100;
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

        // --- CLEAN SWEEP: Execute ONLY ONCE per user in this import session ---
        if (!isset(self::$sweptUsers[$user->id])) {
            // 1. Remove non-migration (demo) loans
            $demoLoanIds = QardHasan::where('user_id', $user->id)
                ->where('qard_id_string', 'NOT LIKE', 'MIG-%')
                ->pluck('id');

            QardHasanRepayment::whereIn('qard_hasan_id', $demoLoanIds)->delete();
            QardHasan::whereIn('id', $demoLoanIds)->delete();

            // 2. Remove previous migration loans to allow re-runs to update data/dates
            $prevMigrationIds = QardHasan::where('user_id', $user->id)
                ->where('qard_id_string', 'LIKE', 'MIG-%')
                ->pluck('id');

            QardHasanRepayment::whereIn('qard_hasan_id', $prevMigrationIds)->delete();
            QardHasan::whereIn('id', $prevMigrationIds)->delete();

            self::$sweptUsers[$user->id] = true;
        }

        $totalRepaid = (float) ($row['total_repaid_to_date'] ?? 0);
        $originalAmount = (float) $row['original_loan_amount'];

        // Calculate total installments based on remaining principal and installment amount
        $remaining = (float) $row['remaining_principal'];
        $perInstallment = (float) $row['next_installment_amount'];

        $totalInstallments = (int) ($row['total_installments'] ?? 0);
        if ($totalInstallments <= 0) {
            $installmentsLeft = ($perInstallment > 0) ? ceil($remaining / $perInstallment) : 1;
            $installmentsRepaid = ($perInstallment > 0) ? floor($totalRepaid / $perInstallment) : 0;
            $totalInstallments = $installmentsRepaid + $installmentsLeft;
        }

        return new QardHasan([
            'user_id' => $user->id,
            'qard_id_string' => 'MIG-' . Str::upper(Str::random(8)),
            'principal_amount' => $originalAmount,
            'paid_amount' => $totalRepaid,
            'total_installments' => $totalInstallments,
            'per_installment' => $perInstallment,
            'interval' => strtolower($row['interval'] ?? 'monthly'),
            'status' => 'active',
            'approved_at' => $this->migrationDate,
            'received_at' => $this->parseExcelDate($row['received_at'], $this->migrationDate),
            'defaulted_at' => $this->parseExcelDate($row['defaulted_at']),
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
            'received_at' => 'nullable',
            'defaulted_at' => 'nullable',
        ];
    }
}
