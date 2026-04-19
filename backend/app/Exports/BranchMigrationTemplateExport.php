<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Illuminate\Support\Collection;
use App\Models\User;
use App\Models\Scheme;
use App\Models\Branch;

class BranchMigrationTemplateExport implements FromCollection, WithHeadings, WithMapping
{
    protected $branchId;

    public function __construct($branchId = null)
    {
        $this->branchId = $branchId;
    }

    public function collection()
    {
        $query = User::query()->with('branch');

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        return $query->get();
    }

    public function headings(): array
    {
        $headings = [
            'membership_no',
            'branch',
            'full_name',
        ];

        $schemes = Scheme::orderBy('name')->get();
        foreach ($schemes as $scheme) {
            $headings[] = strtolower(str_replace(' ', '_', $scheme->name)) . '_balance';
        }

        // Add extra columns that are not in schemes table but in BalancesImport
        $extraColumns = [
            'takaful_balance',
            'digital_gold_balance',
            'outstanding_fines',
            'wallet_balance',
        ];

        foreach ($extraColumns as $col) {
            if (!in_array($col, $headings)) {
                $headings[] = $col;
            }
        }

        return $headings;
    }

    public function map($user): array
    {
        $data = [
            $user->membership_number,
            $user->branch?->name,
            $user->full_name,
        ];

        $schemes = Scheme::orderBy('name')->get();
        foreach ($schemes as $scheme) {
            $data[] = '0';
        }

        // Add extra columns
        $extraColumns = [
            'takaful_balance',
            'digital_gold_balance',
            'outstanding_fines',
            'wallet_balance',
        ];

        foreach ($extraColumns as $col) {
             // Check if it was already added as a scheme
             $isScheme = $schemes->contains(function($s) use ($col) {
                 return strtolower(str_replace(' ', '_', $s->name)) . '_balance' === $col;
             });

             if (!$isScheme) {
                 $data[] = '0';
             }
        }

        return $data;
    }
}
