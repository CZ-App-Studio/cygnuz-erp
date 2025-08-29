<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">{{ __('Branding Settings') }}</h4>
</div>

<form id="brandingSettingsForm">
    @csrf
    <div class="row g-4">
        <!-- Brand Identity -->
        <div class="col-12">
            <h6 class="text-muted fw-light mb-3">{{ __('Brand Identity') }}</h6>
        </div>

        <div class="col-md-6">
            <x-settings-input
                name="brand_name"
                :label="__('Brand Name')"
                :value="$settings['brand_name'] ?? config('app.name')"
                :help="__('Your brand name')"
                required
            />
        </div>

        <div class="col-md-6">
            <x-settings-input
                name="brand_tagline"
                :label="__('Brand Tagline')"
                :value="$settings['brand_tagline'] ?? ''"
                :help="__('Short brand description or slogan')"
            />
        </div>

        <!-- Logo Settings -->
        <div class="col-12 mt-4">
            <h6 class="text-muted fw-light mb-3">{{ __('Logo Settings') }}</h6>
        </div>

        <div class="col-md-6">
            <label class="form-label">{{ __('Light Logo') }}</label>
            <div class="d-flex align-items-center gap-3 mb-3">
                @php
                    $lightLogoPath = 'assets/img/light_logo.png';
                    $lightLogoExists = file_exists(public_path($lightLogoPath));
                @endphp
                @if($lightLogoExists)
                    <img src="{{ asset($lightLogoPath) }}?v={{ time() }}" alt="Light Logo" class="rounded" style="height: 40px;">
                @else
                    <div class="avatar avatar-lg">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="bx bx-image"></i>
                        </span>
                    </div>
                @endif
                <div class="flex-grow-1">
                    <input type="file" name="brand_logo_light" id="brand_logo_light" class="form-control" accept="image/*">
                    <div class="form-text">{{ __('Logo for light backgrounds (PNG recommended)') }}</div>
                    <div class="form-text text-muted small">{{ __('Path: public/assets/img/light_logo.png') }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <label class="form-label">{{ __('Dark Logo') }}</label>
            <div class="d-flex align-items-center gap-3 mb-3">
                @php
                    $darkLogoPath = 'assets/img/dark_logo.png';
                    $darkLogoExists = file_exists(public_path($darkLogoPath));
                @endphp
                @if($darkLogoExists)
                    <img src="{{ asset($darkLogoPath) }}?v={{ time() }}" alt="Dark Logo" class="rounded bg-dark" style="height: 40px;">
                @else
                    <div class="avatar avatar-lg">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="bx bx-image"></i>
                        </span>
                    </div>
                @endif
                <div class="flex-grow-1">
                    <input type="file" name="brand_logo_dark" id="brand_logo_dark" class="form-control" accept="image/*">
                    <div class="form-text">{{ __('Logo for dark backgrounds (PNG recommended)') }}</div>
                    <div class="form-text text-muted small">{{ __('Path: public/assets/img/dark_logo.png') }}</div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <label class="form-label">{{ __('Favicon') }}</label>
            <div class="d-flex align-items-center gap-3 mb-3">
                @php
                    $faviconPath = 'assets/img/favicon/favicon.ico';
                    $faviconExists = file_exists(public_path($faviconPath));
                @endphp
                @if($faviconExists)
                    <img src="{{ asset($faviconPath) }}?v={{ time() }}" alt="Favicon" class="rounded" style="height: 32px; width: 32px;">
                @else
                    <div class="avatar avatar-sm">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="bx bx-star"></i>
                        </span>
                    </div>
                @endif
                <div class="flex-grow-1">
                    <input type="file" name="brand_favicon" id="brand_favicon" class="form-control" accept=".ico,.png,.jpg,.jpeg">
                    <div class="form-text">{{ __('Browser tab icon (32x32 recommended, ICO format preferred)') }}</div>
                    <div class="form-text text-muted small">{{ __('Path: public/assets/img/favicon/favicon.ico') }}</div>
                </div>
            </div>
        </div>

        <!-- Color Scheme -->
        <div class="col-12 mt-4">
            <h6 class="text-muted fw-light mb-3">{{ __('Brand Color') }}</h6>
        </div>

        <div class="col-md-6">
            <x-settings-input
                name="primary_color"
                type="color"
                :label="__('Primary Color')"
                :value="$settings['primary_color'] ?? '#f7ac19'"
                :help="__('Main brand color used throughout the system')"
            />
        </div>

        <!-- Footer Settings -->
        <div class="col-12 mt-4">
            <h6 class="text-muted fw-light mb-3">{{ __('Footer Settings') }}</h6>
        </div>

        <div class="col-md-6">
            <x-settings-input
                name="footer_text"
                :label="__('Footer Text')"
                :value="$settings['footer_text'] ?? ''"
                :help="__('Text to display in footer')"
            />
        </div>

        <div class="col-md-6">
            <x-settings-toggle
                name="show_powered_by"
                :label="__('Show Powered By')"
                :value="$settings['show_powered_by'] ?? true"
                :help="__('Show powered by text in footer')"
            />
        </div>

        <div class="col-md-6">
            <x-settings-toggle
                name="show_footer_links"
                :label="__('Show Footer Links')"
                :value="$settings['show_footer_links'] ?? true"
                :help="__('Show License, More Themes, Documentation, and Support links in footer')"
            />
        </div>

        <div class="col-12">
            <div class="alert alert-info">
                <i class="bx bx-info-circle me-2"></i>
                {{ __('Customize your brand identity, logos, and visual appearance.') }}
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
    // Live preview for file uploads
    $('#brand_logo_light').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            const $input = $(this);
            reader.onload = function(event) {
                $input.closest('.mb-3').find('img, .avatar').first().replaceWith(
                    `<img src="${event.target.result}" alt="Light Logo" class="rounded" style="height: 40px;">`
                );
            };
            reader.readAsDataURL(file);
        }
    });
    
    $('#brand_logo_dark').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            const $input = $(this);
            reader.onload = function(event) {
                $input.closest('.mb-3').find('img, .avatar').first().replaceWith(
                    `<img src="${event.target.result}" alt="Dark Logo" class="rounded bg-dark" style="height: 40px;">`
                );
            };
            reader.readAsDataURL(file);
        }
    });
    
    $('#brand_favicon').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            const $input = $(this);
            reader.onload = function(event) {
                $input.closest('.mb-3').find('img, .avatar').first().replaceWith(
                    `<img src="${event.target.result}" alt="Favicon" class="rounded" style="height: 32px; width: 32px;">`
                );
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Form submission
    $('#brandingSettingsForm').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        // Fix toggle values
        const toggleFields = ['show_powered_by', 'show_footer_links'];
        toggleFields.forEach(field => {
            const isChecked = $(`#${field}`).is(':checked');
            formData.delete(field);
            formData.append(field, isChecked ? '1' : '0');
        });

        $.ajax({
            url: "{{ route('settings.system.update', 'branding') }}",
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Check if primary color was changed
                    const colorChanged = formData.has('primary_color');
                    
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __("Success") }}',
                        text: response.message,
                        timer: colorChanged ? 3000 : 2000,
                        showConfirmButton: false,
                        footer: colorChanged ? '<small class="text-muted">{{ __("CSS files are being rebuilt to apply the new brand color") }}</small>' : null
                    });
                    
                    // If files were uploaded, reload page after a delay to show new images
                    if (formData.has('brand_logo_light') || formData.has('brand_logo_dark') || formData.has('brand_favicon')) {
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    }
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
