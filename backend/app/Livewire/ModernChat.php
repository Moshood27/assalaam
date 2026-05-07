<?php

namespace App\Livewire;

use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Services\ChatService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;

class ModernChat extends Component
{
    use WithFileUploads;

    public ChatRoom $chatRoom;
    public $messageBody = '';
    public $attachment = null;
    public $perPage = 50;

    public $showCannedResponses = false;
    public $showFintechActions = false;

    protected $listeners = [
        'echo:chat.room.{chatRoom.id},ChatMessageSent' => '$refresh',
        'echo:chat.room.{chatRoom.id},ChatMessageDeleted' => '$refresh',
    ];

    public function mount(ChatRoom $chatRoom)
    {
        $this->chatRoom = $chatRoom;
    }

    public function sendMessage(ChatService $chatService)
    {
        if (empty($this->messageBody) && !$this->attachment) {
            return;
        }

        $filePath = null;
        $fileName = null;

        if ($this->attachment) {
            $filePath = $this->attachment->store('chat-attachments', 'public');
            $fileName = $this->attachment->getClientOriginalName();
        }

        $chatService->sendMessage($this->chatRoom, Auth::user(), [
            'body' => $this->messageBody,
            'type' => 'text',
            'attachment' => $filePath,
            'attachment_name' => $fileName,
        ]);

        $this->messageBody = '';
        $this->attachment = null;
        $this->showCannedResponses = false;
        $this->showFintechActions = false;
        $this->dispatch('messageSent');
    }

    public function sendGreeting($greeting, ChatService $chatService)
    {
        $chatService->sendMessage($this->chatRoom, Auth::user(), [
            'body' => $greeting,
            'type' => 'text',
        ]);
        $this->dispatch('messageSent');
    }

    public function sendCannedResponse($responseId, ChatService $chatService)
    {
        $response = \App\Models\ChatCannedResponse::find($responseId);
        if ($response) {
            $chatService->sendMessage($this->chatRoom, Auth::user(), [
                'body' => $response->message,
                'type' => 'text',
            ]);
        }
        $this->showCannedResponses = false;
        $this->dispatch('messageSent');
    }

    public function sendTransactionCard($amount, $description, ChatService $chatService)
    {
        $chatService->sendTransactionCard($this->chatRoom, Auth::user(), $amount, $description);
        $this->showFintechActions = false;
        $this->dispatch('messageSent');
    }

    public function sendApprovalRequest($title, $documentPath = null, ChatService $chatService)
    {
        $chatService->sendApprovalRequest($this->chatRoom, Auth::user(), $title, ['document_path' => $documentPath]);
        $this->showFintechActions = false;
        $this->dispatch('messageSent');
    }

    public function deleteMessage($messageId, ChatService $chatService)
    {
        $message = ChatMessage::findOrFail($messageId);

        // Admins can delete any message in this context
        $chatService->deleteMessage($message);
    }

    public function render()
    {
        $messages = $this->chatRoom->messages()
            ->with('user')
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.modern-chat', [
            'messages' => $messages->reverse(),
        ]);
    }
}
