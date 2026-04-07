<?php

namespace App\Filament\Resources\ContributionResource\Pages;

use App\Filament\Resources\ContributionResource;
use App\Models\ShariahAuditLog as ShariahAudit;
use Filament\Resources\Pages\EditRecord;

class EditContribution extends EditRecord
{
    protected static string $resource = ContributionResource::class;

    protected function afterSave(): void
    {
        $record = $this->record;
        ShariahAudit::log(auth()->user(), 'manual_contribution_updated', [
            'contribution_id' => $record->id,
            'user_id' => $record->user_id,
            'scheme_id' => $record->scheme_id,
            'amount' => $record->amount,
            'status' => $record->status,
            'reference' => $record->reference,
        ]);
    }
}
