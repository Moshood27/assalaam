<?php

namespace App\Filament\Resources\StoreOrderResource\Pages;

use App\Filament\Resources\StoreOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStoreOrder extends EditRecord
{
    protected static string $resource = StoreOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Re-calculate totals on update
        $total_amount = 0;
        $total_cost = 0;

        if (isset($data['items'])) {
            foreach ($data['items'] as &$item) {
                $item['line_total'] = $item['quantity'] * $item['unit_price'];
                $item['line_cost'] = $item['quantity'] * $item['unit_cost'];
                $item['line_profit'] = $item['line_total'] - $item['line_cost'];

                $total_amount += $item['line_total'];
                $total_cost += $item['line_cost'];
            }
        }

        $data['total_amount'] = $total_amount;
        $data['total_cost'] = $total_cost;
        $data['total_profit'] = $total_amount - $total_cost;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
