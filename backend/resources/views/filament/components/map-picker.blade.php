<div
    x-data="{
        lat: @entangle('data.venue_lat'),
        lng: @entangle('data.venue_lng'),
        map: null,
        marker: null,
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
        }
    }"
    x-init="$watch('lat', value => updateFromInputs()); $watch('lng', value => updateFromInputs());"
    class="w-full"
>
    <div x-ref="map" style="height: 400px; width: 100%; border-radius: 8px; z-index: 1;" wire:ignore></div>
    <div class="mt-2">
        <button
            type="button"
            class="px-3 py-1 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none"
            x-on:click="
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(pos => {
                        lat = pos.coords.latitude;
                        lng = pos.coords.longitude;
                        updateFromInputs();
                        map.setView([lat, lng], 15);
                    });
                }
            "
        >
            Get Current Location
        </button>
    </div>
</div>
