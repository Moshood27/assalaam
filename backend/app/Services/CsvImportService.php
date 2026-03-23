<?php

namespace App\Services;

use App\Models\QardHasan;
use App\Models\Scheme;
use App\Models\User;
use Illuminate\Support\Str;

class CsvImportService
{
    /**
     * Import Members CSV from a file path.
     * @return array{summary: array{processed:int,created:int,updated:int,failed:int}, errors: array<int,array{row:int,error:string}>}
     */
    public function importMembers(string $path): array
    {
        [$summary, $errors] = $this->parseCsvAndUpsert($path, function(array $row) {
            $name = $row['name'] ?? null;
            $email = $row['email'] ?? null;
            $membership = $row['membership_number'] ?? ($row['membership'] ?? null);
            $branchId = $this->toInt($row['branch_id'] ?? null);
            $balance = $this->toFloat($row['balance'] ?? null);
            $isDefaulter = $this->toBool($row['is_defaulter'] ?? null);

            if (!$name || (!$email && !$membership)) {
                throw new \InvalidArgumentException('Missing required fields: name and (email or membership_number)');
            }

            $user = null;
            if ($membership) {
                $user = User::where('membership_number', $membership)->first();
            }
            if (!$user && $email) {
                $user = User::where('email', $email)->first();
            }

            $data = ['name' => $name];
            if ($email) $data['email'] = $email;
            if ($branchId) $data['branch_id'] = $branchId;
            if ($membership) $data['membership_number'] = $membership;
            if (!is_null($balance)) $data['balance'] = $balance;
            if (!is_null($isDefaulter)) $data['is_defaulter'] = $isDefaulter;

            if ($user) {
                $user->fill($data);
                $user->save();
                return ['updated' => 1, 'created' => 0, 'id' => $user->id];
            }

            if (!$email) {
                if (!$membership) {
                    throw new \InvalidArgumentException('New user must have email or membership_number');
                }
                $data['email'] = strtolower('member_'.$membership.'@example.test');
            }
            $data['password'] = Str::random(12);
            $data['is_admin'] = false;

            $new = User::create($data);
            return ['updated' => 0, 'created' => 1, 'id' => $new->id];
        });

        return [
            'summary' => $summary,
            'errors' => $errors,
        ];
    }

    /**
     * Import Schemes CSV from a file path.
     */
    public function importSchemes(string $path): array
    {
        [$summary, $errors] = $this->parseCsvAndUpsert($path, function(array $row) {
            $name = $row['name'] ?? null;
            if (!$name) throw new \InvalidArgumentException('Missing required field: name');
            $min = $this->toFloat($row['min_amount'] ?? null);
            $active = $this->toBool($row['active'] ?? null);

            $scheme = Scheme::where('name', $name)->first();
            $data = ['name' => $name];
            if (!is_null($min)) $data['min_amount'] = $min;
            if (!is_null($active)) $data['active'] = $active;

            if ($scheme) {
                $scheme->fill($data)->save();
                return ['updated' => 1, 'created' => 0, 'id' => $scheme->id];
            }
            $new = Scheme::create($data);
            return ['updated' => 0, 'created' => 1, 'id' => $new->id];
        });

        return [
            'summary' => $summary,
            'errors' => $errors,
        ];
    }

