@php($report = $this->report)
<x-filament::page>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 no-print">
            <div class="flex flex-wrap items-center gap-4 w-full sm:w-auto">
                @if(auth()->user()->hasRole('super_admin'))
                    <div class="min-w-[200px]">
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Branch</label>
                        <select wire:model.live="branchId" class="fi-select-input block w-full rounded-lg border-none bg-white py-1.5 text-base text-gray-950 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/10 dark:focus:ring-primary-500 sm:text-sm sm:leading-6">
                            <option value="">All Branches</option>
                            @foreach(\App\Models\Branch::all() as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="min-w-[150px]">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">From Date</label>
                    <input type="date" wire:model.live="from" class="fi-input block w-full rounded-lg border-none bg-white py-1.5 text-base text-gray-950 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/10 dark:focus:ring-primary-500 sm:text-sm sm:leading-6">
                </div>
                <div class="min-w-[150px]">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">To Date</label>
                    <input type="date" wire:model.live="to" class="fi-input block w-full rounded-lg border-none bg-white py-1.5 text-base text-gray-950 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/10 dark:focus:ring-primary-500 sm:text-sm sm:leading-6">
                </div>
            </div>
            <div class="flex gap-2 ml-auto">
                <x-filament::button wire:click="refreshReport" color="gray" icon="heroicon-m-arrow-path">Refresh</x-filament::button>
                <x-filament::button color="gray" icon="heroicon-m-printer" onclick="window.print()">Print</x-filament::button>
                <x-filament::button color="primary" icon="heroicon-m-arrow-down-tray" wire:click="exportCsv">Export CSV</x-filament::button>
            </div>
        </div>

        @forelse($report['branches'] as $branch)
            <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden break-inside-avoid mb-8">
                <div class="bg-gray-50 dark:bg-gray-800/60 px-5 py-3 border-b border-gray-200 dark:border-gray-800 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $branch['branch_name'] }}</h3>
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                        Total for Branch: <span class="text-primary-600 dark:text-primary-400 font-bold">₦{{ number_format($branch['branch_total'], 2) }}</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-xs">
                        <thead>
                            <tr class="bg-gray-50/50 dark:bg-gray-800/40">
                                <th class="px-3 py-3 text-left font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider sticky left-0 bg-gray-50 dark:bg-gray-800 z-10">Member</th>
                                <th class="px-3 py-3 text-left font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Membership #</th>
                                @foreach($report['schemes'] as $scheme)
                                    <th class="px-3 py-3 text-right font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">{{ $scheme['name'] }}</th>
                                @endforeach
                                <th class="px-3 py-3 text-right font-bold text-gray-900 dark:text-white uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($branch['members'] as $member)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="px-3 py-2 text-gray-900 dark:text-gray-100 font-medium sticky left-0 bg-white dark:bg-gray-900 z-10">{{ $member['member_name'] }}</td>
                                    <td class="px-3 py-2 text-gray-600 dark:text-gray-400 font-mono text-[10px]">{{ $member['membership_number'] }}</td>
                                    @foreach($report['schemes'] as $scheme)
                                        <td class="px-3 py-2 text-right tabular-nums text-gray-700 dark:text-gray-300">
                                            {{ $member['schemes'][$scheme['id']] > 0 ? '₦' . number_format($member['schemes'][$scheme['id']], 0) : '-' }}
                                        </td>
                                    @endforeach
                                    <td class="px-3 py-2 text-right tabular-nums font-bold text-gray-900 dark:text-gray-100 bg-gray-50/30 dark:bg-gray-800/20">
                                        ₦{{ number_format($member['total'], 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50/50 dark:bg-gray-800/40 font-bold border-t border-gray-200 dark:border-gray-800">
                            <tr>
                                <td colspan="2" class="px-3 py-4 text-gray-900 dark:text-gray-100 uppercase tracking-tight">Branch Totals</td>
                                @foreach($report['schemes'] as $scheme)
                                    <td class="px-3 py-4 text-right tabular-nums text-gray-900 dark:text-gray-100">
                                        ₦{{ number_format($branch['totals'][$scheme['id']], 0) }}
                                    </td>
                                @endforeach
                                <td class="px-3 py-4 text-right tabular-nums text-primary-600 dark:text-primary-400 bg-gray-50 dark:bg-gray-800">
                                    ₦{{ number_format($branch['branch_total'], 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center p-12 bg-white dark:bg-gray-900 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 no-print">
                <p class="text-gray-500 dark:text-gray-400 font-medium">No contribution data found for schemes.</p>
            </div>
        @endforelse

        @if(count($report['branches']) > 1)
            <div class="rounded-xl border-2 border-primary-500 dark:border-primary-400 bg-primary-50/50 dark:bg-primary-900/20 p-6 break-inside-avoid shadow-lg">
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-6">Organization Grand Totals</h3>
                <div class="overflow-x-auto mb-6">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase text-gray-500 dark:text-gray-400 font-bold">
                                <th class="pb-3 pr-4">Schemes</th>
                                @foreach($report['schemes'] as $scheme)
                                    <th class="pb-3 px-4 text-right">{{ $scheme['name'] }}</th>
                                @endforeach
                                <th class="pb-3 pl-4 text-right text-primary-600 dark:text-primary-400">Total Contribution</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="text-lg font-black text-gray-900 dark:text-gray-100 border-t border-gray-200 dark:border-gray-700 pt-4">
                                <td class="py-4 pr-4">All Branches</td>
                                @foreach($report['schemes'] as $scheme)
                                    <td class="py-4 px-4 text-right tabular-nums">₦{{ number_format($report['grand_totals'][$scheme['id']], 0) }}</td>
                                @endforeach
                                <td class="py-4 pl-4 text-right tabular-nums text-primary-700 dark:text-primary-300">₦{{ number_format($report['grand_total_all'], 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <span class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Total Contributing Members:</span>
                    <span class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ $report['grand_total_members_count'] }}</span>
                </div>
            </div>
        @endif
    </div>

    <style>
        @media print {
            .no-print, .fi-sidebar, .fi-topbar, .fi-footer {
                display: none !important;
            }
            .fi-main {
                padding: 0 !important;
                margin: 0 !important;
            }
            body {
                background: white !important;
            }
            .rounded-xl {
                border-radius: 0 !important;
            }
            .shadow-sm, .shadow-lg {
                box-shadow: none !important;
            }
            .break-inside-avoid {
                break-inside: avoid;
            }
            table {
                font-size: 8px !important;
            }
            th, td {
                padding: 4px !important;
            }
            .sticky {
                position: static !important;
            }
        }
    </style>
</x-filament::page>
