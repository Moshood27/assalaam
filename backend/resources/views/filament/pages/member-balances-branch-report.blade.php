@php($report = $this->report)
<x-filament::page>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 no-print">
            @if(auth()->user()->hasRole('super_admin'))
                <div class="w-full sm:w-1/3">
                    <select wire:model.live="branchId" class="fi-select-input block w-full rounded-lg border-none bg-white py-1.5 text-base text-gray-950 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/10 dark:focus:ring-primary-500 sm:text-sm sm:leading-6">
                        <option value="">All Branches</option>
                        @foreach(\App\Models\Branch::all() as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div></div>
            @endif
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
                        Total Wealth: <span class="text-primary-600 dark:text-primary-400 font-bold">₦{{ number_format($branch['total_savings'] + $branch['total_special_savings'] + $branch['total_shares'] + $branch['total_gold_value'] + $branch['total_other'], 2) }}</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                        <thead>
                            <tr class="bg-gray-50/50 dark:bg-gray-800/40">
                                <th class="px-5 py-3 text-left font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Member</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Membership #</th>
                                <th class="px-5 py-3 text-right font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Savings</th>
                                <th class="px-5 py-3 text-right font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Special Savings</th>
                                <th class="px-5 py-3 text-right font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Shares</th>
                                <th class="px-5 py-3 text-right font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Gold (Val)</th>
                                <th class="px-5 py-3 text-right font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Other Funds</th>
                                <th class="px-5 py-3 text-right font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($branch['members'] as $member)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="px-5 py-3 text-gray-900 dark:text-gray-100 font-medium">{{ $member['member_name'] }}</td>
                                    <td class="px-5 py-3 text-gray-600 dark:text-gray-400 font-mono text-xs">{{ $member['membership_number'] }}</td>
                                    <td class="px-5 py-3 text-right tabular-nums text-gray-900 dark:text-gray-100">₦{{ number_format($member['savings'], 2) }}</td>
                                    <td class="px-5 py-3 text-right tabular-nums text-gray-900 dark:text-gray-100">₦{{ number_format($member['special_savings'], 2) }}</td>
                                    <td class="px-5 py-3 text-right tabular-nums text-gray-900 dark:text-gray-100">₦{{ number_format($member['shares'], 2) }}</td>
                                    <td class="px-5 py-3 text-right tabular-nums text-gray-900 dark:text-gray-100" title="{{ number_format($member['gold_weight'], 2) }}g">₦{{ number_format($member['gold_value'], 2) }}</td>
                                    <td class="px-5 py-3 text-right tabular-nums text-gray-900 dark:text-gray-100">₦{{ number_format($member['other_funds'], 2) }}</td>
                                    <td class="px-5 py-3 text-right tabular-nums font-bold text-primary-600 dark:text-primary-400">₦{{ number_format($member['total_wealth'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50/50 dark:bg-gray-800/40 font-bold border-t border-gray-200 dark:border-gray-800">
                            <tr>
                                <td colspan="2" class="px-5 py-4 text-gray-900 dark:text-gray-100 uppercase tracking-tight">Branch Totals</td>
                                <td class="px-5 py-4 text-right tabular-nums">₦{{ number_format($branch['total_savings'], 2) }}</td>
                                <td class="px-5 py-4 text-right tabular-nums">₦{{ number_format($branch['total_special_savings'], 2) }}</td>
                                <td class="px-5 py-4 text-right tabular-nums">₦{{ number_format($branch['total_shares'], 2) }}</td>
                                <td class="px-5 py-4 text-right tabular-nums">₦{{ number_format($branch['total_gold_value'], 2) }}</td>
                                <td class="px-5 py-4 text-right tabular-nums">₦{{ number_format($branch['total_other'], 2) }}</td>
                                <td class="px-5 py-4 text-right tabular-nums text-primary-600 dark:text-primary-400">₦{{ number_format($branch['total_savings'] + $branch['total_special_savings'] + $branch['total_shares'] + $branch['total_gold_value'] + $branch['total_other'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center p-12 bg-white dark:bg-gray-900 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 no-print">
                <p class="text-gray-500 dark:text-gray-400 font-medium">No member balances found.</p>
            </div>
        @endforelse

        @if(count($report['branches']) > 1)
            <div class="rounded-xl border-2 border-primary-500 dark:border-primary-400 bg-primary-50/50 dark:bg-primary-900/20 p-6 break-inside-avoid">
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">Organization Grand Totals</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-7 gap-4">
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Members</div>
                        <div class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ $report['grand_total_members_count'] }}</div>
                    </div>
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Total Savings</div>
                        <div class="text-xl font-bold text-gray-900 dark:text-gray-100">₦{{ number_format($report['grand_total_savings'], 2) }}</div>
                    </div>
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Special Savings</div>
                        <div class="text-xl font-bold text-gray-900 dark:text-gray-100">₦{{ number_format($report['grand_total_special_savings'], 2) }}</div>
                    </div>
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Total Shares</div>
                        <div class="text-xl font-bold text-gray-900 dark:text-gray-100">₦{{ number_format($report['grand_total_shares'], 2) }}</div>
                    </div>
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Total Gold (Val)</div>
                        <div class="text-xl font-bold text-gray-900 dark:text-gray-100">₦{{ number_format($report['grand_total_gold_value'], 2) }}</div>
                    </div>
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Other Funds</div>
                        <div class="text-xl font-bold text-gray-900 dark:text-gray-100">₦{{ number_format($report['grand_total_other'], 2) }}</div>
                    </div>
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-primary-200 dark:border-primary-800 ring-2 ring-primary-500/20">
                        <div class="text-xs text-primary-600 dark:text-primary-400 uppercase font-bold">Grand Total</div>
                        <div class="text-xl font-black text-primary-700 dark:text-primary-300">₦{{ number_format($report['grand_total_savings'] + $report['grand_total_special_savings'] + $report['grand_total_shares'] + $report['grand_total_gold_value'] + $report['grand_total_other'], 2) }}</div>
                    </div>
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
            .shadow-sm {
                box-shadow: none !important;
            }
            .break-inside-avoid {
                break-inside: avoid;
            }
        }
    </style>
</x-filament::page>
