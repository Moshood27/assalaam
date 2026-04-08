<div> {{-- 1. THIS IS THE ONLY ROOT ELEMENT --}}
    <x-filament-panels::page>
        <div class="space-y-4">
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
        </div>
    </x-filament-panels::page>

    {{-- 2. MOVE ASSETS AND SCRIPTS INSIDE THE ROOT DIV BUT OUTSIDE THE PAGE TAG --}}
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
            if (validBranches.length === 0) return;

            const map = L.map('map').setView([validBranches[0].latitude, validBranches[0].longitude], 6);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
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
                    <div class="text-sm">
                        <h3 class="font-bold text-base border-b border-gray-200 mb-2 pb-1">${branch.name}</h3>
                        <p class="mb-1"><strong>Savings:</strong> ₦${branch.savings_rate.toLocaleString()}</p>
                        <p><strong>Default Rate:</strong> <span style="color: ${color}; font-weight: bold;">${branch.default_rate}%</span></p>
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
        .leaflet-popup-content-wrapper { border-radius: 8px; padding: 0; }
        .leaflet-popup-content { margin: 12px; width: 220px !important; }
        .leaflet-container { font-family: inherit; }
    </style>
</div> {{-- END ROOT ELEMENT --}}
