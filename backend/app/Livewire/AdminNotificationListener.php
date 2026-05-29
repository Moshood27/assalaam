<?php

namespace App\Livewire;

use Livewire\Component;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;

class AdminNotificationListener extends Component
{
    #[On('echo-private:admin-notifications,NewMemberJoined')]
    public function notifyNewMember($data)
    {
        Notification::make()
            ->title('New Member Registered!')
            ->body(($data['user']['name'] ?? 'A new member') . ' has just joined assalaam.')
            ->success()
            ->send();
    }

    #[On('echo-private:admin-notifications,UserAccountUpdated')]
    public function notifyUserUpdate($data)
    {
        // Only show popup for admin if there's an actual message
        if (empty($data['message'])) {
            return;
        }

        // For Global Activity Feed as requested
        Notification::make()
            ->title('Member Activity')
            ->body($data['message'])
            ->info()
            ->send();
    }

    public function render()
    {
        return '<div></div>';
    }
}
