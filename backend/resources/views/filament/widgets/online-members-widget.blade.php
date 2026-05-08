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

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="member in members" :key="member.id">
                    <div class="flex items-center p-3 space-x-4 border rounded-lg dark:border-gray-700">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full bg-primary-500 flex items-center justify-center text-white font-bold" x-text="member.name.charAt(0)"></div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate dark:text-white" x-text="member.name"></p>
                            <p class="text-xs text-gray-500 truncate dark:text-gray-400" x-text="member.membership_number"></p>
                            <p class="mt-1 text-xs font-semibold text-primary-600 dark:text-primary-400" x-text="member.activity"></p>
                        </div>
                        <div class="inline-flex items-center text-base font-semibold text-gray-900 dark:text-white">
                            <span class="w-3 h-3 bg-green-500 rounded-full"></span>
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
