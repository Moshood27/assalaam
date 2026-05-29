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

    <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/60">
                        <th class="px-5 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-100 tracking-wide">Account</th>
                        <th class="px-5 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-100 tracking-wide">Debit</th>
                        <th class="px-5 py-3 text-right text-sm font-semibold text-gray-700 dark:text-gray-100 tracking-wide">Credit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($r['accounts'] as $name => $row)
                        <tr class="odd:bg-white even:bg-gray-50 dark:odd:bg-gray-900 dark:even:bg-gray-800">
                            <td class="px-5 py-3 text-gray-900 dark:text-gray-100">{{ $name }}</td>
                            <td class="px-5 py-3 text-right font-mono tabular-nums text-gray-900 dark:text-gray-100">â‚¦ {{ number_format($row['debit'] ?? 0, 2) }}</td>
                            <td class="px-5 py-3 text-right font-mono tabular-nums text-gray-900 dark:text-gray-100">â‚¦ {{ number_format($row['credit'] ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-800/60">
                    <tr>
                        <th class="px-5 py-3 text-left text-sm font-semibold text-gray-900 dark:text-gray-100">Total</th>
                        <th class="px-5 py-3 text-right font-mono tabular-nums text-gray-900 dark:text-gray-100">â‚¦ {{ number_format($r['total_debit'] ?? 0, 2) }}</th>
                        <th class="px-5 py-3 text-right font-mono tabular-nums text-gray-900 dark:text-gray-100">â‚¦ {{ number_format($r['total_credit'] ?? 0, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div>
        @if(($r['balanced'] ?? false))
            <span class="inline-flex items-center gap-1 rounded-full bg-green-50 px-3 py-1 text-sm font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                Balanced
            </span>
        @else
            <span class="inline-flex items-center gap-1 rounded-full bg-red-50 px-3 py-1 text-sm font-medium text-red-700 ring-1 ring-inset ring-red-600/20">
                Not Balanced
            </span>
        @endif
    </div>
</div>
</x-filament::page>
