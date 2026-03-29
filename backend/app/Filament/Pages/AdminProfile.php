<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;

class AdminProfile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationGroup = 'Account';
    protected static ?string $navigationLabel = 'My Profile';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.admin-profile';

    public ?string $name = null;
    public ?string $email = null;

    // For updating email
    public ?string $currentPasswordForEmail = null;

    // For updating password
    public ?string $current_password = null;
    public ?string $new_password = null;
    public ?string $confirm_password = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && (bool) ($user->is_admin ?? false);
    }

    public function mount(): void
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function updateEmail(): void
    {
        $user = auth()->user();
        if (! $user || ! (bool) ($user->is_admin ?? false)) {
            Notification::make()->danger()->title('Unauthorized.')->send();
            return;
        }

        $this->validate([
            'email' => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email,' . $user->id],
            'currentPasswordForEmail' => ['required', 'string'],
        ]);

        if (! Hash::check($this->currentPasswordForEmail, $user->password)) {
            Notification::make()->danger()->title('The provided password is incorrect.')->send();
            return;
        }

        $user->email = $this->email;
        $user->save();

        // Reset sensitive input
        $this->currentPasswordForEmail = '';

        Notification::make()->success()->title('Email updated successfully.')->send();
    }

    public function updatePassword(): void
    {
        $user = auth()->user();
        if (! $user || ! (bool) ($user->is_admin ?? false)) {
            Notification::make()->danger()->title('Unauthorized.')->send();
            return;
        }

        $this->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6'],
            'confirm_password' => ['required', 'same:new_password'],
        ]);

        if (! Hash::check($this->current_password, $user->password)) {
            Notification::make()->danger()->title('The current password is incorrect.')->send();
            return;
        }

        $user->password = Hash::make($this->new_password);
        $user->save();

        // Reset fields
        $this->current_password = $this->new_password = $this->confirm_password = '';

        Notification::make()->success()->title('Password updated successfully.')->send();
    }
}
