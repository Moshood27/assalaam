<?php

namespace App\Filament\Resources\WalletTransactionResource\Pages;

use App\Filament\Resources\WalletTransactionResource;
use App\Models\ShariahAuditLog as ShariahAudit;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWalletTransaction extends EditRecord
{
    protected static string $resource = WalletTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        ShariahAudit::log(auth()->user(), 'manual_wallet_transaction_edit', [
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
