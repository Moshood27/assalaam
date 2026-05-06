<?php

namespace App\Livewire;

use App\Events\SupportMessageSent;
use App\Events\SupportMessagesRead;
use App\Events\SupportTyping;
use App\Models\SupportMessage;
use App\Models\User;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class SupportChat extends Component
{
    use WithFileUploads;

    public User $user;
    public string $body = '';
    public $attachment = null;
    public bool $isTyping = false;
    public bool $memberIsTyping = false;

    public function mount(User $user)
    {
        $this->user = $user;
        $this->markAsRead();
    }

    protected function markAsRead()
    {
        $updated = SupportMessage::where('user_id', $this->user->id)
            ->where('sender_type', 'member')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        if ($updated > 0) {
            SupportMessagesRead::dispatch($this->user->id, 'admin');
        }
    }

    public function getListeners()
    {
        return [
            "echo-private:support.{$this->user->id},SupportMessageSent" => 'onMessageReceived',
            "echo-private:support.{$this->user->id},SupportTyping" => 'onTypingReceived',
            "echo-private:support.{$this->user->id},SupportMessagesRead" => 'onMessagesReadReceived',
            "message-sent" => '$refresh',
        ];
    }

    public function onMessagesReadReceived($data)
    {
        if (($data['readerType'] ?? '') === 'member') {
            $this->dispatch('message-sent');
        }
    }

    public function onTypingReceived($data)
    {
        if (($data['senderType'] ?? '') === 'member') {
            $this->memberIsTyping = $data['isTyping'] ?? false;
        }
    }

    public function updatedBody()
    {
        if (!$this->isTyping) {
            $this->isTyping = true;
            $this->broadcastTyping(true);
        }

        $this->dispatch('typing-active');
    }

    #[On('stop-typing')]
    public function stopTyping()
    {
        $this->isTyping = false;
        $this->broadcastTyping(false);
    }

    public function broadcastTyping($isTyping)
    {
        SupportTyping::dispatch($this->user->id, 'admin', $isTyping);
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
            'body' => $this->attachment ? 'nullable|string|max:2000' : 'required|string|max:2000',
            'attachment' => 'nullable|file|max:10240', // 10MB max
        ]);

        if (empty(trim($this->body)) && !$this->attachment) {
            return;
        }

        $type = 'text';
        $attachmentPath = null;
        $attachmentName = null;

        if ($this->attachment) {
            $attachmentPath = $this->attachment->store('support-attachments', 'public');
            $attachmentName = $this->attachment->getClientOriginalName();
            $mime = $this->attachment->getMimeType();
            $type = str_contains($mime, 'image') ? 'image' : 'file';
        }

        $msg = SupportMessage::create([
            'user_id' => $this->user->id,
            'sender_type' => 'admin',
            'sender_id' => auth()->id(),
            'body' => trim($this->body) ?: ($type === 'image' ? 'Sent an image' : 'Sent a file'),
            'type' => $type,
            'attachment' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        $this->body = '';
        $this->attachment = null;

        SupportMessageSent::dispatch($msg);

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
