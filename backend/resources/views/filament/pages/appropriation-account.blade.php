@php($r = $this->report)
<x-filament::page>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
            <div class="w-full sm:w-auto">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">From</label>
                <input type="date" wire:model.live="from" class="fi-input fi-input-base w-full sm:w-56 text-sm" />
            </div>
            <div class="w-full sm:w-auto">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">To</label>
                <input type="date" wire:model.live="to" class="fi-input fi-input-base w-full sm:w-56 text-sm" />
            </div>
            <div class="sm:ml-auto flex gap-2 pt-2 sm:pt-0">
                <x-filament::button wire:click="refreshReport">Refresh</x-filament::button>
                <x-filament::button color="gray" wire:click="exportCsv">Export CSV</x-filament::button>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        <tr class="bg-gray-50 dark:bg-gray-800/60">
                            <th class="px-5 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Surplus for the Period</th>
                            <th class="px-5 py-3 text-right font-mono tabular-nums text-gray-900 dark:text-gray-100">â‚¦ {{ number_format($r['surplus'] ?? 0, 2) }}</th>
                        </tr>
                        <tr>
                            <td colspan="2" class="px-5 py-2 text-xs text-gray-500 dark:text-gray-400">Appropriations</td>
                        </tr>
                        @forelse(($r['appropriations'] ?? []) as $line)
                            <tr class="odd:bg-white even:bg-gray-50 dark:odd:bg-gray-900 dark:even:bg-gray-800">
                                <td class="px-5 py-3 text-gray-900 dark:text-gray-100">
                                    {{ $line['name'] ?? '' }}
                                    @if(isset($line['percent']))
                                        <span class="text-gray-500 dark:text-gray-400">({{ number_format($line['percent'], 2) }}%)</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right font-mono tabular-nums text-gray-900 dark:text-gray-100">â‚¦ {{ number_format($line['amount'] ?? 0, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400" colspan="2">No appropriation ratios configured. Entire surplus will be carried forward.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-50 dark:bg-gray-800/60">
                        <tr>
                            <th class="px-5 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Total Appropriations</th>
                            <th class="px-5 py-3 text-right font-mono tabular-nums text-gray-900 dark:text-gray-100">â‚¦ {{ number_format($r['total_appropriated'] ?? 0, 2) }}</th>
                        </tr>
                        <tr>
                            <th class="px-5 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Carried Forward</th>
                            <th class="px-5 py-3 text-right font-mono tabular-nums text-gray-900 dark:text-gray-100">â‚¦ {{ number_format($r['carried_forward'] ?? 0, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-200 p-4">
            <div class="text-sm">
                Tip: Define appropriation ratios in your .env as APPROPRIATION_RATIOS, e.g.
                <code>[{"name":"Statutory Reserve","percent":25},{"name":"General Reserve","percent":10}]</code>
            </div>
        </div>
    </div>
</x-filament::page>
