<x-filament-widgets::widget>
    <x-filament::section>
        <div
            x-data="{
                members: [],
                init() {
                    window.Echo.join('online-members')
                        .here((users) => {
                            this.members = users;
                        })
                        .joining((user) => {
                            this.members.push(user);
                        })
                        .leaving((user) => {
                            this.members = this.members.filter(m => m.id != user.id);
                        })
                        .listenForWhisper('activity', (e) => {
                            const member = this.members.find(m => m.id == e.id);
                            if (member) {
                                member.activity = e.activity;
                            }
                        });
                }
            }"
        >
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold">Online Members</h2>
                <span class="px-2 py-1 text-xs font-semibold text-green-700 bg-green-100 rounded-full" x-text="members.length + ' Online'"></span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <template x-for="member in members.slice(0, 12)" :key="member.id">
                    <div class="flex items-center p-3 space-x-3 border rounded-lg dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 rounded-full bg-primary-500 flex items-center justify-center text-white text-xs font-bold" x-text="member.name.charAt(0)"></div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-medium text-gray-900 truncate dark:text-white" x-text="member.name"></p>
                            <p class="text-[10px] text-gray-500 truncate dark:text-gray-400" x-text="member.membership_number"></p>
                        </div>
                        <div class="inline-flex items-center">
                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="members.length > 12" class="mt-4 text-center text-xs text-gray-500">
                ... and <span x-text="members.length - 12"></span> more members online
            </div>

            <div x-show="members.length === 0" class="py-4 text-center text-gray-500 italic">
                No members currently online.
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
