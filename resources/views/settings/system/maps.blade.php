<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Maps & Location Settings') }}</h4>
</div>

<form id="mapsSettingsForm">
    @csrf
    <div class="row g-4">
        <!-- Google Maps Settings -->
        <div class="col-12">
            <h6 class="text-muted fw-light mb-3">{{ __('Google Maps Configuration') }}</h6>
        </div>
        
        <div class="col-md-12">
            <x-settings-input
                name="google_maps_api_key"
                :label="__('Google Maps API Key')"
                :value="$settings['google_maps_api_key'] ?? ''"
                :help="__('Enter your Google Maps API key for map functionality')"
                type="password"
                required
            />
        </div>
        
        <!-- Location Settings -->
        <div class="col-12 mt-4">
            <h6 class="text-muted fw-light mb-3">{{ __('Default Location Settings') }}</h6>
        </div>
        
        <div class="col-md-6">
            <x-settings-input
                name="default_latitude"
                :label="__('Default Latitude')"
                :value="$settings['default_latitude'] ?? '0'"
                :help="__('Default map center latitude')"
                type="number"
                step="0.000001"
                min="-90"
                max="90"
            />
        </div>
        
        <div class="col-md-6">
            <x-settings-input
                name="default_longitude"
                :label="__('Default Longitude')"
                :value="$settings['default_longitude'] ?? '0'"
                :help="__('Default map center longitude')"
                type="number"
                step="0.000001"
                min="-180"
                max="180"
            />
        </div>
        
        <div class="col-md-6">
            <x-settings-input
                name="default_zoom_level"
                :label="__('Default Zoom Level')"
                :value="$settings['default_zoom_level'] ?? '13'"
                :help="__('Default map zoom level (1-20)')"
                type="number"
                min="1"
                max="20"
            />
        </div>
        
        <div class="col-md-6">
            <x-settings-select
                name="distance_unit"
                :label="__('Distance Unit')"
                :value="$settings['distance_unit'] ?? 'km'"
                :options="[
                    'km' => __('Kilometers'),
                    'mi' => __('Miles'),
                    'm' => __('Meters'),
                    'ft' => __('Feet')
                ]"
                :help="__('Unit for distance calculations')"
            />
        </div>
        
        <div class="col-12">
            <div class="alert alert-info">
                <i class="bx bx-info-circle me-2"></i>
                {{ __('Configure your Google Maps API key to enable map functionality throughout the system.') }}
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <button type="submit" class="btn btn-primary">
            <i class="bx bx-save me-1"></i> {{ __('Save Settings') }}
        </button>
    </div>
</form>

@push('scripts')
<script>
$(function() {
    // Form submission
    $('#mapsSettingsForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // Set map provider to google by default
        formData.append('map_provider', 'google');
        
        $.ajax({
            url: "{{ route('settings.system.update', 'maps') }}",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __("Success") }}',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            },
            error: function(xhr) {
                let message = '{{ __("An error occurred") }}';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: '{{ __("Error") }}',
                    text: message
                });
            }
        });
    });
});
</script>
@endpush

