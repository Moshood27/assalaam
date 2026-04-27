<div class="flex flex-col h-[600px] bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-800">
    <div class="p-4 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center bg-gray-50 dark:bg-gray-800 rounded-t-lg">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
            Chat with {{ $user->full_name }}
        </h3>
        <span class="text-sm text-gray-500 dark:text-gray-400">
            {{ $user->membership_number }}
        </span>
    </div>

    <div id="chat-window"
        class="flex-1 overflow-y-auto p-4 space-y-4"
        x-data="{
            scroll() {
                $nextTick(() => {
                    $el.scrollTop = $el.scrollHeight
                })
            }
        }"
        x-init="scroll()"
        @message-sent.window="scroll()"
    >
        @forelse ($messages as $message)
            <div class="flex {{ $message->sender_type === 'admin' ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[80%] rounded-lg p-3 {{ $message->sender_type === 'admin' ? 'bg-primary-600 text-white rounded-tr-none' : 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-tl-none' }}">
                    <div class="text-sm">
                        {{ $message->body }}
                    </div>
                    <div class="text-[10px] mt-1 opacity-70 flex justify-between gap-4">
                        <span>{{ $message->created_at->format('H:i') }}</span>
                        @if($message->sender_type === 'admin')
                            <span>{{ $message->read_at ? 'Read' : 'Sent' }}</span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center h-full text-gray-500 dark:text-gray-400">
                <x-heroicon-o-chat-bubble-left-right class="w-12 h-12 mb-2 opacity-20" />
                <p>No messages yet. Start the conversation!</p>
            </div>
        @endforelse
    </div>

    <div class="p-4 border-t border-gray-200 dark:border-gray-800">
        <form wire:submit.prevent="sendMessage" class="flex gap-2">
            <input
                type="text"
                wire:model="body"
                placeholder="Type your message..."
                class="flex-1 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-primary-500 focus:border-primary-500 shadow-sm"
                required
            >
            <button
                type="submit"
                class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150"
                wire:loading.attr="disabled"
            >
                Send
            </button>
        </form>
    </div>
</div>
