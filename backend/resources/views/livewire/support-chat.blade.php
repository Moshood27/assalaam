<div class="flex flex-col h-[600px] bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-800">
    <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center bg-white dark:bg-gray-800 rounded-t-lg">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center text-primary-600 dark:text-primary-400">
                <x-heroicon-m-user class="w-6 h-6" />
            </div>
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white leading-none">
                    {{ $user->full_name }}
                </h3>
                <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1 uppercase tracking-widest font-medium">
                    {{ $user->membership_number }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="flex h-2 w-2 rounded-full {{ $memberIsOnline ? 'bg-emerald-500 animate-pulse' : 'bg-gray-300' }}"></span>
            <span class="text-[10px] font-bold {{ $memberIsOnline ? 'text-emerald-600' : 'text-gray-400' }} uppercase tracking-wider">{{ $memberIsOnline ? 'Online' : 'Offline' }}</span>
        </div>
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
                <div class="max-w-[85%] rounded-2xl p-3 shadow-sm {{ $message->sender_type === 'admin' ? 'bg-primary-600 text-white rounded-tr-none' : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border border-gray-100 dark:border-gray-700 rounded-tl-none' }}">
                    @if($message->type === 'image' && $message->attachment)
                        <div class="mb-2 -mx-1 -mt-1">
                            <a href="{{ asset('storage/' . $message->attachment) }}" target="_blank">
                                <img src="{{ asset('storage/' . $message->attachment) }}" alt="Attachment" class="rounded-xl max-h-64 w-full object-cover border border-white/10">
                            </a>
                        </div>
                    @elseif($message->type === 'file' && $message->attachment)
                        <div class="mb-2 p-2.5 bg-black/5 dark:bg-white/5 rounded-xl flex items-center gap-3 border border-black/5 dark:border-white/5">
                            <x-heroicon-o-document-text class="w-8 h-8 opacity-50" />
                            <div class="flex-1 min-w-0">
                                <p class="text-[11px] font-bold truncate">{{ $message->attachment_name }}</p>
                                <a href="{{ asset('storage/' . $message->attachment) }}" target="_blank" class="text-[10px] underline opacity-70 hover:opacity-100">Download File</a>
                            </div>
                        </div>
                    @endif

                    <div class="text-sm whitespace-pre-wrap leading-relaxed">{{ $message->body }}</div>

                    <div class="text-[10px] mt-1.5 opacity-60 flex justify-end items-center gap-1.5">
                        <span>{{ $message->created_at->format('H:i') }}</span>
                        @if($message->sender_type === 'admin')
                            @if($message->read_at)
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5 text-primary-200">
                                    <path fill-rule="evenodd" d="M19.916 4.626a.75.75 0 0 1 .208 1.04l-9 13.5a.75.75 0 0 1-1.154.114l-6-6a.75.75 0 0 1 1.06-1.06l5.353 5.353 8.493-12.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" />
                                    <path fill-rule="evenodd" d="M12.566 4.626a.75.75 0 0 1 .208 1.04l-9 13.5a.75.75 0 0 1-1.154.114l-6-6a.75.75 0 0 1 1.06-1.06l5.353 5.353 8.493-12.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" transform="translate(4,0)" />
                                </svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5 opacity-50">
                                    <path fill-rule="evenodd" d="M19.916 4.626a.75.75 0 0 1 .208 1.04l-9 13.5a.75.75 0 0 1-1.154.114l-6-6a.75.75 0 0 1 1.06-1.06l5.353 5.353 8.493-12.74a.75.75 0 0 1 1.04-.207Z" clip-rule="evenodd" />
                                </svg>
                            @endif
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

    <div class="p-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900"
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
            <div class="mb-3 p-3 bg-white dark:bg-gray-800 rounded-xl flex items-center justify-between border border-gray-100 dark:border-gray-700 shadow-sm">
                <div class="flex items-center gap-3 overflow-hidden">
                    @if(str_contains($attachment->getMimeType(), 'image'))
                        <img src="{{ $attachment->temporaryUrl() }}" class="w-12 h-12 object-cover rounded-lg shadow-sm">
                    @else
                        <div class="w-12 h-12 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-document-text class="w-8 h-8 text-gray-400" />
                        </div>
                    @endif
                    <div class="min-w-0">
                        <p class="text-xs font-bold truncate text-gray-700 dark:text-gray-300">{{ $attachment->getClientOriginalName() }}</p>
                        <p class="text-[10px] text-gray-500 uppercase tracking-tighter">{{ round($attachment->getSize() / 1024) }} KB</p>
                    </div>
                </div>
                <button type="button" wire:click="$set('attachment', null)" class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-full transition-colors">
                    <x-heroicon-m-x-mark class="w-5 h-5" />
                </button>
            </div>
        @endif

        <form wire:submit.prevent="sendMessage" class="flex items-end gap-3">
            <div class="flex-1 relative">
                <textarea
                    wire:model.live="body"
                    placeholder="Type a message..."
                    class="w-full rounded-2xl border-none bg-white dark:bg-gray-800 dark:text-white focus:ring-2 focus:ring-primary-500 shadow-sm resize-none text-sm py-3 px-4"
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

            <div class="flex items-center gap-2">
                <label class="cursor-pointer p-3 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors hover:bg-white dark:hover:bg-gray-800 rounded-full shadow-sm">
                    <input type="file" wire:model="attachment" class="hidden">
                    <x-heroicon-o-paper-clip class="w-6 h-6" />
                </label>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center w-12 h-12 bg-primary-600 hover:bg-primary-700 text-white rounded-full shadow-lg shadow-primary-200 dark:shadow-none transition-all active:scale-95 disabled:opacity-50"
                    wire:loading.attr="disabled"
                >
                    <x-heroicon-m-paper-airplane class="w-6 h-6 ml-0.5" />
                </button>
            </div>
        </form>
    </div>
</div>
