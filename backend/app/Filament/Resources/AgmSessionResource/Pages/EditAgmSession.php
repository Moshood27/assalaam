<?php

namespace App\Filament\Resources\AgmSessionResource\Pages;

use App\Filament\Resources\AgmSessionResource;
use Filament\Resources\Pages\EditRecord;
use App\Models\User;

class EditAgmSession extends EditRecord
{
    protected static string $resource = AgmSessionResource::class;

    protected ?string $originalStatus = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->originalStatus = (string) ($this->record->status ?? '');
        return $data;
    }

    protected function afterSave(): void
    {
        try {
            $newStatus = (string) ($this->record->status ?? '');
            if ($this->originalStatus !== 'open' && $newStatus === 'open') {
                $push = app(\App\Services\PushService::class);
                $title = 'AGM Voting Open';
                $body = 'Voting is now open for \'' . ($this->record->name ?? 'AGM Session') . '\'.';
                $route = '/agm/sessions/' . $this->record->id;
                User::query()
                    ->whereNotNull('device_token')
                    ->orWhereNotNull('fcm_token')
                    ->chunk(500, function ($users) use ($push, $title, $body, $route) {
                        foreach ($users as $u) {
                            $token = $u->fcm_token ?: ($u->device_token ?? null);
                            if (!$token) continue;
                            $push->send($token, $title, $body, [
                                'type' => 'voting_open',
                                'session_id' => $this->record->id,
                                'session_name' => (string) $this->record->name,
                                'start_at' => optional($this->record->start_at)->toIso8601String(),
                                'end_at' => optional($this->record->end_at)->toIso8601String(),
                                'route' => $route,
                            ]);
                        }
                    });
                // mark as notified to avoid duplicate notices from scheduler
                try {
                    $this->record->voting_open_notified_at = now();
                    $this->record->save();
                } catch (\Throwable $e) {}
            }
        } catch (\Throwable $e) {
            // ignore push errors
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
