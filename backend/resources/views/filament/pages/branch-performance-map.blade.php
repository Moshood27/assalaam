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
        <style>
            .gm-style-iw { border-radius: 12px; padding: 0 !important; }
            .gm-style-iw-d { overflow: hidden !important; padding: 12px !important; }
            #map { font-family: inherit; }
        </style>
        @endpush

        @push('scripts')
        <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}"></script>
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

                const map = new google.maps.Map(mapContainer, {
                    center: { lat: parseFloat(validBranches[0].latitude), lng: parseFloat(validBranches[0].longitude) },
                    zoom: 6,
                    mapTypeControl: true,
                    streetViewControl: false,
                });

                const bounds = new google.maps.LatLngBounds();
                const infoWindow = new google.maps.InfoWindow();

                validBranches.forEach(branch => {
                    const color = branch.default_rate > 20 ? '#ef4444' : (branch.default_rate > 10 ? '#f97316' : '#22c55e');
                    const maxSavings = Math.max(...validBranches.map(b => Number(b.savings_rate) || 0)) || 1;
                    const scaled = 5 + ((Number(branch.savings_rate) || 0) / maxSavings) * 25;
                    const radius = Math.min(28, Math.max(8, scaled));

                    const position = { lat: parseFloat(branch.latitude), lng: parseFloat(branch.longitude) };

                    // Use a Marker with a custom SVG icon instead of Circle to match the "pixel-based" radius of Leaflet's circleMarker
                    const marker = new google.maps.Marker({
                        position: position,
                        map: map,
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            fillColor: color,
                            fillOpacity: 0.7,
                            scale: radius,
                            strokeColor: '#000',
                            strokeWeight: 1,
                        },
                        title: branch.name
                    });

                    const savingsFmt = (() => { try { return Number(branch.savings_rate || 0).toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); } catch { return branch.savings_rate; } })();

                    // Safe popup content
                    const popupContent = document.createElement('div');
                    popupContent.className = 'text-sm';
                    popupContent.style.minWidth = '200px';

                    const h3 = document.createElement('h3');
                    h3.className = 'font-bold text-base border-b border-gray-200 mb-2 pb-1';
                    h3.textContent = branch.name;
                    popupContent.appendChild(h3);

                    const p1 = document.createElement('p');
                    p1.className = 'mb-1';
                    p1.innerHTML = `<strong>Total Savings:</strong> ₦${savingsFmt}`;
                    popupContent.appendChild(p1);

                    const p2 = document.createElement('p');
                    p2.innerHTML = `<strong>Default Rate:</strong> <span style="color: ${color}; font-weight: bold;">${(Number(branch.default_rate) || 0).toFixed(2)}%</span>`;
                    popupContent.appendChild(p2);

                    marker.addListener('click', () => {
                        infoWindow.setContent(popupContent);
                        infoWindow.open(map, marker);
                    });

                    bounds.extend(position);
                });

                if (validBranches.length > 0) {
                    map.fitBounds(bounds);
                }
            });
        </script>
        @endpush
    </div>
</x-filament::page>
