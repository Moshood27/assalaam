<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Models\Contracts\FilamentUser;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        try {
            return parent::authenticate();
        } catch (ValidationException $e) {
            $user = \App\Models\User::where('email', $this->data['email'] ?? null)->first();

            if ($user && $user->approval_status !== 'approved') {
                Notification::make()
                    ->title('Account Pending Approval')
                    ->body('Your account is currently awaiting administrator approval. You will be notified once it is approved.')
                    ->danger()
                    ->send();
            }

            throw $e;
        }
    }
}
