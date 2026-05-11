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

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                <template x-for="member in members" :key="member.id">
                    <div class="flex items-center p-2.5 space-x-3 bg-gray-50/50 dark:bg-gray-800/30 border border-gray-100 dark:border-gray-700 rounded-xl transition-all hover:border-emerald-500/30 group">
                        <div class="flex-shrink-0 relative">
                            <div class="w-8 h-8 rounded-full bg-emerald-600 flex items-center justify-center text-white text-xs font-bold" x-text="member.name.charAt(0)"></div>
                            <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-green-500 border-2 border-white dark:border-gray-800 rounded-full"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-gray-900 truncate dark:text-white" x-text="member.name"></p>
                            <p class="text-[10px] text-gray-500 truncate dark:text-gray-400" x-text="member.membership_number"></p>
                            <p class="mt-0.5 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 opacity-0 group-hover:opacity-100 transition-opacity" x-text="member.activity"></p>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="members.length === 0" class="py-4 text-center text-gray-500 italic">
                No members currently online.
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
