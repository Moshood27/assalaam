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
                        Outstanding: <span class="text-primary-600 dark:text-primary-400 font-bold">₦{{ number_format($branch['total_outstanding'], 2) }}</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                        <thead>
                            <tr class="bg-gray-50/50 dark:bg-gray-800/40">
                                <th class="px-5 py-3 text-left font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Member</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Loan ID</th>
                                <th class="px-5 py-3 text-right font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Principal</th>
                                <th class="px-5 py-3 text-right font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Paid</th>
                                <th class="px-5 py-3 text-right font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Overdue</th>
                                <th class="px-5 py-3 text-right font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Outstanding</th>
                                <th class="px-5 py-3 text-center font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Last Payment</th>
                                <th class="px-5 py-3 text-center font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($branch['loans'] as $loan)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="px-5 py-3 text-gray-900 dark:text-gray-100 font-medium">{{ $loan['member_name'] }}</td>
                                    <td class="px-5 py-3 text-gray-600 dark:text-gray-400 font-mono text-xs">{{ $loan['loan_id'] }}</td>
                                    <td class="px-5 py-3 text-right tabular-nums text-gray-900 dark:text-gray-100">₦{{ number_format($loan['principal'], 2) }}</td>
                                    <td class="px-5 py-3 text-right tabular-nums text-gray-900 dark:text-gray-100">₦{{ number_format($loan['paid'], 2) }}</td>
                                    <td class="px-5 py-3 text-right tabular-nums text-red-600 dark:text-red-400 font-bold">₦{{ number_format($loan['overdue'] ?? 0, 2) }}</td>
                                    <td class="px-5 py-3 text-right tabular-nums font-semibold text-primary-600 dark:text-primary-400">₦{{ number_format($loan['outstanding'], 2) }}</td>
                                    <td class="px-5 py-3 text-center text-gray-500 dark:text-gray-400">
                                        {{ $loan['last_payment_date'] ? \Carbon\Carbon::parse($loan['last_payment_date'])->format('d M Y') : 'None' }}
                                    </td>
                                    <td class="px-5 py-3 text-center">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20 capitalize">
                                            {{ $loan['status'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50/50 dark:bg-gray-800/40 font-bold border-t border-gray-200 dark:border-gray-800">
                            <tr>
                                <td colspan="2" class="px-5 py-4 text-gray-900 dark:text-gray-100 uppercase tracking-tight">Branch Totals</td>
                                <td class="px-5 py-4 text-right tabular-nums text-gray-900 dark:text-gray-100">₦{{ number_format($branch['total_principal'], 2) }}</td>
                                <td class="px-5 py-4 text-right tabular-nums text-gray-900 dark:text-gray-100">₦{{ number_format($branch['total_paid'], 2) }}</td>
                                <td class="px-5 py-4 text-right tabular-nums text-red-600 dark:text-red-400">₦{{ number_format($branch['total_overdue'] ?? 0, 2) }}</td>
                                <td class="px-5 py-4 text-right tabular-nums text-primary-600 dark:text-primary-400">₦{{ number_format($branch['total_outstanding'], 2) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center p-12 bg-white dark:bg-gray-900 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 no-print">
                <p class="text-gray-500 dark:text-gray-400 font-medium">No outstanding loans found.</p>
            </div>
        @endforelse

        @if(count($report['branches']) > 1)
            <div class="rounded-xl border-2 border-primary-500 dark:border-primary-400 bg-primary-50/50 dark:bg-primary-900/20 p-6 break-inside-avoid">
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">Organization Grand Totals</h3>
                <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Total Loans</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $report['grand_total_loans_count'] }}</div>
                    </div>
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Total Principal</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">₦{{ number_format($report['grand_total_principal'], 2) }}</div>
                    </div>
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400 uppercase font-semibold">Total Paid</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">₦{{ number_format($report['grand_total_paid'], 2) }}</div>
                    </div>
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-red-200 dark:border-red-800 ring-2 ring-red-500/10">
                        <div class="text-xs text-red-600 dark:text-red-400 uppercase font-bold">Total Overdue</div>
                        <div class="text-2xl font-black text-red-700 dark:text-red-300">₦{{ number_format($report['grand_total_overdue'] ?? 0, 2) }}</div>
                    </div>
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-primary-200 dark:border-primary-800 ring-2 ring-primary-500/20">
                        <div class="text-xs text-primary-600 dark:text-primary-400 uppercase font-bold">Total Outstanding</div>
                        <div class="text-2xl font-black text-primary-700 dark:text-primary-300">₦{{ number_format($report['grand_total_outstanding'], 2) }}</div>
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
