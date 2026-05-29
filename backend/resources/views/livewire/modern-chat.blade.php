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
            @if($this->assignedStaff)
                <div class="flex items-center space-x-1 px-2 py-1 bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700 rounded-full" title="Staff responsible for this room (Amanah)">
                    <div class="w-2 h-2 rounded-full bg-green-500"></div>
                    <span class="text-[10px] font-medium text-green-700 dark:text-green-300">
                        {{ $this->assignedStaff->name }}
                    </span>
                </div>
            @elseif(auth()->user()->hasAnyRole(['Staff', 'Admin']) && $chatRoom->type === 'support')
                <button wire:click="assignToMe" class="flex items-center space-x-1 px-2 py-1 bg-amber-50 dark:bg-amber-900 border border-amber-200 dark:border-amber-700 rounded-full hover:bg-amber-100 transition">
                    <x-heroicon-m-user-plus class="w-3 h-3 text-amber-500" />
                    <span class="text-[10px] font-medium text-amber-700 dark:text-amber-300">
                        Take Responsibility
                    </span>
                </button>
            @endif

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

                        @if($message->metadata['is_flagged'] ?? false)
                            <div class="mt-1 flex items-center text-[10px] text-red-200 font-bold bg-red-900 bg-opacity-30 px-1 rounded">
                                <x-heroicon-m-exclamation-triangle class="w-3 h-3 mr-1" />
                                Adab Violation Flagged
                            </div>
                        @endif

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
        <!-- Quick Actions -->
        <div class="flex items-center space-x-2 mb-3 overflow-x-auto pb-1 no-scrollbar">
            <button wire:click="sendGreeting('Assalamu Alaikum')" class="flex-shrink-0 px-2 py-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-[10px] font-medium hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                Assalamu Alaikum
            </button>
            <button wire:click="sendGreeting('Wa Alaikum Salam')" class="flex-shrink-0 px-2 py-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-[10px] font-medium hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                Wa Alaikum Salam
            </button>
            <button wire:click="sendGreeting('JazakAllah Khair')" class="flex-shrink-0 px-2 py-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-[10px] font-medium hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                JazakAllah Khair
            </button>
            <button wire:click="sendGreeting('BarakAllah Feek')" class="flex-shrink-0 px-2 py-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded text-[10px] font-medium hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                BarakAllah Feek
            </button>

            <div class="h-4 w-px bg-gray-300 dark:bg-gray-600 mx-1"></div>

            <!-- Canned Responses Toggle -->
            <div class="relative" x-data="{ open: @entangle('showCannedResponses') }">
                <button @click="open = !open" class="flex-shrink-0 px-2 py-1 bg-primary-50 dark:bg-primary-900 border border-primary-200 dark:border-primary-700 rounded text-[10px] font-medium text-primary-700 dark:text-primary-300 hover:bg-primary-100 transition">
                    Canned Responses
                </button>
                <div x-show="open" @click.away="open = false" class="absolute bottom-full mb-2 left-0 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50 max-h-48 overflow-y-auto">
                    @foreach(app(\App\Services\ChatService::class)->getCannedResponses() as $title => $msg)
                        <button wire:click="sendCannedResponse('{{ $title }}', '{{ $msg }}')" class="w-full text-left px-3 py-2 text-xs hover:bg-gray-100 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-700 last:border-0">
                            <span class="font-bold">{{ $title }}</span>
                            <p class="text-gray-500 truncate">{{ $msg }}</p>
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Fintech Actions Toggle -->
            <div class="relative" x-data="{ open: @entangle('showFintechActions') }">
                <button @click="open = !open" class="flex-shrink-0 px-2 py-1 bg-amber-50 dark:bg-amber-900 border border-amber-200 dark:border-amber-700 rounded text-[10px] font-medium text-amber-700 dark:text-amber-300 hover:bg-amber-100 transition">
                    Fintech Ready
                </button>
                <div x-show="open" @click.away="open = false" class="absolute bottom-full mb-2 left-0 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50 p-2">
                    <h4 class="text-[10px] font-bold uppercase text-gray-500 mb-2 px-1">Financial Actions (Amanah)</h4>
                    <button wire:click="sendTransactionCard(5000, 'Contribution Payment')" class="w-full text-left px-3 py-2 text-xs hover:bg-gray-100 dark:hover:bg-gray-700 rounded flex items-center">
                        <x-heroicon-o-credit-card class="w-4 h-4 mr-2 text-amber-500" />
                        Send Payment Request (â‚¦5k)
                    </button>
                    <button wire:click="sendApprovalRequest('Loan Agreement Approval')" class="w-full text-left px-3 py-2 text-xs hover:bg-gray-100 dark:hover:bg-gray-700 rounded flex items-center">
                        <x-heroicon-o-pencil-square class="w-4 h-4 mr-2 text-primary-500" />
                        Request Digital Signature
                    </button>
                </div>
            </div>
        </div>

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
