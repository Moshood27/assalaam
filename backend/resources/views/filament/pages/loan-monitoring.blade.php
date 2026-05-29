<x-filament::page>
<div class="space-y-8">
    <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
        <h2 class="text-xl font-semibold">Members on Loan</h2>
        <div class="sm:ml-auto flex gap-2">
            <x-filament::button wire:click="refreshData" color="gray">Refresh</x-filament::button>
            <x-filament::button color="danger" wire:click="sendAllDefaultersReminders">Send All Defaulters Reminders</x-filament::button>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/60">
                        <th class="px-5 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-100">Member</th>
                        <th class="px-5 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-100">Branch</th>
                        <th class="px-5 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-100">Email</th>
                        <th class="px-5 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-100">Loans</th>
                        <th class="px-5 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-100">Received At</th>
                        <th class="px-5 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-100">Overdue (â‚¦)</th>
                        <th class="px-5 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-100">Outstanding (â‚¦)</th>
                        <th class="px-5 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-100">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($this->membersOnLoan as $m)
                        <tr class="odd:bg-white even:bg-gray-50 dark:odd:bg-gray-900 dark:even:bg-gray-800">
                            <td class="px-5 py-3 text-gray-900 dark:text-gray-100">
                                <div class="flex items-center gap-2">
                                    <span>{{ $m['name'] }}</span>
                                    @if($m['is_defaulter'])
                                        <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20">Defaulter</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3 text-gray-900 dark:text-gray-100">{{ $m['branch'] ?? 'â€”' }}</td>
                            <td class="px-5 py-3 text-gray-900 dark:text-gray-100">{{ $m['email'] ?? 'â€”' }}</td>
                            <td class="px-5 py-3 text-right text-gray-900 dark:text-gray-100">{{ $m['loans_count'] }}</td>
                            <td class="px-5 py-3 text-left text-gray-900 dark:text-gray-100">{{ $m['received_at'] }}</td>
                            <td class="px-5 py-3 text-right font-mono tabular-nums text-red-600 dark:text-red-400 font-bold">
                                {{ number_format($m['overdue'], 2) }}
                            </td>
                            <td class="px-5 py-3 text-right font-mono tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($m['outstanding'], 2) }}</td>
                            <td class="px-5 py-3 text-right">
                                <x-filament::button size="sm" wire:click="sendReminder({{ $m['id'] }})">Send Reminder</x-filament::button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-6 text-center text-gray-500 dark:text-gray-400">No members with active or pending loans.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex items-center">
        <h2 class="text-xl font-semibold">Defaulters</h2>
        <div class="ml-auto">
            <x-filament::button color="danger" wire:click="sendAllDefaultersReminders">Send All Defaulters Reminders</x-filament::button>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/60">
                        <th class="px-5 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-100">Member</th>
                        <th class="px-5 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-100">Branch</th>
                        <th class="px-5 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-100">Loans</th>
                        <th class="px-5 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-100">Default Since</th>
                        <th class="px-5 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-100">Overdue (â‚¦)</th>
                        <th class="px-5 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-100">Outstanding (â‚¦)</th>
                        <th class="px-5 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-100">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($this->defaulters as $d)
                        <tr class="odd:bg-white even:bg-gray-50 dark:odd:bg-gray-900 dark:even:bg-gray-800">
                            <td class="px-5 py-3 text-gray-900 dark:text-gray-100">{{ $d['name'] }}</td>
                            <td class="px-5 py-3 text-gray-900 dark:text-gray-100">{{ $d['branch'] ?? 'â€”' }}</td>
                            <td class="px-5 py-3 text-right text-gray-900 dark:text-gray-100">{{ $d['loans_count'] }}</td>
                            <td class="px-5 py-3 text-left text-gray-900 dark:text-gray-100">{{ $d['defaulted_at'] }}</td>
                            <td class="px-5 py-3 text-right font-mono tabular-nums text-red-600 dark:text-red-400 font-bold">
                                {{ number_format($d['overdue'], 2) }}
                            </td>
                            <td class="px-5 py-3 text-right font-mono tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($d['outstanding'], 2) }}</td>
                            <td class="px-5 py-3 text-right">
                                <x-filament::button size="sm" color="danger" wire:click="sendReminder({{ $d['id'] }})">Send Reminder</x-filament::button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-6 text-center text-gray-500 dark:text-gray-400">No defaulters have been flagged.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-filament::page>
