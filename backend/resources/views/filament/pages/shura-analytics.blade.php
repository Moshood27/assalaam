<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6">
        @foreach($this->getHeaderWidgets() as $widget)
            @livewire($widget)
        @endforeach
    </div>

    <div class="mt-8">
        <h2 class="text-xl font-bold tracking-tight">System Participation Breakdown</h2>
        <p class="text-gray-500">Overview of the most active governance activities in the last 30 days.</p>

        <div class="mt-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden border border-gray-100 dark:border-gray-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900 border-b border-gray-100 dark:border-gray-700">
                    <tr>
                        <th class="px-6 py-3 font-semibold">Activity Type</th>
                        <th class="px-6 py-3 font-semibold text-center">Total Participants</th>
                        <th class="px-6 py-3 font-semibold text-center">Avg. Weight/Vote</th>
                        <th class="px-6 py-3 font-semibold text-right">Last Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @php
                        $agmStats = \App\Models\AgmVote::select(
                            \Illuminate\Support\Facades\DB::raw('count(DISTINCT user_id) as participants'),
                            \Illuminate\Support\Facades\DB::raw('avg(weight) as avg_weight'),
                            \Illuminate\Support\Facades\DB::raw('max(created_at) as last_vote')
                        )->where('created_at', '>=', now()->subDays(30))->first();

                        $proposalStats = \App\Models\ProjectProposalVote::select(
                            \Illuminate\Support\Facades\DB::raw('count(DISTINCT user_id) as participants'),
                            \Illuminate\Support\Facades\DB::raw('avg(weight) as avg_weight'),
                            \Illuminate\Support\Facades\DB::raw('max(created_at) as last_vote')
                        )->where('created_at', '>=', now()->subDays(30))->first();
                    @endphp
                    <tr>
                        <td class="px-6 py-4 font-medium">AGM & Board Elections</td>
                        <td class="px-6 py-4 text-center">{{ $agmStats->participants ?? 0 }}</td>
                        <td class="px-6 py-4 text-center">{{ number_format($agmStats->avg_weight ?? 0, 2) }}</td>
                        <td class="px-6 py-4 text-right text-gray-400">{{ $agmStats->last_vote ? \Carbon\Carbon::parse($agmStats->last_vote)->diffForHumans() : 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td class="px-6 py-4 font-medium">Project & Investment Proposals</td>
                        <td class="px-6 py-4 text-center">{{ $proposalStats->participants ?? 0 }}</td>
                        <td class="px-6 py-4 text-center">{{ number_format($proposalStats->avg_weight ?? 0, 2) }}</td>
                        <td class="px-6 py-4 text-right text-gray-400">{{ $proposalStats->last_vote ? \Carbon\Carbon::parse($proposalStats->last_vote)->diffForHumans() : 'N/A' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
