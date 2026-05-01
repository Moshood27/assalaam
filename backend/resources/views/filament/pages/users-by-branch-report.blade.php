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
                        Total Users: <span class="text-primary-600 dark:text-primary-400 font-bold">{{ count($branch['members']) }}</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                        <thead>
                            <tr class="bg-gray-50/50 dark:bg-gray-800/40">
                                <th class="px-5 py-3 text-left font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Member</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Membership #</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Email</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Phone</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Status</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Joined At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($branch['members'] as $member)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="px-5 py-3 text-gray-900 dark:text-gray-100 font-medium">{{ $member['member_name'] }}</td>
                                    <td class="px-5 py-3 text-gray-600 dark:text-gray-400 font-mono text-xs">{{ $member['membership_number'] }}</td>
                                    <td class="px-5 py-3 text-gray-600 dark:text-gray-400">{{ $member['email'] }}</td>
                                    <td class="px-5 py-3 text-gray-600 dark:text-gray-400">{{ $member['phone'] }}</td>
                                    <td class="px-5 py-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $member['status'] === 'approved' ? 'bg-success-500/10 text-success-700' : 'bg-gray-500/10 text-gray-700' }}">
                                            {{ ucfirst($member['status']) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3 text-gray-600 dark:text-gray-400">{{ $member['joined_at'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center p-12 bg-white dark:bg-gray-900 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 no-print">
                <p class="text-gray-500 dark:text-gray-400 font-medium">No users found.</p>
            </div>
        @endforelse

        @if(count($report['branches']) > 1)
            <div class="rounded-xl border-2 border-primary-500 dark:border-primary-400 bg-primary-50/50 dark:bg-primary-900/20 p-6 break-inside-avoid">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100 uppercase tracking-tight">Organization Grand Total</h3>
                    <div class="text-2xl font-black text-primary-700 dark:text-primary-300">
                        {{ $report['grand_total_members_count'] }} Members
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
