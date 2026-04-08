<x-filament-panels::page>
<div class="w-full space-y-4">
    {{-- 1. Only ONE root element inside the page component --}}

    {{-- Map Legend --}}
    <div class="flex flex-wrap items-center justify-between gap-4 p-4 bg-white rounded-xl shadow-sm dark:bg-gray-800">
        <div class="flex items-center gap-2">
            <span class="inline-block w-4 h-4 bg-green-500 rounded-full"></span>
            <span class="text-sm">Low Default (< 10%)</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-block w-4 h-4 bg-orange-500 rounded-full"></span>
            <span class="text-sm">Medium Default (10% - 20%)</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-block w-4 h-4 bg-red-500 rounded-full"></span>
            <span class="text-sm">High Default (> 20%)</span>
        </div>
        <div class="text-sm text-gray-500 italic">
            Marker size reflects Savings Rate
        </div>
    </div>

    {{-- Map Container --}}
    <div wire:ignore
         id="map"
         style="height: 600px; width: 100%; border-radius: 12px; z-index: 1;"
         class="border border-gray-300 dark:border-gray-700 shadow-lg">
    </div>

    {{-- CSS and JS specifically for this page --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
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

            // Initialize Map
            const map = L.map('map').setView([validBranches[0].latitude, validBranches[0].longitude], 6);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);

            const markers = [];

            validBranches.forEach(branch => {
                const color = branch.default_rate > 20 ? '#ef4444' : (branch.default_rate > 10 ? '#f97316' : '#22c55e');
                const maxSavings = Math.max(...validBranches.map(b => b.savings_rate)) || 1;
                const radius = 5 + (branch.savings_rate / maxSavings) * 25;

                const marker = L.circleMarker([branch.latitude, branch.longitude], {
                    radius: radius,
                    fillColor: color,
                    color: "#000",
                    weight: 1,
                    opacity: 1,
                    fillOpacity: 0.7
                }).addTo(map);

                marker.bindPopup(`
                    <div style="min-width: 200px">
                        <h3 style="font-weight: bold; border-bottom: 1px solid #ddd; margin-bottom: 5px;">${branch.name}</h3>
                        <p>Savings: ₦${branch.savings_rate.toLocaleString()}</p>
                        <p>Default Rate: <span style="color: ${color}">${branch.default_rate}%</span></p>
                    </div>
                `);

                markers.push([branch.latitude, branch.longitude]);
            });

            if (markers.length > 0) {
                map.fitBounds(L.latLngBounds(markers), { padding: [50, 50] });
            }
        });
    </script>

    <style>
        .leaflet-popup-content-wrapper { border-radius: 8px; }
        .leaflet-container { font-family: inherit; }
    </style>
</div>
</x-filament-panels::page>