    /**
     * Import Loans CSV from a file path.
     */
    public function importLoans(string $path): array
    {
        [$summary, $errors] = $this->parseCsvAndUpsert($path, function(array $row) {
            $userId = $this->toInt($row['user_id'] ?? null);
            $membership = $row['membership_number'] ?? null;
            $email = $row['email'] ?? null;

            $user = null;
            if ($userId) $user = User::find($userId);
            if (!$user && $membership) $user = User::where('membership_number', $membership)->first();
            if (!$user && $email) $user = User::where('email', $email)->first();
            if (!$user) throw new \InvalidArgumentException('User not found for loan row');

            $qardString = $row['qard_id_string'] ?? null;
            $principal = $this->toFloat($row['principal_amount'] ?? null);
            $totalInstallments = $this->toInt($row['total_installments'] ?? null) ?: 1;
            $interval = $row['interval'] ?? 'monthly';
            $adminFeeFlat = $this->toFloat($row['admin_fee_flat'] ?? null) ?? 0;
            $adminFeePct = $this->toFloat($row['admin_fee_pct'] ?? null) ?? 0;
            $paidAmount = $this->toFloat($row['paid_amount'] ?? null) ?? 0;
            $status = $row['status'] ?? 'pending';

            if (is_null($principal)) throw new \InvalidArgumentException('Missing principal_amount');

            $loan = null;
            if ($qardString) {
                $loan = QardHasan::where('qard_id_string', $qardString)->first();
            }

            $perInstallment = $this->toFloat($row['per_installment'] ?? null);
            if (is_null($perInstallment)) {
                $perInstallment = round(((float)$principal) / max($totalInstallments, 1), 2);
            }

            $data = [
                'user_id' => $user->id,
                'principal_amount' => $principal,
                'total_installments' => $totalInstallments,
                'per_installment' => $perInstallment,
                'interval' => $interval,
                'admin_fee_flat' => $adminFeeFlat,
                'admin_fee_pct' => $adminFeePct,
                'paid_amount' => $paidAmount,
                'status' => $status,
            ];

            if ($loan) {
                $loan->fill($data)->save();
                return ['updated' => 1, 'created' => 0, 'id' => $loan->id];
            }

            if (!$qardString) {
                $qardString = 'QH-'.now()->format('Y').'-'.Str::upper(Str::random(6));
            }
            $data['qard_id_string'] = $qardString;
            $new = QardHasan::create($data);
            return ['updated' => 0, 'created' => 1, 'id' => $new->id];
        });

        return [
            'summary' => $summary,
            'errors' => $errors,
        ];
    }

    /**
     * Generic CSV parser & upserter helper.
     * @param string $path
     * @param callable $upsert receives normalized row array, must return ['created'=>int,'updated'=>int,'id'=>mixed]
     * @return array{0: array, 1: array}
     */
    protected function parseCsvAndUpsert(string $path, callable $upsert): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [[
                'processed' => 0,
                'created' => 0,
                'updated' => 0,
                'failed' => 0,
            ], [ ['row' => 0, 'error' => 'Unable to open uploaded file'] ]];
        }

        $header = null;
        $rowNum = 0;
        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        while (($data = fgetcsv($handle)) !== false) {
            $rowNum++;
            if ($rowNum === 1) {
                $header = array_map(function($h) {
                    $h = trim($h ?? '');
                    $h = preg_replace('/\x{FEFF}/u', '', $h); // remove UTF-8 BOM
                    return $this->normalizeKey($h);
                }, $data);
                continue;
            }
            if (!$header) continue;
            if ($this->isEmptyRow($data)) continue;
            $row = [];
            foreach ($header as $i => $key) {
                if ($key === '') continue;
                $row[$key] = isset($data[$i]) ? trim((string) $data[$i]) : null;
            }
            try {
                $res = $upsert($row);
                $created += (int)($res['created'] ?? 0);
                $updated += (int)($res['updated'] ?? 0);
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = [
                    'row' => $rowNum,
                    'error' => $e->getMessage(),
                ];
            }
        }
        fclose($handle);

        return [[
            'processed' => $created + $updated + $failed,
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
        ], $errors];
    }

    protected function normalizeKey(?string $key): string
    {
        $key = strtolower(trim($key ?? ''));
        $key = str_replace([' ', '-'], '_', $key);
        return $key;
    }

    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string)$value) !== '') return false;
        }
        return true;
    }

    protected function toInt($value): ?int
    {
        if ($value === null || $value === '') return null;
        if (!is_numeric($value)) return null;
        return (int) $value;
    }

    protected function toFloat($value): ?float
    {
        if ($value === null || $value === '') return null;
        $v = str_replace([',', ' '], ['', ''], (string)$value);
        if (!is_numeric($v)) return null;
        return (float) $v;
    }

    protected function toBool($value): ?bool
    {
        if ($value === null || $value === '') return null;
        $v = strtolower(trim((string)$value));
        if (in_array($v, ['1','true','yes','y'], true)) return true;
        if (in_array($v, ['0','false','no','n'], true)) return false;
        return null;
    }
}
