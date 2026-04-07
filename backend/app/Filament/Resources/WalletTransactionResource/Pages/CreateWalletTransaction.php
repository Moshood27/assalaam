<?php

namespace App\Filament\Resources\WalletTransactionResource\Pages;

use App\Filament\Resources\WalletTransactionResource;
use App\Models\ShariahAuditLog as ShariahAudit;
use Filament\Resources\Pages\CreateRecord;

class CreateWalletTransaction extends CreateRecord
{
    protected static string $resource = WalletTransactionResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;
        ShariahAudit::log(auth()->user(), 'manual_wallet_transaction_create', [
            'transaction_id' => $record->id,
            'user_id' => $record->user_id,
            'amount' => $record->amount,
            'type' => $record->type,
            'reference' => $record->reference,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
