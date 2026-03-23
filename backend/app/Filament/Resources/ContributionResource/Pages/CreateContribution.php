<?php

namespace App\Filament\Resources\ContributionResource\Pages;

use App\Filament\Resources\ContributionResource;
use App\Models\Contribution;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateContribution extends CreateRecord
{
    protected static string $resource = ContributionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $items = $data['items'] ?? [];

        // If no repeater items provided, fall back to single record creation (should not happen on create)
        if (empty($items)) {
            return static::getModel()::create($data);
        }

        $userId = $data['user_id'] ?? null;
        $status = $data['status'] ?? 'pending';

        if (!$userId) {
            throw ValidationException::withMessages([
                'user_id' => 'Please select a member.',
            ]);
        }

        // Validate distinct schemes and positive amounts
        $schemeIds = [];
        $normalizedItems = [];
        foreach ($items as $index => $item) {
            $scheme = $item['scheme_id'] ?? null;
            $amount = isset($item['amount']) ? (float) $item['amount'] : null;

            if (!$scheme) {
                throw ValidationException::withMessages([
                    "items.$index.scheme_id" => 'Please choose a scheme.',
                ]);
            }
            if ($amount === null || $amount <= 0) {
                throw ValidationException::withMessages([
                    "items.$index.amount" => 'Amount must be greater than 0.',
                ]);
            }
            if (in_array($scheme, $schemeIds, true)) {
                throw ValidationException::withMessages([
                    "items.$index.scheme_id" => 'Duplicate scheme selected. Each scheme should appear only once.',
                ]);
            }
            $schemeIds[] = $scheme;
            $normalizedItems[] = [
                'scheme_id' => $scheme,
                'amount' => $amount,
            ];
        }

        $firstRecord = null;

        DB::transaction(function () use ($normalizedItems, $userId, $status, &$firstRecord) {
            foreach ($normalizedItems as $item) {
                $row = [
                    'user_id' => $userId,
                    'scheme_id' => $item['scheme_id'],
                    'amount' => $item['amount'],
                    'status' => $status,
                    // Intentionally skip 'reference' to let the model auto-generate unique references
                ];

                $created = Contribution::create($row);

                if ($firstRecord === null) {
                    $firstRecord = $created;
                }
            }
        });

        // As CreateRecord expects a Model, return the first one created.
        return $firstRecord ?? static::getModel()::create($data);
    }
}
