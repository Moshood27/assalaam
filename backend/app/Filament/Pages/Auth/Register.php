<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Register as BaseRegister;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Filament\Notifications\Notification;
use Illuminate\Auth\Events\Registered;
use Filament\Facades\Filament;

class Register extends BaseRegister
{
    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(2);
        } catch (\Exception $exception) {
            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();

            return null;
        }

        $data = $this->form->getState();

        $user = $this->handleRegistration($data);

        event(new Registered($user));

        // Disable automatic login
        // Filament::auth()->login($user);
        // session()->regenerate();

        Notification::make()
            ->title('Registration successful')
            ->body('Your account has been created and is awaiting administrator approval. You will be notified once it is approved.')
            ->success()
            ->persistent()
            ->send();

        return new class implements RegistrationResponse {
            public function toResponse($request)
            {
                return redirect()->route('filament.admin.auth.login');
            }
        };
    }
}
