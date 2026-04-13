<div
    x-data="{
        lat: @entangle('data.venue_lat'),
        lng: @entangle('data.venue_lng'),
        map: null,
        marker: null,
        searchQuery: '',
        isSearching: false,
        init() {
            if (typeof L === 'undefined') {
                if (!document.getElementById('leaflet-css')) {
                    const link = document.createElement('link');
                    link.id = 'leaflet-css';
                    link.rel = 'stylesheet';
                    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                    document.head.appendChild(link);
                }

                if (!document.getElementById('leaflet-js')) {
                    const script = document.createElement('script');
                    script.id = 'leaflet-js';
                    script.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                    script.onload = () => this.initMap();
                    document.head.appendChild(script);
                } else {
                    // Script is loading, check periodically
                    const interval = setInterval(() => {
                        if (typeof L !== 'undefined') {
                            clearInterval(interval);
                            this.initMap();
                        }
                    }, 100);
                }
            } else {
                this.initMap();
            }
        },
        initMap() {
            if (this.map) return;

            this.map = L.map($refs.map).setView([this.lat || 9.0820, this.lng || 8.6753], this.lat ? 15 : 6);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap'
            }).addTo(this.map);

            if (this.lat && this.lng) {
                this.marker = L.marker([this.lat, this.lng]).addTo(this.map);
            }

            this.map.on('click', (e) => {
                this.lat = e.latlng.lat;
                this.lng = e.latlng.lng;
                this.searchQuery = ''; // Clear search when manually picking
                if (this.marker) {
                    this.marker.setLatLng(e.latlng);
                } else {
                    this.marker = L.marker(e.latlng).addTo(this.map);
                }
            });
        },
        updateFromInputs() {
            if (this.map && this.lat && this.lng) {
                const latlng = [parseFloat(this.lat), parseFloat(this.lng)];
                if (this.marker) {
                    this.marker.setLatLng(latlng);
                } else {
                    this.marker = L.marker(latlng).addTo(this.map);
                }
                this.map.panTo(latlng);
            }
        },
        async searchLocation() {
            if (!this.searchQuery) return;
            this.isSearching = true;
            try {
                // Using Nominatim with country preference for Nigeria (NG)
                const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(this.searchQuery)}&countrycodes=ng&limit=1`;
                const response = await fetch(url, {
                    headers: {
                        'User-Agent': 'Attaqwa-Cooperative-Admin-Map-Picker'
                    }
                });
                const data = await response.json();
                if (data && data.length > 0) {
                    const result = data[0];
                    this.lat = parseFloat(result.lat);
                    this.lng = parseFloat(result.lon);
                    this.updateFromInputs();
                    this.map.setView([this.lat, this.lng], 15);
                    this.searchQuery = result.display_name;
                } else {
                    alert('Location not found for: ' + this.searchQuery + '. Please try a more specific search (e.g. Street, Town).');
                }
            } catch (error) {
                console.error('Search error:', error);
                alert('An error occurred while searching. Please check your internet connection.');
            } finally {
                this.isSearching = false;
            }
        }
    }"
    x-init="$watch('lat', value => updateFromInputs()); $watch('lng', value => updateFromInputs());"
    class="w-full"
>
    <div class="flex items-center space-x-2 mb-2">
        <div class="flex-1">
            <input
                type="text"
                x-model="searchQuery"
                @keydown.enter.prevent="searchLocation()"
                placeholder="Search for street address..."
                class="block w-full border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6 rounded-md dark:bg-white/5 dark:text-white dark:ring-white/10"
            >
        </div>
        <button
            type="button"
            @click="searchLocation()"
            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:opacity-50 dark:bg-white/5 dark:text-white dark:ring-white/10"
            :disabled="isSearching"
        >
            <span x-show="!isSearching">Search</span>
            <span x-show="isSearching">...</span>
        </button>
        <button
            type="button"
            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:ring-white/10"
            x-on:click="
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(pos => {
                        this.lat = pos.coords.latitude;
                        this.lng = pos.coords.longitude;
                        this.updateFromInputs();
                        this.map.setView([this.lat, this.lng], 15);
                    }, (err) => {
                        console.error('Geolocation error:', err);
                        alert('Unable to retrieve your location: ' + (err.message || 'Permission denied'));
                    }, { enableHighAccuracy: true, timeout: 5000, maximumAge: 0 });
                } else {
                    alert('Geolocation is not supported by your browser.');
                }
            "
            title="Get Current Location"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
        </button>
    </div>

    <div x-ref="map" style="height: 400px; width: 100%; border-radius: 8px; z-index: 1;" wire:ignore></div>
</div>
