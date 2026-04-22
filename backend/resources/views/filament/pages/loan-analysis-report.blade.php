@php($report = $this->report)
<x-filament::page>
    <div class="space-y-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 no-print">
            <div class="flex flex-wrap items-center gap-4 w-full sm:w-auto">
                @if(auth()->user()->hasRole('super_admin'))
                    <div class="min-w-[150px]">
                        <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Branch</label>
                        <select wire:model.live="branchId" class="fi-select-input block w-full rounded-lg border-none bg-white py-1.5 text-base text-gray-950 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/10 dark:focus:ring-primary-500 sm:text-sm sm:leading-6">
                            <option value="">All Branches</option>
                            @foreach(\App\Models\Branch::all() as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="min-w-[120px]">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">As At Date</label>
                    <input type="date" wire:model.live="date" class="fi-input block w-full rounded-lg border-none bg-white py-1.5 text-base text-gray-950 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/10 dark:focus:ring-primary-500 sm:text-sm sm:leading-6">
                </div>
                <div class="min-w-[200px]">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Search Member</label>
                    <input type="text" wire:model.live.debounce.500ms="search" placeholder="Name or Membership #" class="fi-input block w-full rounded-lg border-none bg-white py-1.5 text-base text-gray-950 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/10 dark:focus:ring-primary-500 sm:text-sm sm:leading-6">
                </div>
            </div>
            <div class="flex gap-2 ml-auto">
                <x-filament::button wire:click="refreshReport" color="gray" icon="heroicon-m-arrow-path">Refresh</x-filament::button>
                <x-filament::button color="gray" icon="heroicon-m-printer" onclick="window.print()">Print</x-filament::button>
                <x-filament::button color="primary" icon="heroicon-m-arrow-down-tray" wire:click="exportCsv">Export CSV</x-filament::button>
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm overflow-hidden break-inside-avoid mb-8">
            <div class="bg-gray-50 dark:bg-gray-800/60 px-5 py-6 border-b border-gray-200 dark:border-gray-800 text-center">
                <h2 class="text-2xl font-black text-gray-900 dark:text-gray-100 uppercase tracking-tight">{{ $report['cooperative_name'] }}</h2>
                <h3 class="text-lg font-bold text-gray-700 dark:text-gray-300 uppercase mt-1">LOAN ANALYSIS REPORT</h3>
                <h4 class="text-md font-medium text-gray-500 dark:text-gray-400 uppercase">AS AT MONTH OF {{ strtoupper($report['month']) }} {{ $report['year'] }}</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-[10px] sm:text-xs">
                    <thead>
                        <tr class="bg-gray-50/50 dark:bg-gray-800/40">
                            <th class="px-2 py-3 text-left font-bold text-gray-700 dark:text-gray-200 uppercase">S/N</th>
                            <th class="px-2 py-3 text-left font-bold text-gray-700 dark:text-gray-200 uppercase">NAME OF MEMBERS AND BRANCH</th>
                            <th class="px-2 py-3 text-center font-bold text-gray-700 dark:text-gray-200 uppercase">DATE GRANTED</th>
                            <th class="px-2 py-3 text-right font-bold text-gray-700 dark:text-gray-200 uppercase">LOAN GRANTED</th>
                            <th class="px-2 py-3 text-right font-bold text-gray-700 dark:text-gray-200 uppercase">AMOUNT REPAID</th>
                            <th class="px-2 py-3 text-right font-bold text-gray-700 dark:text-gray-200 uppercase">EXPECTED AMOUNT TO PAY</th>
                            <th class="px-2 py-3 text-right font-bold text-gray-700 dark:text-gray-200 uppercase">AMOUNT DEFAULTED</th>
                            <th class="px-2 py-3 text-right font-bold text-gray-700 dark:text-gray-200 uppercase">LOAN BALANCE</th>
                            <th class="px-2 py-3 text-right font-bold text-gray-700 dark:text-gray-200 uppercase">SHARE/SAVINGS BALANCE</th>
                            <th class="px-2 py-3 text-center font-bold text-gray-700 dark:text-gray-200 uppercase">PHONE NUMBER</th>
                            <th class="px-2 py-3 text-center font-bold text-gray-700 dark:text-gray-200 uppercase">PERIOD OF DEFAULT</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse($report['rows'] as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                <td class="px-2 py-3 text-gray-900 dark:text-gray-100">{{ $row['sn'] }}</td>
                                <td class="px-2 py-3 text-gray-900 dark:text-gray-100 font-medium">
                                    {{ $row['member_name'] }}
                                    <span class="block text-[9px] text-gray-500 uppercase">{{ $row['branch_name'] }}</span>
                                </td>
                                <td class="px-2 py-3 text-center text-gray-600 dark:text-gray-400">
                                    {{ $row['date_granted'] ? ($row['date_granted'] instanceof \Carbon\Carbon ? $row['date_granted']->format('d/m/Y') : \Illuminate\Support\Carbon::parse($row['date_granted'])->format('d/m/Y')) : 'N/A' }}
                                </td>
                                <td class="px-2 py-3 text-right tabular-nums text-gray-900 dark:text-gray-100">₦{{ number_format($row['loan_granted'], 2) }}</td>
                                <td class="px-2 py-3 text-right tabular-nums text-gray-900 dark:text-gray-100">₦{{ number_format($row['amount_repaid'], 2) }}</td>
                                <td class="px-2 py-3 text-right tabular-nums text-gray-900 dark:text-gray-100 font-semibold">₦{{ number_format($row['expected_amount_to_pay'], 2) }}</td>
                                <td class="px-2 py-3 text-right tabular-nums @if($row['amount_defaulted'] > 0) text-red-600 font-bold @endif">₦{{ number_format($row['amount_defaulted'], 2) }}</td>
                                <td class="px-2 py-3 text-right tabular-nums font-bold text-primary-600">₦{{ number_format($row['loan_balance'], 2) }}</td>
                                <td class="px-2 py-3 text-right tabular-nums text-gray-900 dark:text-gray-100">₦{{ number_format($row['savings_balance'], 2) }}</td>
                                <td class="px-2 py-3 text-center text-gray-600 dark:text-gray-400">{{ $row['phone_number'] }}</td>
                                <td class="px-2 py-3 text-center">
                                    <span @class([
                                        'px-1.5 py-0.5 rounded text-[10px] font-bold',
                                        //'bg-red-100 text-red-700' => $row['period_of_default'] !== 'None',
                                       // 'bg-gray-100 text-gray-600' => $row['period_of_default'] === 'None',
                                        'bg-gray-100 text-gray-600' => $row['period_of_default']
                                    ])>
                                        {{ $row['period_of_default'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-5 py-12 text-center text-gray-500 dark:text-gray-400">
                                    No loans found for the selected criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(!empty($report['rows']))
                        <tfoot class="bg-gray-50/50 dark:bg-gray-800/40 border-t border-gray-200 dark:border-gray-800">
                            <tr class="font-bold text-gray-900 dark:text-gray-100">
                                <td colspan="3" class="px-2 py-3 text-center uppercase tracking-wider">Total</td>
                                <td class="px-2 py-3 text-right tabular-nums">₦{{ number_format($report['totals']['loan_granted'], 2) }}</td>
                                <td class="px-2 py-3 text-right tabular-nums">₦{{ number_format($report['totals']['amount_repaid'], 2) }}</td>
                                <td class="px-2 py-3 text-right tabular-nums">₦{{ number_format($report['totals']['expected_amount_to_pay'], 2) }}</td>
                                <td class="px-2 py-3 text-right tabular-nums text-red-600">₦{{ number_format($report['totals']['amount_defaulted'], 2) }}</td>
                                <td class="px-2 py-3 text-right tabular-nums text-primary-600">₦{{ number_format($report['totals']['loan_balance'], 2) }}</td>
                                <td class="px-2 py-3 text-right tabular-nums">₦{{ number_format($report['totals']['savings_balance'], 2) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
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
                font-size: 8px !important;
            }
            .rounded-xl {
                border-radius: 0 !important;
                border: 1px solid #eee !important;
            }
            .shadow-sm {
                box-shadow: none !important;
            }
            .break-inside-avoid {
                break-inside: avoid;
            }
            table {
                width: 100% !important;
                border-collapse: collapse !important;
            }
            th, td {
                border: 0.5px solid #ddd !important;
                padding: 4px !important;
            }
        }
    </style>
</x-filament::page>
