<div
    x-data="{
        lat: @entangle('data.venue_lat'),
        lng: @entangle('data.venue_lng'),
        map: null,
        marker: null,
        searchQuery: '',
        isSearching: false,
        apiKey: '{{ config('services.google.maps_api_key') }}',
        init() {
            if (!this.apiKey) {
                console.error('Google Maps API Key is missing. Please set GOOGLE_MAPS_API_KEY in your .env file.');
                return;
            }

            if (typeof google === 'undefined') {
                if (!document.getElementById('google-maps-js')) {
                    const script = document.createElement('script');
                    script.id = 'google-maps-js';
                    script.src = `https://maps.googleapis.com/maps/api/js?key=${this.apiKey}&libraries=places`;
                    script.onload = () => this.initMap();
                    document.head.appendChild(script);
                }
            } else {
                this.initMap();
            }
        },
        initMap() {
            if (this.map) return;

            const center = { lat: parseFloat(this.lat) || 9.0820, lng: parseFloat(this.lng) || 8.6753 };
            this.map = new google.maps.Map($refs.map, {
                center: center,
                zoom: this.lat ? 15 : 6,
                mapTypeControl: true,
                streetViewControl: false,
                fullscreenControl: true
            });

            if (this.lat && this.lng) {
                this.marker = new google.maps.Marker({
                    position: center,
                    map: this.map,
                    draggable: true
                });
            }

            this.map.addListener('click', (e) => {
                this.updateLocation(e.latLng.lat(), e.latLng.lng());
            });

            if (this.marker) {
                this.marker.addListener('dragend', (e) => {
                    this.updateLocation(e.latLng.lat(), e.latLng.lng());
                });
            }

            // Initialize Autocomplete
            const input = $refs.searchInput;
            const autocomplete = new google.maps.places.Autocomplete(input, {
                componentRestrictions: { country: 'NG' },
                fields: ['geometry', 'name', 'formatted_address']
            });

            autocomplete.addListener('place_changed', () => {
                const place = autocomplete.getPlace();
                if (!place.geometry || !place.geometry.location) {
                    return;
                }

                this.updateLocation(place.geometry.location.lat(), place.geometry.location.lng());
                this.map.setCenter(place.geometry.location);
                this.map.setZoom(17);
                this.searchQuery = place.formatted_address || place.name;
            });
        },
        updateLocation(lat, lng) {
            this.lat = lat;
            this.lng = lng;

            if (this.marker) {
                this.marker.setPosition({ lat, lng });
            } else {
                this.marker = new google.maps.Marker({
                    position: { lat, lng },
                    map: this.map,
                    draggable: true
                });
                this.marker.addListener('dragend', (e) => {
                    this.updateLocation(e.latLng.lat(), e.latLng.lng());
                });
            }
        },
        updateFromInputs() {
            if (this.map && this.lat && this.lng) {
                const latlng = { lat: parseFloat(this.lat), lng: parseFloat(this.lng) };
                if (this.marker) {
                    this.marker.setPosition(latlng);
                } else {
                    this.marker = new google.maps.Marker({
                        position: latlng,
                        map: this.map,
                        draggable: true
                    });
                }
                this.map.panTo(latlng);
            }
        },
        getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(pos => {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    this.updateLocation(lat, lng);
                    this.map.setCenter({ lat, lng });
                    this.map.setZoom(15);
                }, (err) => {
                    console.error('Geolocation error:', err);
                    alert('Unable to retrieve your location: ' + (err.message || 'Permission denied'));
                }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
            } else {
                alert('Geolocation is not supported by your browser.');
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
                x-ref="searchInput"
                x-model="searchQuery"
                @keydown.enter.prevent
                placeholder="Search for street address..."
                class="block w-full border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6 rounded-md dark:bg-white/5 dark:text-white dark:ring-white/10"
            >
        </div>
        <button
            type="button"
            class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:ring-white/10"
            @click="getCurrentLocation()"
            title="Get Current Location"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
        </button>
    </div>

    <div x-ref="map" style="height: 400px; width: 100%; border-radius: 8px; z-index: 1;" wire:ignore></div>
</div>
