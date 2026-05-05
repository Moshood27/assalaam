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
                    @if($message->type === 'image' && $message->attachment)
                        <div class="mb-2">
                            <a href="{{ asset('storage/' . $message->attachment) }}" target="_blank">
                                <img src="{{ asset('storage/' . $message->attachment) }}" alt="Attachment" class="rounded-lg max-h-60 w-auto object-cover border border-white/20">
                            </a>
                        </div>
                    @elseif($message->type === 'file' && $message->attachment)
                        <div class="mb-2 p-2 bg-black/10 rounded flex items-center gap-2">
                            <x-heroicon-o-document class="w-8 h-8 opacity-50" />
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium truncate">{{ $message->attachment_name }}</p>
                                <a href="{{ asset('storage/' . $message->attachment) }}" target="_blank" class="text-[10px] underline opacity-70">Download</a>
                            </div>
                        </div>
                    @endif

                    <div class="text-sm whitespace-pre-wrap">{{ $message->body }}</div>

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

        @if($memberIsTyping)
            <div class="flex justify-start">
                <div class="bg-gray-100 dark:bg-gray-800 text-gray-500 rounded-lg p-2 text-xs animate-pulse">
                    Member is typing...
                </div>
            </div>
        @endif
    </div>

    <div class="p-4 border-t border-gray-200 dark:border-gray-800"
        x-data="{
            typingTimeout: null,
            startTypingTimeout() {
                if (this.typingTimeout) clearTimeout(this.typingTimeout);
                this.typingTimeout = setTimeout(() => {
                    $wire.dispatch('stop-typing');
                    this.typingTimeout = null;
                }, 3000);
            }
        }"
        @typing-active.window="startTypingTimeout()"
    >
        @if ($attachment)
            <div class="mb-2 p-2 bg-gray-50 dark:bg-gray-800 rounded-lg flex items-center justify-between">
                <div class="flex items-center gap-2 overflow-hidden">
                    @if(str_contains($attachment->getMimeType(), 'image'))
                        <img src="{{ $attachment->temporaryUrl() }}" class="w-10 h-10 object-cover rounded">
                    @else
                        <x-heroicon-o-document class="w-10 h-10 text-gray-400" />
                    @endif
                    <span class="text-xs truncate">{{ $attachment->getClientOriginalName() }}</span>
                </div>
                <button type="button" wire:click="$set('attachment', null)" class="text-rose-500">
                    <x-heroicon-m-x-mark class="w-5 h-5" />
                </button>
            </div>
        @endif

        <form wire:submit.prevent="sendMessage" class="flex items-end gap-2">
            <div class="flex-1">
                <textarea
                    wire:model.live="body"
                    placeholder="Type your message..."
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:ring-primary-500 focus:border-primary-500 shadow-sm resize-none"
                    rows="1"
                    x-data="{
                        resize() {
                            $el.style.height = '0px';
                            $el.style.height = $el.scrollHeight + 'px'
                        }
                    }"
                    x-init="resize()"
                    @input="resize()"
                    @keydown.enter.prevent="$wire.sendMessage()"
                ></textarea>
            </div>

            <div class="flex gap-1">
                <label class="cursor-pointer p-2 text-gray-500 hover:text-primary-600 transition">
                    <input type="file" wire:model="attachment" class="hidden">
                    <x-heroicon-o-paper-clip class="w-6 h-6" />
                </label>

                <button
                    type="submit"
                    class="inline-flex items-center p-2 bg-primary-600 border border-transparent rounded-lg font-semibold text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    wire:loading.attr="disabled"
                >
                    <x-heroicon-m-paper-airplane class="w-5 h-5" />
                </button>
            </div>
        </form>
    </div>
</div>
