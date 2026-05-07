<div class="flex flex-col h-[600px] bg-white dark:bg-gray-900 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <!-- Chat Header -->
    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center bg-gray-50 dark:bg-gray-800 rounded-t-lg">
        <div class="flex items-center space-x-3">
            <div class="flex-shrink-0">
                @if($chatRoom->avatar)
                    <img class="h-10 w-10 rounded-full object-cover" src="{{ asset('storage/' . $chatRoom->avatar) }}" alt="">
                @else
                    <div class="h-10 w-10 rounded-full bg-primary-500 flex items-center justify-center text-white font-bold">
                        {{ strtoupper(substr($chatRoom->name, 0, 1)) }}
                    </div>
                @endif
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-900 dark:text-white">{{ $chatRoom->name }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 capitalize">{{ $chatRoom->type }} Room</p>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            @if($chatRoom->metadata['is_2fa_protected'] ?? false)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                    <x-heroicon-m-shield-check class="w-3 h-3 mr-1" /> 2FA
                </span>
            @endif
        </div>
    </div>

    <!-- Messages Area -->
    <div class="flex-1 overflow-y-auto p-4 space-y-4" id="chat-messages" x-data="{ scroll: () => { $el.scrollTop = $el.scrollHeight } }" x-init="scroll()" @message-sent.window="scroll()">
        @forelse($messages as $message)
            <div class="flex {{ $message->user_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[70%] {{ $message->user_id === auth()->id() ? 'bg-primary-500 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' }} rounded-lg px-4 py-2 shadow-sm relative group">
                    <div class="flex flex-col">
                        @if($message->user_id !== auth()->id())
                            <span class="text-[10px] font-bold opacity-75 mb-1">{{ $message->user->full_name }}</span>
                        @endif

                        <div class="text-sm">
                            {!! nl2br(e($message->body)) !!}
                        </div>

                        @if($message->attachment)
                            <div class="mt-2 p-2 bg-black bg-opacity-10 rounded flex items-center space-x-2">
                                <x-heroicon-o-paper-clip class="w-4 h-4" />
                                <a href="{{ asset('storage/' . $message->attachment) }}" target="_blank" class="text-xs hover:underline truncate">
                                    {{ $message->attachment_name ?? 'Attachment' }}
                                </a>
                            </div>
                        @endif

                        <div class="flex items-center justify-end space-x-1 mt-1 opacity-50">
                            <span class="text-[10px]">{{ $message->created_at->format('H:i') }}</span>
                            @if($message->edited_at)
                                <span class="text-[10px] italic">(Edited)</span>
                            @endif
                        </div>
                    </div>

                    <!-- Delete Action -->
                    <button
                        wire:click="deleteMessage({{ $message->id }})"
                        wire:confirm="Are you sure you want to delete this message? It will be soft-deleted."
                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity"
                    >
                        <x-heroicon-m-x-mark class="w-3 h-3" />
                    </button>
                </div>
            </div>
        @empty
            <div class="flex items-center justify-center h-full text-gray-500 italic">
                No messages yet. Start the conversation with Adab.
            </div>
        @endforelse
    </div>

    <!-- Chat Input -->
    <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 rounded-b-lg">
        <form wire:submit.prevent="sendMessage" class="flex items-end space-x-2">
            <div class="flex-1">
                <textarea
                    wire:model="messageBody"
                    placeholder="Type a message (Maintain Adab)..."
                    class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm h-10 min-h-[40px] max-h-32 resize-none py-2"
                    @keydown.enter.prevent="$wire.sendMessage()"
                ></textarea>

                @if($attachment)
                    <div class="mt-2 flex items-center text-xs text-gray-600 dark:text-gray-400">
                        <x-heroicon-o-document class="w-3 h-3 mr-1" />
                        {{ $attachment->getClientOriginalName() }}
                        <button type="button" wire:click="$set('attachment', null)" class="ml-2 text-red-500 hover:text-red-700">Remove</button>
                    </div>
                @endif
            </div>

            <div class="flex items-center space-x-2">
                <label class="cursor-pointer p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-500 dark:text-gray-400">
                    <input type="file" wire:model="attachment" class="hidden">
                    <x-heroicon-o-paper-clip class="w-5 h-5" />
                </label>

                <button
                    type="submit"
                    class="inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                    <x-heroicon-m-paper-airplane class="w-5 h-5" />
                </button>
            </div>
        </form>
    </div>
</div>
