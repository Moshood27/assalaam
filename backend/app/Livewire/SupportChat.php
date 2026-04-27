<?php

namespace App\Livewire;

use App\Events\SupportMessageSent;
use App\Models\SupportMessage;
use App\Models\User;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Livewire\Component;

class SupportChat extends Component
{
    public User $user;
    public string $body = '';

    public function mount(User $user)
    {
        $this->user = $user;
        $this->markAsRead();
    }

    protected function markAsRead()
    {
        SupportMessage::where('user_id', $this->user->id)
            ->where('sender_type', 'member')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function getListeners()
    {
        return [
            "echo-private:support.{$this->user->id},SupportMessageSent" => 'onMessageReceived',
            "message-sent" => '$refresh',
        ];
    }

    public function onMessageReceived($data)
    {
        // The message is already in the database if it was sent via the API
        // We just need to refresh the component to show it.
        // We also mark it as read if the admin is currently viewing this chat.
        $this->markAsRead();
        $this->dispatch('message-sent');
    }

    public function sendMessage()
    {
        $this->validate([
            'body' => 'required|string|max:2000',
        ]);

        $msg = SupportMessage::create([
            'user_id' => $this->user->id,
            'sender_type' => 'admin',
            'sender_id' => auth()->id(),
            'body' => trim($this->body),
        ]);

        $this->body = '';

        event(new SupportMessageSent($msg));

        $this->dispatch('message-sent');
    }

    public function getMessagesProperty()
    {
        return SupportMessage::where('user_id', $this->user->id)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function render()
    {
        return view('livewire.support-chat', [
            'messages' => $this->messages,
        ]);
    }
}
