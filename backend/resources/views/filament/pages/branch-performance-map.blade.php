<x-filament::page>
    <div class="w-full space-y-4">
        {{-- Map Legend --}}
        <div class="flex flex-wrap items-center justify-between gap-4 p-4 bg-white rounded-xl shadow-sm dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-2">
                <span class="inline-block w-4 h-4 bg-green-500 rounded-full"></span>
                <span class="text-sm font-medium">Low Default (< 10%)</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-block w-4 h-4 bg-orange-500 rounded-full"></span>
                <span class="text-sm font-medium">Medium Default (10% - 20%)</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-block w-4 h-4 bg-red-500 rounded-full"></span>
                <span class="text-sm font-medium">High Default (> 20%)</span>
            </div>
            <div class="flex-1 text-sm text-gray-500 italic">
                Marker size reflects Savings Total
            </div>
            <div>
                <button onclick="window.location.reload()" class="px-3 py-2 text-sm font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">Refresh Data</button>
            </div>
        </div>

        {{-- Aggregate Totals --}}
        <div id="agg" class="flex flex-wrap items-center gap-4 p-4 bg-white rounded-xl shadow-sm dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
            <div class="text-sm"><span class="text-gray-500">Branches:</span> <span id="agg-branches" class="font-semibold">0</span></div>
            <div class="text-sm"><span class="text-gray-500">Total Savings:</span> <span id="agg-savings" class="font-semibold">₦0.00</span></div>
            <div class="text-sm"><span class="text-gray-500">Avg Default Rate:</span> <span id="agg-default" class="font-semibold">0%</span></div>
        </div>

        {{-- Map Container --}}
        <div wire:ignore
             id="map"
             style="height: 600px; width: 100%; border-radius: 12px; z-index: 1;"
             class="border border-gray-300 dark:border-gray-700 shadow-lg">
        </div>

        @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <style>
            .leaflet-popup-content-wrapper { border-radius: 12px; padding: 0; overflow: hidden; }
            .leaflet-popup-content { margin: 12px !important; width: auto !important; }
            .leaflet-container { font-family: inherit; }
        </style>
        @endpush

        @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            document.addEventListener('livewire:initialized', function () {
                const branches = @json($branches);
                const mapContainer = document.getElementById('map');

                if (!branches || branches.length === 0) {
                    mapContainer.innerHTML = '<div class="flex items-center justify-center h-full text-gray-500">No branches found.</div>';
                    return;
                }

                const validBranches = branches.filter(b => b.latitude && b.longitude);
                if (validBranches.length === 0) return;

                // Aggregate
                const totalSavings = validBranches.reduce((a, b) => a + (Number(b.savings_rate) || 0), 0);
                const avgDefault = validBranches.reduce((a, b) => a + (Number(b.default_rate) || 0), 0) / validBranches.length;
                const fmt = (n) => {
                    try { return Number(n).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); } catch { return n }
                };
                document.getElementById('agg-branches').textContent = String(validBranches.length);
                document.getElementById('agg-savings').textContent = `₦ ${fmt(totalSavings)}`;
                document.getElementById('agg-default').textContent = `${(avgDefault || 0).toFixed(2)}%`;

                const map = L.map('map').setView([validBranches[0].latitude, validBranches[0].longitude], 6);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);

                const markers = [];

                validBranches.forEach(branch => {
                    const color = branch.default_rate > 20 ? '#ef4444' : (branch.default_rate > 10 ? '#f97316' : '#22c55e');
                    const maxSavings = Math.max(...validBranches.map(b => Number(b.savings_rate) || 0)) || 1;
                    const scaled = 5 + ((Number(branch.savings_rate) || 0) / maxSavings) * 25;
                    const radius = Math.min(28, Math.max(8, scaled));

                    const marker = L.circleMarker([branch.latitude, branch.longitude], {
                        radius: radius,
                        fillColor: color,
                        color: "#000",
                        weight: 1,
                        opacity: 1,
                        fillOpacity: 0.7
                    }).addTo(map);

                    const savingsFmt = (() => { try { return Number(branch.savings_rate || 0).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); } catch { return branch.savings_rate; } })();
                    marker.bindPopup(`
                        <div class="text-sm" style="min-width: 200px">
                            <h3 class="font-bold text-base border-b border-gray-200 mb-2 pb-1">${branch.name}</h3>
                            <p class="mb-1"><strong>Total Savings:</strong> ₦${savingsFmt}</p>
                            <p><strong>Default Rate:</strong> <span style="color: ${color}; font-weight: bold;">${(Number(branch.default_rate) || 0).toFixed(2)}%</span></p>
                        </div>
                    `);
                    marker.bindTooltip(`${branch.name}: ₦${savingsFmt}`, { direction: 'top' });

                    markers.push([branch.latitude, branch.longitude]);
                });

                if (markers.length > 0) {
                    map.fitBounds(L.latLngBounds(markers), { padding: [50, 50] });
                }
            });
        </script>
        @endpush
    </div>
</x-filament::page>
