<div>
    @if (session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    <div class="address-header">
        <h3>Delivery Addresses</h3>
        <button type="button" wire:click="openForm()" class="btn btn-primary">Add Address</button>
    </div>

    @if ($showForm)
        <div class="address-form card">
            <h4>{{ $editingId ? 'Edit Address' : 'Add New Address' }}</h4>
            <form wire:submit.prevent="save">
                <div class="form-group">
                    <label>Label (e.g. Home, Office)</label>
                    <input type="text" wire:model="label" class="form-control" placeholder="Optional">
                </div>
                <div class="form-group">
                    <label>Address Line 1 <span class="required">*</span></label>
                    <input type="text" id="address-autocomplete" wire:model="address_line1" class="form-control" placeholder="Start typing or use current location" required>
                    @error('address_line1') <span class="error-message">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label>Address Line 2</label>
                    <input type="text" wire:model="address_line2" class="form-control" placeholder="Apt, suite, etc.">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>City <span class="required">*</span></label>
                        <input type="text" wire:model="city" class="form-control" required>
                        @error('city') <span class="error-message">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label>State</label>
                        <input type="text" wire:model="state" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Postal Code</label>
                        <input type="text" wire:model="postal_code" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label>Country</label>
                    <input type="text" wire:model="country" class="form-control">
                </div>
                <div class="form-group">
                    <button type="button" id="use-current-location" class="btn btn-secondary">Use Current Location</button>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" wire:model="is_default"> Set as default address
                    </label>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Address</button>
                    <button type="button" wire:click="closeForm" class="btn btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    @endif

    <div class="address-list">
        @forelse ($addresses as $addr)
            <div class="address-card {{ $addr['is_default'] ? 'is-default' : '' }}">
                @if ($addr['is_default'])
                    <span class="default-badge">Default</span>
                @endif
                @if ($addr['label'])
                    <strong>{{ $addr['label'] }}</strong><br>
                @endif
                {{ $addr['address_line1'] }}
                @if ($addr['address_line2'])
                    , {{ $addr['address_line2'] }}
                @endif
                <br>
                {{ $addr['city'] }}{{ $addr['state'] ? ', ' . $addr['state'] : '' }} {{ $addr['postal_code'] }} {{ $addr['country'] }}
                <div class="address-actions">
                    <button type="button" wire:click="openForm({{ $addr['id'] }})" class="btn btn-sm btn-edit">Edit</button>
                    @if (!$addr['is_default'])
                        <button type="button" wire:click="setDefault({{ $addr['id'] }})" class="btn btn-sm btn-secondary">Set Default</button>
                    @endif
                    <button type="button" wire:click="deleteAddress({{ $addr['id'] }})" onclick="return confirm('Remove this address?')" class="btn btn-sm btn-delete">Remove</button>
                </div>
            </div>
        @empty
            <p class="empty-addresses">No addresses yet. Add one to proceed with checkout.</p>
        @endforelse
    </div>
</div>

@push('scripts')
@php $mapsKey = config('services.google.maps_key', env('GOOGLE_MAPS_API_KEY', '')); @endphp
@if ($mapsKey)
<script src="https://maps.googleapis.com/maps/api/js?key={{ $mapsKey }}&libraries=places&callback=initGoogleMaps" async defer></script>
@endif
<script>
function initGoogleMaps() {
    if (typeof google === 'undefined' || !google.maps || !google.maps.places) return;
    const apiKey = '{{ $mapsKey ?? '' }}';

    function initAutocomplete() {
        const input = document.getElementById('address-autocomplete');
        if (!input || input._autocompleteInit) return;
        input._autocompleteInit = true;
        const autocomplete = new google.maps.places.Autocomplete(input, { types: ['address'], fields: ['address_components'] });
        autocomplete.addListener('place_changed', function() {
            const place = autocomplete.getPlace();
            if (!place.address_components) return;
            let street = '', city = '', state = '', postal = '', country = 'US';
            for (const c of place.address_components) {
                if (c.types.includes('street_number')) street = c.long_name + ' ';
                if (c.types.includes('route')) street += c.long_name;
                if (c.types.includes('locality')) city = c.long_name;
                if (c.types.includes('administrative_area_level_1')) state = c.short_name;
                if (c.types.includes('postal_code')) postal = c.long_name;
                if (c.types.includes('country')) country = c.short_name;
            }
            const addr1 = street || (place.name || '');
            const root = input.closest('[wire\\:id]');
            if (root && window.Livewire) {
                const c = Livewire.find(root.getAttribute('wire:id'));
                if (c) { c.set('address_line1', addr1); c.set('city', city); c.set('state', state); c.set('postal_code', postal); c.set('country', country); }
            }
        });
    }

    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'use-current-location') {
            if (!navigator.geolocation) { alert('Geolocation not supported.'); return; }
            const btn = e.target;
            btn.disabled = true; btn.textContent = 'Getting location...';
            navigator.geolocation.getCurrentPosition(function(pos) {
                const url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' + pos.coords.latitude + ',' + pos.coords.longitude + '&key=' + apiKey;
                fetch(url).then(r => r.json()).then(function(data) {
                    if (data.results && data.results[0]) {
                        const addr = data.results[0], comp = addr.address_components;
                        let street = '', city = '', state = '', postal = '', country = 'US';
                        for (var i = 0; i < comp.length; i++) {
                            var c = comp[i];
                            if (c.types.indexOf('street_number') >= 0) street = c.long_name + ' ';
                            if (c.types.indexOf('route') >= 0) street += c.long_name;
                            if (c.types.indexOf('locality') >= 0) city = c.long_name;
                            if (c.types.indexOf('administrative_area_level_1') >= 0) state = c.short_name;
                            if (c.types.indexOf('postal_code') >= 0) postal = c.long_name;
                            if (c.types.indexOf('country') >= 0) country = c.short_name;
                        }
                        const addr1 = street || addr.formatted_address;
                        const root = btn.closest('[wire\\:id]');
                        if (root && window.Livewire) {
                            const lw = Livewire.find(root.getAttribute('wire:id'));
                            if (lw) { lw.set('address_line1', addr1); lw.set('city', city); lw.set('state', state); lw.set('postal_code', postal); lw.set('country', country); lw.set('latitude', pos.coords.latitude); lw.set('longitude', pos.coords.longitude); }
                        }
                    }
                    btn.disabled = false; btn.textContent = 'Use Current Location';
                }).catch(function() { btn.disabled = false; btn.textContent = 'Use Current Location'; });
            }, function() { alert('Could not get location.'); btn.disabled = false; btn.textContent = 'Use Current Location'; });
        }
    });

    function tryInit() {
        if (document.getElementById('address-autocomplete')) initAutocomplete();
    }
    tryInit();
    if (window.Livewire && Livewire.hook) {
        Livewire.hook('message.processed', tryInit);
    } else {
        document.addEventListener('livewire:load', function() { setInterval(tryInit, 500); });
    }
}
if (typeof google !== 'undefined' && google.maps && google.maps.places) initGoogleMaps();
</script>
@endpush
