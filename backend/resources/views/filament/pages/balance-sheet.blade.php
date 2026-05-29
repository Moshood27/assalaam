@php($r = $this->report)
<x-filament::page>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
            <div class="w-full sm:w-auto">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">As of</label>
                <input type="date" wire:model.live="as_of" class="fi-input fi-input-base w-full sm:w-56 text-sm" />
            </div>
            <div class="sm:ml-auto flex gap-2 pt-2 sm:pt-0">
                <x-filament::button wire:click="refreshReport">Refresh</x-filament::button>
                <x-filament::button color="gray" wire:click="exportCsv">Export CSV</x-filament::button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
                <div class="px-5 py-3 text-sm font-semibold text-gray-700 dark:text-gray-100 bg-gray-50 dark:bg-gray-800/60">Assets</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($r['assets'] as $line)
                                <tr class="odd:bg-white even:bg-gray-50 dark:odd:bg-gray-900 dark:even:bg-gray-800">
                                    <td class="px-5 py-3 text-gray-900 dark:text-gray-100">{{ $line['name'] }}</td>
                                    <td class="px-5 py-3 text-right font-mono tabular-nums text-gray-900 dark:text-gray-100">₦ {{ number_format($line['amount'] ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-800/60">
                            <tr>
                                <th class="px-5 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Total Assets</th>
                                <th class="px-5 py-3 text-right font-mono tabular-nums text-gray-900 dark:text-gray-100">₦ {{ number_format($r['total_assets'] ?? 0, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden">
                <div class="px-5 py-3 text-sm font-semibold text-gray-700 dark:text-gray-100 bg-gray-50 dark:bg-gray-800/60">Liabilities & Equity</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($r['liabilities'] as $line)
                                <tr class="odd:bg-white even:bg-gray-50 dark:odd:bg-gray-900 dark:even:bg-gray-800">
                                    <td class="px-5 py-3 text-gray-900 dark:text-gray-100">{{ $line['name'] }}</td>
                                    <td class="px-5 py-3 text-right font-mono tabular-nums text-gray-900 dark:text-gray-100">₦ {{ number_format($line['amount'] ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-800/60">
                            <tr>
                                <th class="px-5 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Total Liabilities & Equity</th>
                                <th class="px-5 py-3 text-right font-mono tabular-nums text-gray-900 dark:text-gray-100">₦ {{ number_format($r['total_liabilities_and_equity'] ?? 0, 2) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-filament::page>
