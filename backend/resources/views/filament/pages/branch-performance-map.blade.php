<x-filament-panels::page>
    <div>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
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
                <div class="flex items-center gap-2 ml-4">
                    <span class="text-sm text-gray-500 italic">Marker size reflects Savings Rate</span>
                </div>
            </div>

            <div wire:ignore id="map" style="height: 600px; width: 100%; border-radius: 10px; z-index: 1;" class="border border-gray-300 dark:border-gray-700 shadow-sm"></div>
        </div>

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const branches = @json($branches);

                const mapContainer = document.getElementById('map');
                if (!branches || branches.length === 0) {
                    mapContainer.innerHTML = '<div class="flex items-center justify-center h-full text-gray-500">No branches with coordinates found. Please set coordinates in Branch Management.</div>';
                    return;
                }

                // Find a valid starting point
                const validBranches = branches.filter(b => b.latitude && b.longitude);
                if (validBranches.length === 0) {
                    mapContainer.innerHTML = '<div class="flex items-center justify-center h-full text-gray-500">No valid branch coordinates.</div>';
                    return;
                }

                const map = L.map('map').setView([validBranches[0].latitude, validBranches[0].longitude], 6);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);

                const markers = [];

                validBranches.forEach(branch => {
                    const color = branch.default_rate > 20 ? '#ef4444' : (branch.default_rate > 10 ? '#f97316' : '#22c55e');

                    // Scale radius between 5 and 30 based on savings rate relative to max
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
                        <div class="p-2">
                            <h3 class="font-bold text-lg border-b mb-2 pb-1">${branch.name}</h3>
                            <div class="space-y-1">
                                <p><span class="text-gray-600 font-medium">Savings Rate:</span> ₦${branch.savings_rate.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                                <p><span class="text-gray-600 font-medium">Default Rate:</span> <span class="${branch.default_rate > 20 ? 'text-red-600' : 'text-gray-900'} font-bold">${branch.default_rate}%</span></p>
                            </div>
                        </div>
                    `);

                    markers.push([branch.latitude, branch.longitude]);
                });

                if (markers.length > 0) {
                    const bounds = L.latLngBounds(markers);
                    map.fitBounds(bounds, { padding: [50, 50] });
                }
            });
        </script>

        <style>
            .leaflet-popup-content-wrapper {
                border-radius: 8px;
                padding: 0;
            }
            .leaflet-popup-content {
                margin: 0;
                width: 250px !important;
            }
        </style>
    </div>
</x-filament-panels::page>
